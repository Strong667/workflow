# WorkFlow CRM

CRM-система для управления сотрудниками, задачами и отделами.
Реализована по техническому заданию: SPA на Vue 3 + TypeScript и REST API на Laravel 12 с авторизацией по JWT.

## Стек

**Frontend:** Vue 3 (Composition API), TypeScript, Vite, Pinia, Vue Router, Axios, PrimeVue 4 + PrimeIcons, Chart.js, Vue I18n, VueUse
**Backend:** Laravel 12, PHP 8.4, tymon/jwt-auth, MySQL 8 (в dev-режиме — SQLite)

## Структура

```
e-commerce/
├── backend/     # Laravel 12: REST API, JWT, миграции, сидеры
└── frontend/    # Vue 3 + TS SPA
```

## Быстрый старт

### 1. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan storage:link
php artisan migrate --seed
php artisan serve --port=8088
```

API поднимется на `http://127.0.0.1:8088`.

### 2. Frontend

```bash
cd frontend
npm install
npm run dev
```

Интерфейс — `http://localhost:5188`.

Порты по умолчанию — **8088** (API) и **5188** (фронтенд): на машине разработчика рядом работают другие проекты, поэтому дефолтные 8000 и 5173 не используются. Переопределяются в `frontend/.env`:

```
VITE_PORT=5188
VITE_API_PROXY=http://127.0.0.1:8088
```

Dev-сервер проксирует `/api` на бэкенд, поэтому CORS в разработке не требуется. У Vite включён `strictPort`: при занятом порте он остановится с ошибкой, а не займёт соседний порт чужого проекта.

### Справочник и аккаунты

Это разные сущности: `employees` — карточки сотрудников, `users` — аккаунты со входом в систему. Не каждому в справочнике нужен вход (подрядчики, те, кто CRM не пользуется), и не каждый аккаунт — человек из справочника (служебный админ). Связывает их поле `employees.user_id`.

Из карточки сотрудника доступны два действия: **выдать доступ** — заводит аккаунт на email из карточки, и **привязать аккаунт** — цепляет уже существующий, у которого ещё нет карточки. Отвязка снимает связь, но аккаунт не удаляет: им управляют в разделе «Пользователи».

Пока карточка не связана с аккаунтом, вошедший сотрудник не увидит ни одной задачи — система просто не знает, какая из карточек это он. Связь даёт:

- канбан показывает только его задачи, переносить и править он может тоже только их;
- дашборд считает его задачи, а не компанию целиком, и не показывает журнал активности;
- создание и удаление задач остаются за админом и менеджером.

### Данные

Свежая установка приходит без демо-записей: сидер создаёт только три аккаунта и шесть отделов, а сотрудники и задачи заводятся через интерфейс. Наполнить систему сгенерированным набором (48 сотрудников, 70 задач) можно отдельной командой:

```bash
php artisan db:seed --class=DemoSeeder
```

### Демо-доступы

| Роль | Email | Пароль |
| --- | --- | --- |
| Администратор | admin@workflow.test | password |
| Менеджер | manager@workflow.test | password |
| Сотрудник | user@workflow.test | password |

## База данных

По ТЗ рабочая СУБД — **MySQL 8**. Чтобы её подключить, создайте базу и пользователя:

```sql
CREATE DATABASE workflow_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'workflow'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON workflow_crm.* TO 'workflow'@'localhost';
FLUSH PRIVILEGES;
```

Затем в `backend/.env` укажите `DB_CONNECTION=mysql` и выполните `php artisan migrate:fresh --seed`.

Для локального запуска без MySQL в репозитории оставлен `DB_CONNECTION=sqlite` — схема одинакова, специфичного для SQLite SQL в коде нет.

### Схема

| Таблица | Поля |
| --- | --- |
| `users` | id, name, email, password, role, avatar, language, theme |
| `departments` | id, name, description |
| `employees` | id, first_name, last_name, email, phone, department_id, position, hire_date, avatar |
| `tasks` | id, title, description, employee_id, status, priority, deadline, position |
| `activity_logs` | id, user_id, action, entity, entity_id, description, created_at |

## UI-слой

Интерфейс собран на **PrimeVue 4.5.5** (MIT) с пресетом Aura и фирменным индиго. Тёмная тема включается классом `.dark` на `<html>` — его ставит стор `ui`, PrimeVue настроен на `darkModeSelector: '.dark'`.

Всё, что знает о библиотеке, лежит в `src/ui/` — экраны обращаются к PrimeVue только через этот каталог:

| Файл | Отвечает за |
| --- | --- |
| `theme.ts` | пресет темы, фирменные цвета, конфигурация плагина |
| `global-components.ts` | глобальная регистрация лёгких компонентов |
| `lazy-components.ts` | реэкспорт тяжёлых компонентов для локальных импортов в экранах |
| `locales.ts` | подписи внутри компонентов библиотеки для ru/en/kk |
| `tokens.ts` | чтение значений токенов темы для Chart.js |
| `feedback.ts` | `useNotify()` и `useConfirmDelete()` в терминах приложения |

Тяжёлые компоненты (`DataTable`, `Chart`, `DatePicker`, `Dialog`, `Paginator`, `Password`, `Timeline`) не регистрируются глобально, а импортируются в своих экранах через `lazy-components.ts` и уезжают в чанки соответствующих страниц: экран логина не тянет код таблиц и графиков.

Валидация форм — собственный композабл `useValidation` (~60 строк): в проекте четыре типа правил, отдельная библиотека под них избыточна.

### Фотографии

