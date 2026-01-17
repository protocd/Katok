# Схема базы данных
## Веб-приложение "Катки Москвы"

---

## ER-диаграмма (текстовое описание)

```
┌─────────────────┐
│     users       │
├─────────────────┤
│ id (PK)         │
│ email (UNIQUE)   │
│ password_hash   │
│ name            │
│ role            │
│ created_at      │
│ last_login       │
│ ip_address      │
└────────┬────────┘
         │
         │ 1:N
         │
         ├─────────────────┐
         │                 │
         │                 │
    ┌────▼────┐       ┌────▼────┐
    │ reviews │       │checkins │
    ├─────────┤       ├─────────┤
    │ id (PK) │       │ id (PK) │
    │ rink_id │       │ rink_id │
    │ user_id │       │ user_id │
    │ text    │       │ latitude│
    │ rating  │       │ longitude│
    │ ice_... │       │ distance │
    │ crowd_..│       │ ip_addr  │
    │ created │       │ timestamp │
    └────┬────┘       └────┬────┘
         │                 │
         │                 │
         │ N:1             │ N:1
         │                 │
    ┌────▼─────────────────▼────┐
    │         rinks             │
    ├────────────────────────────┤
    │ id (PK)                    │
    │ name                        │
    │ address                     │
    │ district                    │
    │ latitude                    │
    │ longitude                   │
    │ phone, email, website       │
    │ working_hours               │
    │ is_paid, price              │
    │ has_equipment_rental         │
    │ has_locker_room             │
    │ has_cafe                    │
    │ has_wifi                    │
    │ has_atm                     │
    │ has_medpoint                │
    │ is_disabled_accessible      │
    │ capacity                    │
    │ lighting, coverage          │
    │ created_at, updated_at      │
    └──────────┬──────────────────┘
               │
               │ 1:N
               │
          ┌────▼──────┐
          │ schedules │
          ├───────────┤
          │ id (PK)   │
          │ rink_id   │
          │ day_of_.. │
          │ start_time│
          │ end_time  │
          │ type      │
          │ descr...  │
          │ created_by│
          └───────────┘

┌──────────────────────┐
│ suspicious_activity │
├──────────────────────┤
│ id (PK)              │
│ user_id (FK, NULL)   │
│ ip_address           │
│ activity_type        │
│ details              │
│ timestamp            │
└──────────────────────┘
```

---

## Описание таблиц

### 1. Таблица `users` (Пользователи)

**Назначение:** Хранение данных зарегистрированных пользователей

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор пользователя |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Email пользователя (для авторизации) |
| `password_hash` | VARCHAR(255) | NOT NULL | Хеш пароля (bcrypt) |
| `name` | VARCHAR(100) | NOT NULL | Имя пользователя |
| `role` | ENUM('user', 'admin') | DEFAULT 'user' | Роль пользователя |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата регистрации |
| `last_login` | TIMESTAMP | NULL | Дата последнего входа |
| `ip_address` | VARCHAR(45) | NULL | IP-адрес (для отслеживания) |

**Индексы:**
- `idx_email` на поле `email` (для быстрого поиска при авторизации)

**Связи:**
- 1:N с таблицей `reviews` (один пользователь может оставить много отзывов)
- 1:N с таблицей `checkins` (один пользователь может сделать много отметок)
- 1:N с таблицей `schedules` (один пользователь может создать много расписаний)

---

### 2. Таблица `rinks` (Катки)

**Назначение:** Хранение информации о катках Москвы из открытых данных

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор катка |
| `name` | VARCHAR(255) | NOT NULL | Название катка |
| `address` | TEXT | NULL | Адрес катка |
| `district` | VARCHAR(100) | NULL | Административный район |
| `latitude` | DECIMAL(10, 8) | NULL | Широта (для карты) |
| `longitude` | DECIMAL(11, 8) | NULL | Долгота (для карты) |
| `phone` | VARCHAR(50) | NULL | Телефон |
| `email` | VARCHAR(255) | NULL | Email |
| `website` | VARCHAR(255) | NULL | Сайт |
| `working_hours` | TEXT | NULL | График работы |
| `is_paid` | BOOLEAN | DEFAULT FALSE | Платность (true/false) |
| `price` | DECIMAL(10, 2) | NULL | Стоимость посещения |
| `has_equipment_rental` | BOOLEAN | DEFAULT FALSE | Наличие проката оборудования |
| `has_locker_room` | BOOLEAN | DEFAULT FALSE | Наличие раздевалки |
| `has_cafe` | BOOLEAN | DEFAULT FALSE | Наличие кафе |
| `has_wifi` | BOOLEAN | DEFAULT FALSE | Наличие Wi-Fi |
| `has_atm` | BOOLEAN | DEFAULT FALSE | Наличие банкомата |
| `has_medpoint` | BOOLEAN | DEFAULT FALSE | Наличие медпункта |
| `is_disabled_accessible` | BOOLEAN | DEFAULT FALSE | Приспособленность для инвалидов |
| `capacity` | INT | NULL | Вместимость |
| `lighting` | VARCHAR(50) | NULL | Освещение |
| `coverage` | VARCHAR(50) | NULL | Покрытие |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата добавления |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Дата обновления |

