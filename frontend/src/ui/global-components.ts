import type { App } from 'vue'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Menu from 'primevue/menu'
import ProgressSpinner from 'primevue/progressspinner'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import ToggleSwitch from 'primevue/toggleswitch'

/**
 * Глобально регистрируем только лёгкие компоненты, которые встречаются почти везде.
 * Тяжёлые (DataTable, Chart, DatePicker, Dialog, Paginator, Password, Timeline)
 * импортируются локально в своих экранах и попадают в их чанки.
 */
export function registerComponents(app: App): void {
  const components: Record<string, unknown> = {
    Avatar,
    Badge,
    Button,
    ConfirmDialog,
    IconField,
    InputIcon,
    InputText,
    Menu,
    ProgressSpinner,
    Select,
    SelectButton,
    Skeleton,
    Tag,
    Textarea,
    Toast,
    ToggleSwitch,
  }

  for (const [name, component] of Object.entries(components)) {
    app.component(name, component as never)
  }
}