Аватары загружаются файлом, а не ссылкой: `AvatarUpload.vue` отправляет изображение на `POST /api/uploads/avatar`, получает URL и подставляет его в поле `avatar` формы — запись сохраняется обычным JSON вместе с остальными полями.

Перед отправкой открывается окно кадрирования: картинку двигают мышью и подбирают масштаб колесом или ползунком, в аватар попадает область внутри круга. На канвасе выбранный участок превращается в квадрат 512×512, поэтому непрямоугольные фотографии не сжимаются. Сервер на всякий случай повторяет обрезку по центру для загрузок через API.

Файлы лежат в `storage/app/public/avatars` и раздаются через симлинк `public/storage` (создаётся командой `php artisan storage:link`). Ограничения — JPG, PNG, WEBP, GIF до 2 МБ и не больше 4000×4000; они проверяются и на клиенте, и на сервере. Прежний файл удаляется при замене фотографии и при удалении сотрудника, внешние URL при этом не трогаются — их по-прежнему можно задать через API.

### Переход на PrimeVue 5

Проект к нему готов: с `primevue@5` проходят и `vue-tsc`, и сборка, без правок в коде. Мешает только лицензия — с пятой версии PrimeVue распространяется по PrimeUI License и требует ключ даже на бесплатном Community-тарифе. Порядок обновления, регистрация ключа и список учтённых отличий — в [docs/PRIMEVUE-5.md](docs/PRIMEVUE-5.md).

```bash
npm run upgrade:primevue5     # и обратно: npm run downgrade:primevue4
```


## REST API

Все защищённые маршруты требуют заголовок `Authorization: Bearer <token>`.

| Метод | Маршрут | Описание |
| --- | --- | --- |
| POST | `/api/login` | Авторизация, выдача JWT |
| POST | `/api/refresh` | Обновление токена (работает и с истёкшим, в пределах `JWT_REFRESH_TTL`) |
| POST | `/api/logout` | Выход, инвалидация токена |
| GET | `/api/me` | Текущий пользователь |
| PUT | `/api/profile` | Профиль, смена пароля, тема и язык |
| POST | `/api/uploads/avatar` | Загрузка фотографии, возвращает URL для поля `avatar` |
| GET | `/api/dashboard` | Агрегаты для дашборда |
| GET/POST/PUT/DELETE | `/api/employees` | CRUD сотрудников (поиск, фильтры, сортировка, пагинация) |
| GET/POST/PUT/DELETE | `/api/tasks` | CRUD задач; `?board=1` — выборка для канбана |
| PATCH | `/api/tasks/{id}/move` | Перенос карточки между колонками |
| GET/POST/PUT/DELETE | `/api/departments` | CRUD отделов |
| GET/POST/PUT/DELETE | `/api/users` | CRUD аккаунтов (только admin и manager) |
| POST | `/api/employees/{id}/account` | Выдать сотруднику доступ: создаёт аккаунт на его email |
| PUT | `/api/employees/{id}/account` | Привязать к карточке существующий аккаунт |
| DELETE | `/api/employees/{id}/account` | Отвязать аккаунт от карточки |
| GET | `/api/activity-logs` | Журнал активности с фильтрами |

### Роли

- `admin` — полный доступ, включая управление любыми аккаунтами;
- `manager` — то же, кроме администраторских аккаунтов: не может ни выдать роль `admin`, ни изменить или удалить чужого администратора;
- `employee` — справочники на чтение и работа **только со своими задачами**.

Система не остаётся без администратора: последнего нельзя удалить или понизить в роли, а собственный аккаунт не удаляет никто.

Попытка открыть закрытый раздел приводит на страницу 403 (роутер), а запрос к API — к ответу `403`.

## Страницы

Login · Dashboard · Сотрудники · Создание сотрудника · Профиль сотрудника · Задачи (Kanban) · Создание задачи · Отделы · Пользователи · Журнал активности · Настройки · Профиль пользователя · 404 · 403

## Реализовано по чек-листу ТЗ

- Адаптивная вёрстка (сайдбар сворачивается, таблицы и доска скроллятся)
- JWT: login / logout / refresh с автоматическим повтором запроса после обновления токена
- CRUD сотрудников, задач и отделов
- Kanban с drag & drop и оптимистичным обновлением
- Поиск с debounce, фильтры, сортировка, пагинация
- Pinia: `auth`, `ui`, `employees`, `tasks`, `departments`
- TypeScript строгого режима, `vue-tsc` проходит без ошибок
- Lazy Loading маршрутов и файлов локализации
- Code Splitting: отдельные вендорные чанки (`vue`, `element`, `i18n`, `api`)
- Skeleton-состояния таблиц и карточек
- Тёмная тема и три языка (ru / en / kk) с сохранением на сервере
- Графики дашборда на Chart.js

## Команды

```bash
# frontend
npm run dev        # дев-сервер на 5188
npm run type-check # проверка типов
npm run build      # прод-сборка (vue-tsc + vite build)
npm run preview    # предпросмотр сборки на 5189

# backend
php artisan serve --port=8088
php artisan migrate:fresh --seed
```

## Деплой

Фронтенд собирается в статику (`npm run build` → `frontend/dist`) и разворачивается на Vercel/Netlify;
переменная `VITE_API_URL` должна указывать на публичный адрес API.
Бэкенд — Railway/любой VPS с PHP 8.2+ и MySQL 8; в `.env` задаются `APP_URL`, доступы к БД и `JWT_SECRET`.

## Git Flow

```
main            # стабильные релизы
develop         # интеграционная ветка
feature/login
feature/dashboard
feature/employees
feature/tasks
feature/settings
```
