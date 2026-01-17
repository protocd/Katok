# Финальная схема базы данных
## Веб-приложение "Катки Москвы"
## Соответствует всем нормальным формам и правилам Бойса-Кодда

---

## Концептуальная модель

**Основная идея:** Пользователь и каток связаны через сущность **VISIT (Посещение)**. 
К посещению привязываются отзыв и отметка присутствия. Расписание - это фотография, загружаемая пользователем.

---

## ER-диаграмма (текстовое представление)

```
┌─────────────┐
│    USERS    │
│  (Пользователи) │
├─────────────┤
│ id (PK)     │
│ email       │
│ password... │
│ name        │
│ role        │
│ ...         │
└──────┬──────┘
       │
       │ 1
       │
       │
  ┌────▼────────────┐
  │     VISITS      │ ◄─── Основная связь
  │   (Посещения)   │      пользователь ↔ каток
  ├─────────────────┤
  │ id (PK)         │
  │ user_id (FK)    │
  │ rink_id (FK)    │
  │ visit_date      │
  │ created_at      │
  └────┬──────┬─────┘
       │      │
       │ 1    │ 1
       │      │
       │      │
  ┌────▼──┐ ┌─▼──────┐
  │REVIEWS│ │CHECKINS│
  │(Отзывы)│ │(Отметки)│
  ├───────┤ ├────────┤
  │id (PK)│ │id (PK) │
  │visit_ │ │visit_  │
  │  id   │ │  id    │
  │text   │ │lat/lon │
  │rating │ │distance│
  │...    │ │...     │
  └───────┘ └────────┘

┌─────────────┐
│    RINKS    │
│   (Катки)   │
├─────────────┤
│ id (PK)     │
│ name        │
│ address     │
│ ...         │
└──────┬──────┘
       │
       │ 1
       │
  ┌────▼──────────────┐
  │ SCHEDULE_PHOTOS    │
  │ (Фото расписания)  │
  ├────────────────────┤
  │ id (PK)            │
  │ rink_id (FK)       │
  │ visit_id (FK, NULL)│
  │ photo_path         │
  │ uploaded_by (FK)   │
  │ created_at         │
  └────────────────────┘

┌──────────────────────┐
│ SUSPICIOUS_ACTIVITY   │
│ (Подозрительная       │
│   активность)         │
└──────────────────────┘
```

---

## Детальное описание таблиц

### 1. Таблица `users` (Пользователи)

**Назначение:** Хранение данных зарегистрированных пользователей

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Email (уникальный) |
| `password_hash` | VARCHAR(255) | NOT NULL | Хеш пароля (bcrypt) |
| `name` | VARCHAR(100) | NOT NULL | Имя пользователя |
| `role` | ENUM('user', 'admin') | DEFAULT 'user' | Роль |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата регистрации |
| `last_login` | TIMESTAMP | NULL | Последний вход |
| `ip_address` | VARCHAR(45) | NULL | IP-адрес |

**Индексы:**
- PRIMARY KEY: `id`
- UNIQUE: `email`
- INDEX: `idx_email` на `email` (для авторизации)

**Нормализация:** 3NF ✓
- Все поля атомарны
- Нет функциональных зависимостей от части ключа
- Нет транзитивных зависимостей

---

### 2. Таблица `rinks` (Катки)

**Назначение:** Хранение информации о катках из открытых данных

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор |
| `name` | VARCHAR(255) | NOT NULL | Название катка |
| `address` | TEXT | NULL | Адрес |
| `district` | VARCHAR(100) | NULL | Район Москвы |
| `latitude` | DECIMAL(10, 8) | NULL | Широта |
| `longitude` | DECIMAL(11, 8) | NULL | Долгота |
| `phone` | VARCHAR(50) | NULL | Телефон |
| `email` | VARCHAR(255) | NULL | Email |
| `website` | VARCHAR(255) | NULL | Сайт |
| `working_hours` | TEXT | NULL | График работы (текст) |
| `is_paid` | BOOLEAN | DEFAULT FALSE | Платность |
| `price` | DECIMAL(10, 2) | NULL | Цена |
| `has_equipment_rental` | BOOLEAN | DEFAULT FALSE | Прокат оборудования |
| `has_locker_room` | BOOLEAN | DEFAULT FALSE | Раздевалка |
| `has_cafe` | BOOLEAN | DEFAULT FALSE | Кафе |
| `has_wifi` | BOOLEAN | DEFAULT FALSE | Wi-Fi |
| `has_atm` | BOOLEAN | DEFAULT FALSE | Банкомат |
| `has_medpoint` | BOOLEAN | DEFAULT FALSE | Медпункт |
| `is_disabled_accessible` | BOOLEAN | DEFAULT FALSE | Для инвалидов |
| `capacity` | INT UNSIGNED | NULL | Вместимость |
| `lighting` | VARCHAR(50) | NULL | Освещение |
| `coverage` | VARCHAR(50) | NULL | Покрытие |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата добавления |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Дата обновления |