**Индексы:**
- `idx_name` на поле `name` (для поиска по названию)
- `idx_district` на поле `district` (для фильтрации по району)
- `idx_location` на полях `latitude, longitude` (для геопоиска)

**Связи:**
- 1:N с таблицей `reviews` (один каток может иметь много отзывов)
- 1:N с таблицей `checkins` (на одном катке может быть много отметок)
- 1:N с таблицей `schedules` (один каток может иметь много расписаний)

---

### 3. Таблица `reviews` (Отзывы)

**Назначение:** Хранение отзывов пользователей о катках

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор отзыва |
| `rink_id` | INT | NOT NULL, FOREIGN KEY -> rinks.id | ID катка |
| `user_id` | INT | NOT NULL, FOREIGN KEY -> users.id | ID пользователя |
| `text` | TEXT | NULL | Текст отзыва |
| `rating` | TINYINT | CHECK (1-5) | Общий рейтинг (1-5) |
| `ice_condition` | TINYINT | CHECK (1-5), NULL | Оценка состояния льда (1-5) |
| `crowd_level` | TINYINT | CHECK (1-5), NULL | Оценка загруженности (1-5) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата создания |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Дата обновления |

**Индексы:**
- `idx_rink_id` на поле `rink_id` (для получения отзывов катка)
- `idx_user_id` на поле `user_id` (для получения отзывов пользователя)

**Связи:**
- N:1 с таблицей `rinks` (много отзывов к одному катку)
- N:1 с таблицей `users` (много отзывов от одного пользователя)

**Ограничения:**
- ON DELETE CASCADE - при удалении катка или пользователя удаляются их отзывы

---

### 4. Таблица `checkins` (Отметки присутствия)

**Назначение:** Хранение отметок присутствия пользователей на катках (для статистики и защиты от накруток)

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор отметки |
| `rink_id` | INT | NOT NULL, FOREIGN KEY -> rinks.id | ID катка |
| `user_id` | INT | NOT NULL, FOREIGN KEY -> users.id | ID пользователя |
| `latitude` | DECIMAL(10, 8) | NULL | Широта пользователя при отметке |
| `longitude` | DECIMAL(11, 8) | NULL | Долгота пользователя при отметке |
| `distance` | DECIMAL(10, 2) | NULL | Расстояние от пользователя до катка (в метрах) |
| `ip_address` | VARCHAR(45) | NULL | IP-адрес пользователя (для защиты от накруток) |
| `timestamp` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Время отметки |

**Индексы:**
- `idx_rink_id` на поле `rink_id` (для получения отметок катка)
- `idx_user_id` на поле `user_id` (для получения отметок пользователя)
- `idx_timestamp` на поле `timestamp` (для статистики по времени)
- `idx_ip` на поле `ip_address` (для защиты от накруток)

**Связи:**
- N:1 с таблицей `rinks` (много отметок на одном катке)
- N:1 с таблицей `users` (много отметок от одного пользователя)

**Ограничения:**
- ON DELETE CASCADE - при удалении катка или пользователя удаляются их отметки

**Логика защиты от накруток:**
- Ограничение частоты: не более одной отметки в час от одного пользователя на одном катке
- Проверка геолокации: пользователь должен быть в радиусе 500 метров от катка
- Отслеживание IP: логирование подозрительной активности (более 10 отметок с одного IP в день)

---

### 5. Таблица `schedules` (Расписание)

**Назначение:** Хранение расписания работы катков и занятий секций

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор записи |
| `rink_id` | INT | NOT NULL, FOREIGN KEY -> rinks.id | ID катка |
| `day_of_week` | TINYINT | CHECK (0-6) | День недели (0=воскресенье, 1=понедельник, ...) |
| `start_time` | TIME | NULL | Время начала |
| `end_time` | TIME | NULL | Время окончания |
| `type` | ENUM('working', 'section') | DEFAULT 'working' | Тип: работа катка или занятие секции |
| `description` | TEXT | NULL | Описание (для секций) |
| `created_by` | INT | NULL, FOREIGN KEY -> users.id | ID пользователя, создавшего запись |

**Индексы:**
- `idx_rink_id` на поле `rink_id` (для получения расписания катка)

**Связи:**
- N:1 с таблицей `rinks` (много расписаний для одного катка)
- N:1 с таблицей `users` (много расписаний может создать один пользователь)

**Ограничения:**
- ON DELETE CASCADE для `rink_id` - при удалении катка удаляется его расписание
- ON DELETE SET NULL для `created_by` - при удалении пользователя расписание остается

