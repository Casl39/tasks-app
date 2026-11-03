# Tasks API

REST API задач на Laravel 13 с кэшированием в Redis. Сервисы работают в отдельных Docker-контейнерах: PHP-FPM (`app`), nginx, MySQL 8 (`db`), Redis.

## Требования

- Docker
- Docker Compose

## Запуск

1. Склонировать репозиторий:
   ```bash
   git clone git@github.com:Casl39/tasks-app.git
   cd tasks-app
   ```
2. Создать файл окружения:
   ```bash
   cp .env.example .env
   ```
3. Поднять контейнеры:
   ```bash
   docker compose up -d --build
   ```
4. Установить зависимости и подготовить приложение:
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   ```
5. Создать тестовые задачи:
   ```bash
   docker compose exec app php artisan tinker
   ```
   В tinker выполнить:
   ```php
   App\Models\Task::create(['title'=>'Todo task','status'=>'todo','deadline'=>now()->addDays(3)]);
   App\Models\Task::create(['title'=>'Progress task','status'=>'in_progress','deadline'=>now()->addHours(5)]);
   App\Models\Task::create(['title'=>'Done task','status'=>'done','deadline'=>now()->subDay()]);
   ```
   Затем `exit`.

Приложение доступно по адресу: <http://localhost:8080>

Если возникают ошибки прав на `storage/` или `bootstrap/cache`:
```bash
chmod -R 775 storage bootstrap/cache
```
В `docker-compose.yml` для сервиса `app` указано `user: "1000:1000"`. Если ваш id пользователя другой (`id -u`, `id -g`), поменяйте значения.

## API

Все запросы с заголовком `Accept: application/json`.

### GET /api/tasks/{id}

Возвращает задачу с вычисленным полем `deadline_status` и признаком `cached`.

```bash
curl -H "Accept: application/json" http://localhost:8080/api/tasks/1
```

Ответ 200:
```json
{
  "id": 1,
  "title": "Todo task",
  "description": null,
  "status": "todo",
  "deadline": "2026-11-06T09:54:25.000000Z",
  "created_at": "2026-11-03T09:54:25.000000Z",
  "updated_at": "2026-11-03T09:54:25.000000Z",
  "deadline_status": "on_track",
  "cached": false
}
```

Коды: `200`, `404` (задача не найдена).

### PATCH /api/tasks/{id}/status

Меняет статус задачи и сбрасывает её кэш.

```bash
curl -X PATCH -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"status":"in_progress"}' http://localhost:8080/api/tasks/1/status
```

Ответ 200: задача в том же формате, что и в GET (с новым статусом, без поля `cached`).

Коды: `200`, `404` (задача не найдена), `422` (неверное значение статуса или недопустимый переход).

## Правила

### Переходы статусов

| Из            | Разрешено в            |
|---------------|------------------------|
| `todo`        | `in_progress`          |
| `in_progress` | `todo`, `done`         |
| `done`        | `in_progress`          |

Все остальные переходы, включая переход в тот же статус, возвращают `422`. Статус `status` хранится как enum в миграции (`todo`, `in_progress`, `done`).

### deadline_status

Вычисляется при каждом формировании ответа (не хранится в БД):

- `overdue`: дедлайн уже в прошлом;
- `due_soon`: до дедлайна меньше 24 часов;
- `on_track`: иначе.

Для задач в статусе `done` всегда возвращается `on_track`. Время хранится и отдаётся в UTC.

## Кэш

- хранилище: Redis;
- ключ: `task:{id}` (Laravel добавляет префикс, ключ лежит в базе Redis №1);
- TTL: 60 секунд;
- в ответе GET поле `cached`: `false` при чтении из БД, `true` при чтении из кэша;
- кэш задачи сбрасывается после успешного PATCH.

Из-за кэша `deadline_status` может быть неактуален до 60 секунд.

Посмотреть ключи и время жизни:
```bash
docker compose exec redis redis-cli -n 1 KEYS '*'
docker compose exec redis redis-cli -n 1 TTL "<имя_ключа>"
```

## Postman

1. Postman → Import → файл `postman/tasks-app.postman_collection.json`.
2. Переменная `base_url` равна `http://localhost:8080`.
3. Перед прогоном должны быть задачи: id 1 (`todo`), id 2 (`in_progress`), id 3 (`done`), кэш пустой:
   ```bash
   docker compose exec app php artisan migrate:fresh
   # затем создать три задачи, как в разделе «Запуск», шаг 5
   docker compose exec app php artisan cache:clear
   ```
4. Запускать запросы по порядку (Run collection): они меняют данные.

## Допущения

- `deadline_status` трактуется как вычисляемое поле; enum в миграции задан для колонки `status`.
- Пароли в `docker-compose.yml` и `.env.example` учебные.
- `APP_DEBUG=true` подходит только для локальной работы, в продакшене должен быть `false`.