**Индексы:**
- PRIMARY KEY: `id`
- INDEX: `idx_name` на `name` (для поиска)
- INDEX: `idx_district` на `district` (для фильтрации)
- INDEX: `idx_location` на `(latitude, longitude)` (для геопоиска)

**Нормализация:** 3NF ✓
- Все поля атомарны
- Нет повторяющихся групп
- Нет транзитивных зависимостей

---

### 3. Таблица `visits` (Посещения) ⭐ КЛЮЧЕВАЯ СУЩНОСТЬ

**Назначение:** Связь между пользователем и катком. Одно посещение = один визит пользователя на каток.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор посещения |
| `user_id` | INT UNSIGNED | NOT NULL, FOREIGN KEY -> users.id | ID пользователя |
| `rink_id` | INT UNSIGNED | NOT NULL, FOREIGN KEY -> rinks.id | ID катка |
| `visit_date` | DATE | NOT NULL | Дата посещения |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Время создания записи |

**Индексы:**
- PRIMARY KEY: `id`
- UNIQUE: `(user_id, rink_id, visit_date)` - один пользователь не может иметь два посещения одного катка в один день
- INDEX: `idx_user_id` на `user_id` (для получения посещений пользователя)
- INDEX: `idx_rink_id` на `rink_id` (для получения посещений катка)
- INDEX: `idx_visit_date` на `visit_date` (для статистики по датам)

**Связи:**
- N:1 с `users` (много посещений от одного пользователя)
- N:1 с `rinks` (много посещений на одном катке)
- 1:1 с `reviews` (одно посещение может иметь один отзыв)
- 1:N с `checkins` (одно посещение может иметь несколько отметок в течение дня)

**Нормализация:** BCNF ✓ (Boyce-Codd Normal Form)
- Все детерминанты являются потенциальными ключами
- UNIQUE constraint на (user_id, rink_id, visit_date) предотвращает дубликаты

**Обоснование:**
- Посещение - это основная сущность, которая связывает пользователя и каток
- К посещению привязываются отзыв и отметки
- Один пользователь может посетить каток только один раз в день (логически)

---

### 4. Таблица `reviews` (Отзывы)

**Назначение:** Отзывы пользователей о посещении катка

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор отзыва |
| `visit_id` | INT UNSIGNED | NOT NULL, UNIQUE, FOREIGN KEY -> visits.id | ID посещения (один отзыв на посещение) |
| `text` | TEXT | NOT NULL | Текст отзыва |
| `rating` | TINYINT UNSIGNED | NOT NULL, CHECK (1-5) | Общий рейтинг (1-5) |
| `ice_condition` | TINYINT UNSIGNED | NULL, CHECK (1-5) | Оценка состояния льда (1-5) |
| `crowd_level` | TINYINT UNSIGNED | NULL, CHECK (1-5) | Оценка загруженности (1-5) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата создания |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Дата обновления |

**Индексы:**
- PRIMARY KEY: `id`
- UNIQUE: `visit_id` (один отзыв на одно посещение)
- INDEX: `idx_visit_id` на `visit_id` (для связи с посещением)

**Связи:**
- N:1 с `visits` (много отзывов к разным посещениям, но один отзыв на посещение)

**Нормализация:** 3NF ✓
- Все поля зависят от первичного ключа
- `visit_id` - внешний ключ, связь с посещением

**Обоснование:**
- Отзыв привязан к конкретному посещению, а не напрямую к пользователю и катку
- Один отзыв на одно посещение (логически правильно)
- Через `visit_id` можно получить и пользователя, и каток