---

### 6. Таблица `suspicious_activity` (Подозрительная активность)

**Назначение:** Логирование подозрительной активности для защиты от накруток

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор записи |
| `user_id` | INT | NULL, FOREIGN KEY -> users.id | ID пользователя (может быть NULL) |
| `ip_address` | VARCHAR(45) | NULL | IP-адрес |
| `activity_type` | VARCHAR(50) | NULL | Тип активности ('checkin', 'review', etc.) |
| `details` | TEXT | NULL | Детали активности |
| `timestamp` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Время события |

**Индексы:**
- `idx_ip` на поле `ip_address` (для поиска по IP)
- `idx_timestamp` на поле `timestamp` (для фильтрации по времени)

**Связи:**
- N:1 с таблицей `users` (много записей может быть от одного пользователя, но может быть NULL)

**Ограничения:**
- ON DELETE SET NULL для `user_id` - при удалении пользователя записи остаются для анализа

---

## Связи между таблицами (Foreign Keys)

1. **users → reviews** (1:N)
   - `reviews.user_id` → `users.id`
   - ON DELETE CASCADE

2. **users → checkins** (1:N)
   - `checkins.user_id` → `users.id`
   - ON DELETE CASCADE

3. **users → schedules** (1:N)
   - `schedules.created_by` → `users.id`
   - ON DELETE SET NULL

4. **users → suspicious_activity** (1:N)
   - `suspicious_activity.user_id` → `users.id`
   - ON DELETE SET NULL

5. **rinks → reviews** (1:N)
   - `reviews.rink_id` → `rinks.id`
   - ON DELETE CASCADE

6. **rinks → checkins** (1:N)
   - `checkins.rink_id` → `rinks.id`
   - ON DELETE CASCADE

7. **rinks → schedules** (1:N)
   - `schedules.rink_id` → `rinks.id`
   - ON DELETE CASCADE

---

## Индексы для оптимизации

### Основные индексы (созданы в database.sql):

1. **idx_rinks_name** - для поиска катков по названию
2. **idx_rinks_district** - для фильтрации по району
3. **idx_rinks_location** - составной индекс для геопоиска (latitude, longitude)
4. **idx_reviews_rink_id** - для получения отзывов катка
5. **idx_reviews_user_id** - для получения отзывов пользователя
6. **idx_checkins_rink_id** - для получения отметок катка
7. **idx_checkins_user_id** - для получения отметок пользователя
8. **idx_checkins_timestamp** - для статистики по времени
9. **idx_checkins_ip** - для защиты от накруток
10. **idx_schedules_rink_id** - для получения расписания катка

### Обоснование индексов:

- **idx_rinks_name**: Ускоряет поиск катков по названию (живой поиск)
- **idx_rinks_district**: Ускоряет фильтрацию по району (частая операция)
- **idx_rinks_location**: Ускоряет геопоиск ближайших катков (используется формула гаверсинуса)
- **idx_reviews_rink_id**: Ускоряет получение отзывов о катке (частая операция)
- **idx_checkins_timestamp**: Ускоряет статистику по времени (группировка по часам, дням)
- **idx_checkins_ip**: Ускоряет проверку подозрительной активности по IP

---

## Нормализация базы данных

База данных находится в **третьей нормальной форме (3NF)**:

1. **Первая нормальная форма (1NF)**: Все поля атомарны, нет повторяющихся групп
2. **Вторая нормальная форма (2NF)**: Все неключевые поля зависят от полного первичного ключа
3. **Третья нормальная форма (3NF)**: Нет транзитивных зависимостей

**Преимущества нормализации:**
- Устранение избыточности данных
- Упрощение поддержки и обновления
- Снижение риска аномалий при вставке, обновлении и удалении

---

## Типы данных и их обоснование

- **INT** для ID - достаточный диапазон для количества записей
- **VARCHAR(255)** для email, name - стандартная длина для текстовых полей
- **VARCHAR(255)** для password_hash - bcrypt хеш всегда 60 символов, но оставляем запас
- **DECIMAL(10, 8)** для latitude - точность до 1.1 мм (достаточно для карт)
- **DECIMAL(11, 8)** для longitude - точность до 1.1 мм
- **TEXT** для address, description - переменная длина
- **BOOLEAN** для флагов инфраструктуры - компактное хранение
- **TIMESTAMP** для дат - автоматическое управление временем
- **ENUM** для role, type - ограничение значений на уровне БД

---

## Стратегия резервного копирования

Рекомендуется:
- Ежедневное резервное копирование базы данных
- Хранение резервных копий на отдельном сервере
- Тестирование восстановления из резервных копий

---

## Масштабируемость

При росте данных можно:
- Добавить партиционирование таблицы `checkins` по датам
- Использовать репликацию для чтения
- Добавить кэширование часто запрашиваемых данных
