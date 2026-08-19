# WorkFlow CRM

CRM-система для управления сотрудниками, задачами и отделами.
Реализована по техническому заданию: SPA на Vue 3 + TypeScript и REST API на Laravel 12 с авторизацией по JWT.

## Стек

**Frontend:** Vue 3 (Composition API), TypeScript, Vite, Pinia, Vue Router, Axios, Quasar 2 + Material Icons, Chart.js, Vue I18n, VueUse
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

Интерфейс собран на **Quasar 2** (MIT), подключённом как плагин Vite (`@quasar/vite-plugin`) — а не через Quasar CLI, чтобы сохранить обычную структуру Vite-проекта, свои порты и прокси. Плагин сам подставляет импорты компонентов и директив, поэтому регистрировать их вручную не нужно, а в сборку попадает только использованное: `QTable`, `QSelect`, `QMenu`, `QPopupProxy` выезжают отдельными чанками.

Каркас — `QLayout` + `QDrawer` в mini-режиме (сворачивание сайдбара и мобильный оверлей работают средствами Quasar) и `QHeader`. Фирменная палитра задаётся в `src/styles/quasar-variables.sass`, тёмная тема — плагином `Dark`, синхронизированным со стором `ui`.

Уведомления и подтверждения — плагины `Notify` и `Dialog`. Подписи внутри компонентов Quasar берутся из его языковых пакетов (`ru`, `en-US`, `kk`) и переключаются вместе с языком интерфейса. Графики — Chart.js через тонкую обёртку `BaseChart.vue`.

Валидация форм — собственный композабл `useValidation` (~60 строк): в проекте четыре типа правил, отдельная библиотека под них избыточна.


## REST API

Все защищённые маршруты требуют заголовок `Authorization: Bearer <token>`.

| Метод | Маршрут | Описание |
| --- | --- | --- |
| POST | `/api/login` | Авторизация, выдача JWT |
| POST | `/api/refresh` | Обновление токена (работает и с истёкшим, в пределах `JWT_REFRESH_TTL`) |
| POST | `/api/logout` | Выход, инвалидация токена |
| GET | `/api/me` | Текущий пользователь |
| PUT | `/api/profile` | Профиль, смена пароля, тема и язык |
| GET | `/api/dashboard` | Агрегаты для дашборда |
| GET/POST/PUT/DELETE | `/api/employees` | CRUD сотрудников (поиск, фильтры, сортировка, пагинация) |
| GET/POST/PUT/DELETE | `/api/tasks` | CRUD задач; `?board=1` — выборка для канбана |
| PATCH | `/api/tasks/{id}/move` | Перенос карточки между колонками |
| GET/POST/PUT/DELETE | `/api/departments` | CRUD отделов |
| GET | `/api/activity-logs` | Журнал активности с фильтрами |

### Роли

- `admin`, `manager` — полный доступ, включая создание/редактирование/удаление сотрудников и отделов и просмотр журнала активности;
- `employee` — просмотр справочников и работа с задачами.

Попытка открыть закрытый раздел приводит на страницу 403 (роутер), а запрос к API — к ответу `403`.

## Страницы

Login · Dashboard · Сотрудники · Создание сотрудника · Профиль сотрудника · Задачи (Kanban) · Создание задачи · Отделы · Журнал активности · Настройки · Профиль пользователя · 404 · 403

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