---

### 5. Таблица `checkins` (Отметки присутствия)

**Назначение:** Отметки присутствия пользователя на катке в конкретный момент времени

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор отметки |
| `visit_id` | INT UNSIGNED | NOT NULL, FOREIGN KEY -> visits.id | ID посещения |
| `latitude` | DECIMAL(10, 8) | NOT NULL | Широта пользователя |
| `longitude` | DECIMAL(11, 8) | NOT NULL | Долгота пользователя |
| `distance` | DECIMAL(10, 2) | NULL | Расстояние до катка (метры) |
| `ip_address` | VARCHAR(45) | NULL | IP-адрес (для защиты от накруток) |
| `timestamp` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Время отметки |

**Индексы:**
- PRIMARY KEY: `id`
- INDEX: `idx_visit_id` на `visit_id` (для получения отметок посещения)
- INDEX: `idx_timestamp` на `timestamp` (для статистики по времени)
- INDEX: `idx_ip` на `ip_address` (для защиты от накруток)

**Связи:**
- N:1 с `visits` (много отметок в течение одного посещения)

**Нормализация:** 3NF ✓

**Обоснование:**
- Отметка привязана к посещению
- В течение одного посещения может быть несколько отметок (например, пришел, ушел)
- Защита от накруток: проверка времени между отметками, геолокация, IP

---

### 6. Таблица `schedule_photos` (Фотографии расписания)

**Назначение:** Хранение фотографий расписания, загруженных пользователями

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор |
| `rink_id` | INT UNSIGNED | NOT NULL, FOREIGN KEY -> rinks.id | ID катка |
| `visit_id` | INT UNSIGNED | NULL, FOREIGN KEY -> visits.id | ID посещения (если загружено во время посещения) |
| `photo_path` | VARCHAR(500) | NOT NULL | Путь к файлу фотографии |
| `photo_url` | VARCHAR(500) | NULL | URL фотографии (если хранится на CDN) |
| `uploaded_by` | INT UNSIGNED | NOT NULL, FOREIGN KEY -> users.id | ID пользователя, загрузившего фото |
| `is_verified` | BOOLEAN | DEFAULT FALSE | Проверено администратором |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата загрузки |

**Индексы:**
- PRIMARY KEY: `id`
- INDEX: `idx_rink_id` на `rink_id` (для получения фото катка)
- INDEX: `idx_visit_id` на `visit_id` (для получения фото посещения)
- INDEX: `idx_uploaded_by` на `uploaded_by` (для получения фото пользователя)
- INDEX: `idx_is_verified` на `is_verified` (для фильтрации проверенных)

**Связи:**
- N:1 с `rinks` (много фото одного катка)
- N:1 с `visits` (может быть привязано к посещению, опционально)
- N:1 с `users` (много фото от одного пользователя)

**Нормализация:** 3NF ✓

**Обоснование:**
- Фотография может быть привязана к катку (общее расписание)
- Или к конкретному посещению (расписание на момент посещения)
- `visit_id` может быть NULL, если фото загружено не во время посещения

---

### 7. Таблица `suspicious_activity` (Подозрительная активность)

**Назначение:** Логирование подозрительной активности для защиты от накруток

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор |
| `user_id` | INT UNSIGNED | NULL, FOREIGN KEY -> users.id | ID пользователя (может быть NULL) |
| `ip_address` | VARCHAR(45) | NOT NULL | IP-адрес |
| `activity_type` | ENUM('checkin', 'review', 'visit', 'photo') | NOT NULL | Тип активности |
| `details` | TEXT | NULL | Детали активности |
| `timestamp` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Время события |

**Индексы:**
- PRIMARY KEY: `id`
- INDEX: `idx_user_id` на `user_id` (для поиска по пользователю)
- INDEX: `idx_ip` на `ip_address` (для поиска по IP)
- INDEX: `idx_timestamp` на `timestamp` (для фильтрации по времени)
- INDEX: `idx_activity_type` на `activity_type` (для фильтрации по типу)

**Связи:**
- N:1 с `users` (много записей от одного пользователя, опционально)

**Нормализация:** 3NF ✓

---

## Связи между таблицами (Foreign Keys)

### Основные связи:

