<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Dialog } from '@/ui/lazy-components'

const props = defineProps<{ file: File | null }>()
const emit = defineEmits<{
  (e: 'cropped', blob: Blob): void
  (e: 'cancel'): void
}>()

const { t } = useI18n()

/** Сторона окна кадрирования и итогового изображения. */
const VIEWPORT = 300
const OUTPUT = 512

const visible = ref(false)
const imageUrl = ref('')
const image = ref<HTMLImageElement>()
const naturalWidth = ref(0)
const naturalHeight = ref(0)
const scale = ref(1)
const minScale = ref(1)
const offset = ref({ x: 0, y: 0 })
const dragging = ref(false)
let dragStart = { x: 0, y: 0, ox: 0, oy: 0 }

const imageStyle = computed(() => ({
  width: `${naturalWidth.value * scale.value}px`,
  height: `${naturalHeight.value * scale.value}px`,
  transform: `translate(${offset.value.x}px, ${offset.value.y}px)`,
}))

watch(
  () => props.file,
  (file) => {
    if (!file) return
    if (imageUrl.value) URL.revokeObjectURL(imageUrl.value)
    imageUrl.value = URL.createObjectURL(file)
    visible.value = true
  },
)

function onImageLoad(): void {
  const element = image.value
  if (!element) return

  naturalWidth.value = element.naturalWidth
  naturalHeight.value = element.naturalHeight

  // Минимальный масштаб — тот, при котором картинка полностью закрывает окно.
  minScale.value = Math.max(VIEWPORT / element.naturalWidth, VIEWPORT / element.naturalHeight)
  scale.value = minScale.value
  centerImage()
}

function centerImage(): void {
  offset.value = {
    x: (VIEWPORT - naturalWidth.value * scale.value) / 2,
    y: (VIEWPORT - naturalHeight.value * scale.value) / 2,
  }
}

/** Не даём вытащить картинку за пределы окна — пустых углов быть не должно. */
function clamp(): void {
  const width = naturalWidth.value * scale.value
  const height = naturalHeight.value * scale.value

  offset.value = {
    x: Math.min(0, Math.max(VIEWPORT - width, offset.value.x)),
    y: Math.min(0, Math.max(VIEWPORT - height, offset.value.y)),
  }
}

function onZoom(value: number): void {
  const previous = scale.value
  scale.value = value

  // Масштабируем относительно центра окна, иначе картинка «уплывает».
  const center = VIEWPORT / 2
  const ratio = scale.value / previous
  offset.value = {
    x: center - (center - offset.value.x) * ratio,
    y: center - (center - offset.value.y) * ratio,
  }
  clamp()
}

function onWheel(event: WheelEvent): void {
  const next = scale.value * (event.deltaY < 0 ? 1.08 : 0.92)
  onZoom(Math.min(minScale.value * 4, Math.max(minScale.value, next)))
}

function onPointerDown(event: PointerEvent): void {
  dragging.value = true
  dragStart = { x: event.clientX, y: event.clientY, ox: offset.value.x, oy: offset.value.y }
  ;(event.target as HTMLElement).setPointerCapture(event.pointerId)
}

function onPointerMove(event: PointerEvent): void {
  if (!dragging.value) return
  offset.value = {
    x: dragStart.ox + (event.clientX - dragStart.x),
    y: dragStart.oy + (event.clientY - dragStart.y),
  }
  clamp()
}

function onPointerUp(): void {
  dragging.value = false
}

function close(): void {
  visible.value = false
  if (imageUrl.value) {
    URL.revokeObjectURL(imageUrl.value)
    imageUrl.value = ''
  }
}

function cancel(): void {
  close()
  emit('cancel')
}

/** Окно кадрирования переносится на канвас один в один, с коэффициентом OUTPUT/VIEWPORT. */
function apply(): void {
  const element = image.value
  if (!element) return

  const canvas = document.createElement('canvas')
  canvas.width = OUTPUT
  canvas.height = OUTPUT

  const context = canvas.getContext('2d')
  if (!context) return

  const k = OUTPUT / VIEWPORT
  context.imageSmoothingQuality = 'high'
  context.drawImage(
    element,
    offset.value.x * k,
    offset.value.y * k,
    naturalWidth.value * scale.value * k,
    naturalHeight.value * scale.value * k,
  )

  canvas.toBlob(
    (blob) => {
      if (blob) emit('cropped', blob)
      close()
    },
    'image/png',
    0.92,
  )
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    :draggable="false"
    :header="t('avatar.cropTitle')"
    :style="{ width: '360px' }"
    @hide="cancel"
  >
    <div class="cropper">
      <div
        class="cropper__viewport"
        :class="{ 'cropper__viewport--dragging': dragging }"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerUp"
        @wheel.prevent="onWheel"
      >
        <img
          ref="image"
          :src="imageUrl"
          alt=""
          class="cropper__image"
          :style="imageStyle"
          draggable="false"
          @load="onImageLoad"
        />
        <div class="cropper__mask" />
      </div>

      <div class="cropper__zoom">
        <i class="pi pi-image cropper__zoom-icon" />
        <input
          type="range"
          class="cropper__slider"
          :min="minScale"
          :max="minScale * 4"
          :step="minScale / 100"
          :value="scale"
          @input="onZoom(Number(($event.target as HTMLInputElement).value))"
        />
        <i class="pi pi-images cropper__zoom-icon" />
      </div>

      <p class="cropper__hint wf-muted">{{ t('avatar.cropHint') }}</p>
    </div>

    <template #footer>
      <Button :label="t('common.cancel')" severity="secondary" outlined @click="cancel" />
      <Button :label="t('avatar.cropApply')" icon="pi pi-check" @click="apply" />
    </template>
  </Dialog>
</template>

<style scoped lang="scss">
.cropper {
  display: flex;
  flex-direction: column;
  gap: 14px;
  align-items: center;
}

.cropper__viewport {
  position: relative;
  width: 300px;
  height: 300px;
  overflow: hidden;
  border-radius: 12px;
  background: var(--p-surface-200);
  cursor: grab;
  touch-action: none;
  user-select: none;

  &--dragging {
    cursor: grabbing;
  }
}

html.dark .cropper__viewport {
  background: var(--p-surface-800);
}

.cropper__image {
  position: absolute;
  top: 0;
  left: 0;
  transform-origin: top left;
  max-width: none;
}

/* Круг показывает, что попадёт в аватар; углы приглушены */
.cropper__mask {
  position: absolute;
  inset: 0;
  pointer-events: none;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.45);
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.85);
}

.cropper__zoom {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
}

.cropper__zoom-icon {
  color: var(--p-text-muted-color);
  font-size: 13px;

  &:last-child {
    font-size: 17px;
  }
}

.cropper__slider {
  flex: 1;
  accent-color: var(--p-primary-color);
}

.cropper__hint {
  margin: 0;
  font-size: 11.5px;
  text-align: center;
}
</style>
