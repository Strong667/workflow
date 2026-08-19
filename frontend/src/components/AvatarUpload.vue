<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AvatarCropper from '@/components/AvatarCropper.vue'
import { uploadsApi } from '@/api'
import { apiMessage } from '@/api/client'
import { useNotify } from '@/ui/feedback'

const props = withDefaults(
  defineProps<{
    modelValue: string | null
    initials?: string
    size?: number
  }>(),
  { initials: '', size: 96 },
)

const emit = defineEmits<{ (e: 'update:modelValue', value: string | null): void }>()

const { t } = useI18n()
const notify = useNotify()

const input = ref<HTMLInputElement>()
const uploading = ref(false)
const dragOver = ref(false)
const pendingFile = ref<File | null>(null)

const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
const MAX_BYTES = 2 * 1024 * 1024

const avatarStyle = computed(() => ({ width: `${props.size}px`, height: `${props.size}px` }))

function pick(): void {
  input.value?.click()
}

/** Файл не уходит на сервер сразу: сначала пользователь выбирает область. */
function onFiles(files: FileList | null): void {
  const file = files?.[0]
  if (!file) return

  // Проверяем на клиенте, чтобы не гонять заведомо негодный файл на сервер.
  if (!ACCEPTED.includes(file.type)) {
    notify.error(t('avatar.wrongType'))
    resetInput()
    return
  }
  if (file.size > MAX_BYTES) {
    notify.error(t('avatar.tooLarge'))
    resetInput()
    return
  }

  pendingFile.value = file
}

async function upload(blob: Blob): Promise<void> {
  const name = (pendingFile.value?.name ?? 'avatar').replace(/\.[^.]+$/, '')
  pendingFile.value = null
  resetInput()

  uploading.value = true
  try {
    emit('update:modelValue', await uploadsApi.avatar(new File([blob], `${name}.png`, { type: 'image/png' })))
    notify.success(t('avatar.uploaded'))
  } catch (error) {
    notify.error(apiMessage(error, t('avatar.failed')))
  } finally {
    uploading.value = false
  }
}

function cancelCrop(): void {
  pendingFile.value = null
  resetInput()
}

function resetInput(): void {
  if (input.value) input.value.value = ''
}

function onDrop(event: DragEvent): void {
  dragOver.value = false
  onFiles(event.dataTransfer?.files ?? null)
}

function remove(): void {
  emit('update:modelValue', null)
}
</script>

<template>
  <div class="avatar-upload">
    <div
      class="avatar-upload__preview"
      :class="{ 'avatar-upload__preview--over': dragOver }"
      :style="avatarStyle"
      role="button"
      tabindex="0"
      :aria-label="t('avatar.upload')"
      @click="pick"
      @keydown.enter.prevent="pick"
      @keydown.space.prevent="pick"
      @dragover.prevent="dragOver = true"
      @dragleave="dragOver = false"
      @drop.prevent="onDrop"
    >
      <img v-if="modelValue" :src="modelValue" alt="" class="avatar-upload__image" />
      <span v-else class="avatar-upload__initials">{{ initials || '?' }}</span>

      <div class="avatar-upload__overlay">
        <i class="pi pi-camera" />
      </div>

      <div v-if="uploading" class="avatar-upload__loading">
        <ProgressSpinner style="width: 28px; height: 28px" stroke-width="5" />
      </div>
    </div>

    <div class="avatar-upload__actions">
      <Button
        type="button"
        icon="pi pi-upload"
        :label="modelValue ? t('avatar.replace') : t('avatar.upload')"
        size="small"
        severity="secondary"
        outlined
        :disabled="uploading"
        @click="pick"
      />
      <Button
        v-if="modelValue"
        type="button"
        icon="pi pi-trash"
        :label="t('avatar.remove')"
        size="small"
        severity="danger"
        text
        :disabled="uploading"
        @click="remove"
      />
      <small class="avatar-upload__hint wf-muted">{{ t('avatar.hint') }}</small>
    </div>

    <input
      ref="input"
      type="file"
      class="avatar-upload__input"
      :accept="ACCEPTED.join(',')"
      @change="onFiles(($event.target as HTMLInputElement).files)"
    />

    <AvatarCropper :file="pendingFile" @cropped="upload" @cancel="cancelCrop" />
  </div>
</template>

<style scoped lang="scss">
.avatar-upload {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.avatar-upload__preview {
  position: relative;
  border-radius: 50%;
  overflow: hidden;
  display: grid;
  place-items: center;
  cursor: pointer;
  flex: 0 0 auto;
  background: var(--p-primary-color);
  color: var(--p-primary-contrast-color);
  border: 2px dashed transparent;
  outline-offset: 3px;
  transition: border-color 0.15s ease, opacity 0.15s ease;

  &:hover .avatar-upload__overlay,
  &:focus-visible .avatar-upload__overlay {
    opacity: 1;
  }

  &--over {
    border-color: var(--p-primary-color);
    opacity: 0.85;
  }
}

.avatar-upload__image {
  // Абсолютное позиционирование: у grid-элемента с place-items: center
  // высота в процентах не разрешается и картинка сохраняет свои пропорции.
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-upload__initials {
  font-size: 26px;
  font-weight: 650;
  letter-spacing: 0.5px;
}

.avatar-upload__overlay {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, 0.45);
  color: #fff;
  font-size: 20px;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.avatar-upload__loading {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, 0.35);
}

.avatar-upload__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.avatar-upload__hint {
  flex-basis: 100%;
  font-size: 11.5px;
}

.avatar-upload__input {
  display: none;
}
</style>