1. **users → visits** (1:N)
   - `visits.user_id` → `users.id`
   - ON DELETE CASCADE (при удалении пользователя удаляются его посещения)

2. **rinks → visits** (1:N)
   - `visits.rink_id` → `rinks.id`
   - ON DELETE CASCADE (при удалении катка удаляются посещения)

3. **visits → reviews** (1:1)
   - `reviews.visit_id` → `visits.id`
   - ON DELETE CASCADE
   - UNIQUE constraint (один отзыв на посещение)

4. **visits → checkins** (1:N)
   - `checkins.visit_id` → `visits.id`
   - ON DELETE CASCADE (несколько отметок в течение посещения)

5. **rinks → schedule_photos** (1:N)
   - `schedule_photos.rink_id` → `rinks.id`
   - ON DELETE CASCADE

6. **visits → schedule_photos** (1:N, опционально)
   - `schedule_photos.visit_id` → `visits.id`
   - ON DELETE SET NULL (если посещение удалено, фото остается)

7. **users → schedule_photos** (1:N)
   - `schedule_photos.uploaded_by` → `users.id`
   - ON DELETE CASCADE

8. **users → suspicious_activity** (1:N, опционально)
   - `suspicious_activity.user_id` → `users.id`
   - ON DELETE SET NULL

---

## Проверка нормализации

### Первая нормальная форма (1NF) ✓
- Все поля атомарны (нет составных значений)
- Нет повторяющихся групп
- Каждая строка уникальна

### Вторая нормальная форма (2NF) ✓
- Все таблицы в 1NF
- Все неключевые поля полностью зависят от первичного ключа
- Нет частичных зависимостей

### Третья нормальная форма (3NF) ✓
- Все таблицы в 2NF
- Нет транзитивных зависимостей
- Все неключевые поля зависят только от первичного ключа

### Нормальная форма Бойса-Кодда (BCNF) ✓
- Все таблицы в 3NF
- Все детерминанты являются потенциальными ключами
- В таблице `visits`: UNIQUE (user_id, rink_id, visit_date) - составной ключ

**Пример проверки BCNF:**
- В `visits`: детерминант (user_id, rink_id, visit_date) → id (PK) ✓
- В `reviews`: детерминант visit_id → id (PK) ✓
- Все детерминанты являются ключами ✓

---

## Преимущества новой структуры

1. **Правильная нормализация:**
   - Нет избыточности данных
   - Нет аномалий при вставке, обновлении, удалении
   - Соответствует BCNF

2. **Логическая связность:**
   - Посещение - центральная сущность
   - Отзыв и отметка привязаны к конкретному посещению
   - Один отзыв на одно посещение (логически правильно)

3. **Гибкость:**
   - Можно отслеживать историю посещений
   - Можно анализировать поведение пользователей
   - Можно строить статистику по посещениям

4. **Защита от накруток:**
   - UNIQUE constraint на (user_id, rink_id, visit_date) предотвращает дубликаты
   - Отметки привязаны к посещению
   - Логирование подозрительной активности

---

## Индексы для оптимизации

### Основные индексы:

1. **users.email** (UNIQUE) - для быстрой авторизации
2. **rinks.name** - для поиска по названию
3. **rinks.district** - для фильтрации по району
4. **rinks(latitude, longitude)** - для геопоиска
5. **visits(user_id, rink_id, visit_date)** (UNIQUE) - предотвращение дубликатов
6. **visits.visit_date** - для статистики по датам
7. **reviews.visit_id** (UNIQUE) - один отзыв на посещение
8. **checkins.timestamp** - для статистики по времени
9. **checkins.ip_address** - для защиты от накруток
10. **schedule_photos.rink_id** - для получения фото катка

---

## Итоговая структура

**Основные сущности:**
1. `users` - пользователи
2. `rinks` - катки
3. `visits` - посещения (связь пользователь ↔ каток) ⭐
4. `reviews` - отзывы (привязаны к посещению)
5. `checkins` - отметки (привязаны к посещению)
6. `schedule_photos` - фотографии расписания
7. `suspicious_activity` - логирование подозрительной активности

**Связи:**
- Пользователь → Посещение → Каток (через visits)
- Посещение → Отзыв (1:1)
- Посещение → Отметки (1:N)
- Каток → Фотографии расписания (1:N)

**Нормализация:** BCNF ✓
