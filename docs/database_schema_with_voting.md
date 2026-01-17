# Финальная схема базы данных с системой голосования
## Веб-приложение "Катки Москвы"
## Соответствует всем нормальным формам и правилам Бойса-Кодда

---

## Концептуальная модель

**Основная идея:** Пользователь и каток связаны через сущность **VISIT (Посещение)**. 
К посещению привязываются отзыв и отметка присутствия. Расписание - это фотография, загружаемая пользователем.

**Система голосования:** Пользователи могут голосовать за отзывы и фотографии расписания (upvote/downvote), 
как на Reddit. Сортировка по рейтингу (разница между upvotes и downvotes).

---

## ER-диаграмма (обновленная с голосованием)

```
┌─────────────┐
│    USERS    │
│(Пользователи)│
├─────────────┤
│ id (PK)     │
│ email       │
│ ...         │
└──────┬──────┘
       │
       │ 1
       │
  ┌────▼────────────┐
  │     VISITS      │ ◄─── Основная связь
  │   (Посещения)   │      пользователь ↔ каток
  ├─────────────────┤
  │ id (PK)         │
  │ user_id (FK)    │
  │ rink_id (FK)    │
  │ visit_date      │
  └────┬──────┬─────┘
       │      │
       │ 1    │ 1
       │      │
  ┌────▼──┐ ┌─▼──────┐
  │REVIEWS│ │CHECKINS│
  │(Отзывы)│ │(Отметки)│
  ├───────┤ ├────────┤
  │id (PK)│ │id (PK) │
  │visit_ │ │visit_  │
  │  id   │ │  id    │
  │text   │ │...     │
  │rating │ │        │
  │score  │ │        │
  │...    │ │        │
  └───┬───┘ └────────┘
      │
      │ 1
      │
  ┌───▼──────────────┐
  │      VOTES       │ ◄─── Голосование
  │   (Голоса)       │      за отзывы и фото
  ├──────────────────┤
  │ id (PK)          │
  │ user_id (FK)     │
  │ review_id (FK)   │
  │ photo_id (FK)    │
  │ vote_type        │
  │ (up/down)        │
  └──────────────────┘

┌─────────────┐
│    RINKS    │
│   (Катки)   │
├─────────────┤
│ id (PK)     │
│ name        │
│ ...         │
└──────┬──────┘
       │
       │ 1
       │
  ┌────▼──────────────┐
  │ SCHEDULE_PHOTOS   │
  │ (Фото расписания) │
  ├────────────────────┤
  │ id (PK)            │
  │ rink_id (FK)       │
  │ visit_id (FK)      │
  │ photo_path         │
  │ score              │ ◄─── Рейтинг (upvotes - downvotes)
  │ ...                │
  └────────────────────┘
```

---

## Обновленные таблицы

### 1. Таблица `reviews` (Отзывы) - ОБНОВЛЕНА

**Добавлено поле для рейтинга:**

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор отзыва |
| `visit_id` | INT UNSIGNED | NOT NULL, UNIQUE, FOREIGN KEY -> visits.id | ID посещения |
| `text` | TEXT | NOT NULL | Текст отзыва |
| `rating` | TINYINT UNSIGNED | NOT NULL, CHECK (1-5) | Общий рейтинг (1-5) |
| `ice_condition` | TINYINT UNSIGNED | NULL, CHECK (1-5) | Оценка состояния льда |
| `crowd_level` | TINYINT UNSIGNED | NULL, CHECK (1-5) | Оценка загруженности |
| **`score`** | **INT** | **DEFAULT 0** | **Рейтинг голосования (upvotes - downvotes)** |
| `upvotes_count` | INT UNSIGNED | DEFAULT 0 | Количество upvotes (для быстрого доступа) |
| `downvotes_count` | INT UNSIGNED | DEFAULT 0 | Количество downvotes (для быстрого доступа) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата создания |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Дата обновления |

**Индексы:**
- PRIMARY KEY: `id`
- UNIQUE: `visit_id`
- INDEX: `idx_score` на `score` (для сортировки по рейтингу)
- INDEX: `idx_created_at` на `created_at` (для сортировки по дате)

**Обоснование:**
- `score` = upvotes - downvotes (может быть отрицательным)
- `upvotes_count` и `downvotes_count` - денормализация для быстрого отображения
- Обновляются триггерами или приложением при каждом голосовании

---

### 2. Таблица `schedule_photos` (Фотографии расписания) - ОБНОВЛЕНА

**Добавлено поле для рейтинга:**

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор |
| `rink_id` | INT UNSIGNED | NOT NULL, FOREIGN KEY -> rinks.id | ID катка |
| `visit_id` | INT UNSIGNED | NULL, FOREIGN KEY -> visits.id | ID посещения |
| `photo_path` | VARCHAR(500) | NOT NULL | Путь к файлу |
| `photo_url` | VARCHAR(500) | NULL | URL фотографии |
| `uploaded_by` | INT UNSIGNED | NOT NULL, FOREIGN KEY -> users.id | ID пользователя |
| **`score`** | **INT** | **DEFAULT 0** | **Рейтинг голосования (upvotes - downvotes)** |
| `upvotes_count` | INT UNSIGNED | DEFAULT 0 | Количество upvotes |
| `downvotes_count` | INT UNSIGNED | DEFAULT 0 | Количество downvotes |
| `is_verified` | BOOLEAN | DEFAULT FALSE | Проверено администратором |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата загрузки |

**Индексы:**
- PRIMARY KEY: `id`
- INDEX: `idx_rink_id` на `rink_id`
- INDEX: `idx_score` на `score` (для сортировки по рейтингу)
- INDEX: `idx_is_verified` на `is_verified`
- INDEX: `idx_created_at` на `created_at`

---

### 3. Таблица `votes` (Голоса) ⭐ НОВАЯ

**Назначение:** Хранение голосов пользователей за отзывы и фотографии расписания

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Уникальный идентификатор голоса |
| `user_id` | INT UNSIGNED | NOT NULL, FOREIGN KEY -> users.id | ID пользователя, проголосовавшего |
| `review_id` | INT UNSIGNED | NULL, FOREIGN KEY -> reviews.id | ID отзыва (если голос за отзыв) |
| `photo_id` | INT UNSIGNED | NULL, FOREIGN KEY -> schedule_photos.id | ID фотографии (если голос за фото) |
| `vote_type` | ENUM('up', 'down') | NOT NULL | Тип голоса: upvote или downvote |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Время голосования |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Время изменения голоса |

**Ограничения:**
- CHECK: `review_id IS NOT NULL OR photo_id IS NOT NULL` (должен быть указан либо отзыв, либо фото)
- CHECK: `NOT (review_id IS NOT NULL AND photo_id IS NOT NULL)` (нельзя голосовать за оба одновременно)
- UNIQUE: `(user_id, review_id)` - один пользователь может проголосовать за отзыв только один раз
- UNIQUE: `(user_id, photo_id)` - один пользователь может проголосовать за фото только один раз

**Индексы:**
- PRIMARY KEY: `id`
- UNIQUE: `(user_id, review_id)` - предотвращение дубликатов голосов за отзыв
- UNIQUE: `(user_id, photo_id)` - предотвращение дубликатов голосов за фото
- INDEX: `idx_review_id` на `review_id` (для подсчета голосов отзыва)
- INDEX: `idx_photo_id` на `photo_id` (для подсчета голосов фото)
- INDEX: `idx_user_id` на `user_id` (для получения голосов пользователя)

**Связи:**
- N:1 с `users` (много голосов от одного пользователя)
- N:1 с `reviews` (много голосов за один отзыв)
- N:1 с `schedule_photos` (много голосов за одну фотографию)

**Нормализация:** BCNF ✓
- Все детерминанты являются ключами
- UNIQUE constraints предотвращают дубликаты

**Логика работы:**
1. Пользователь может проголосовать за отзыв или фото только один раз
2. Если пользователь уже голосовал, можно изменить голос (up → down или наоборот)
3. При изменении голоса обновляется `updated_at`
4. При удалении голоса (отзыв/фото удален) - каскадное удаление

---

## Логика системы голосования

### Алгоритм голосования:

1. **Пользователь нажимает "вверх" (upvote):**
   - Проверка: есть ли уже голос этого пользователя?
     - Если нет → создается запись с `vote_type = 'up'`
     - Если есть и `vote_type = 'down'` → обновляется на `vote_type = 'up'`
     - Если есть и `vote_type = 'up'` → удаляется голос (отмена upvote)

2. **Пользователь нажимает "вниз" (downvote):**
   - Аналогично, но с `vote_type = 'down'`

3. **Обновление счетчиков:**
   - После каждого голосования пересчитываются:
     - `upvotes_count` = COUNT(votes WHERE vote_type = 'up')
     - `downvotes_count` = COUNT(votes WHERE vote_type = 'down')
     - `score` = upvotes_count - downvotes_count

### Отображение рейтинга:

- **Положительный рейтинг:** `+17` (17 upvotes больше, чем downvotes)
- **Отрицательный рейтинг:** `-5` (5 downvotes больше, чем upvotes)
- **Нулевой рейтинг:** `0` (равное количество)

### Сортировка:

1. **По рейтингу (по умолчанию):** ORDER BY score DESC, created_at DESC
2. **По дате:** ORDER BY created_at DESC
3. **По количеству upvotes:** ORDER BY upvotes_count DESC

---

## SQL-запросы для работы с голосованием

### Получить рейтинг отзыва:
```sql
SELECT 
    r.id,
    r.text,
    r.score,
    r.upvotes_count,
    r.downvotes_count,
    (r.upvotes_count - r.downvotes_count) as display_score
FROM reviews r
WHERE r.id = ?
```

### Получить голос пользователя за отзыв:
```sql
SELECT vote_type 
FROM votes 
WHERE user_id = ? AND review_id = ?
```

### Проголосовать за отзыв (INSERT или UPDATE):
```sql
-- Если голоса нет - INSERT
INSERT INTO votes (user_id, review_id, vote_type)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE 
    vote_type = VALUES(vote_type),
    updated_at = CURRENT_TIMESTAMP;

-- Затем обновить счетчики в reviews
UPDATE reviews 
SET 
    upvotes_count = (SELECT COUNT(*) FROM votes WHERE review_id = ? AND vote_type = 'up'),
    downvotes_count = (SELECT COUNT(*) FROM votes WHERE review_id = ? AND vote_type = 'down'),
    score = upvotes_count - downvotes_count
WHERE id = ?;
```

### Получить отзывы, отсортированные по рейтингу:
```sql
SELECT 
    r.*,
    u.name as user_name,
    (r.upvotes_count - r.downvotes_count) as display_score
FROM reviews r
LEFT JOIN visits v ON r.visit_id = v.id
LEFT JOIN users u ON v.user_id = u.id
WHERE v.rink_id = ?
ORDER BY r.score DESC, r.created_at DESC
LIMIT ? OFFSET ?;
```

---

## Обновленная структура связей

### Новые связи:

1. **users → votes** (1:N)
   - `votes.user_id` → `users.id`
   - ON DELETE CASCADE

2. **reviews → votes** (1:N)
   - `votes.review_id` → `reviews.id`
   - ON DELETE CASCADE (при удалении отзыва удаляются голоса)

3. **schedule_photos → votes** (1:N)
   - `votes.photo_id` → `schedule_photos.id`
   - ON DELETE CASCADE (при удалении фото удаляются голоса)

---

## Защита от накруток голосов

1. **UNIQUE constraints:**
   - Один пользователь = один голос за отзыв/фото
   - Предотвращает множественные голоса

2. **Проверка авторизации:**
   - Голосовать могут только авторизованные пользователи
   - Нельзя голосовать за свой собственный отзыв/фото (опционально)

3. **Логирование:**
   - Подозрительная активность: много голосов с одного IP
   - Множественные голоса от одного пользователя за короткое время

---

## Преимущества системы голосования

1. **Саморегуляция контента:**
   - Полезные отзывы и фото поднимаются вверх
   - Неполезные опускаются вниз
   - Не нужна модерация для базовой фильтрации

2. **Мотивация пользователей:**
   - Пользователи получают обратную связь
   - Качественный контент поощряется

3. **Улучшение UX:**
   - Самые полезные отзывы и актуальные фото расписания показываются первыми
   - Пользователи быстрее находят нужную информацию

4. **Гибкость:**
   - Можно сортировать по рейтингу, дате, количеству голосов
   - Можно фильтровать по типу контента

---

## Итоговая структура БД

**Основные сущности:**
1. `users` - пользователи
2. `rinks` - катки
3. `visits` - посещения ⭐
4. `reviews` - отзывы (с рейтингом)
5. `checkins` - отметки присутствия
6. `schedule_photos` - фотографии расписания (с рейтингом)
7. `votes` - голоса за отзывы и фото ⭐ НОВОЕ
8. `suspicious_activity` - логирование подозрительной активности

**Нормализация:** BCNF ✓

**Система голосования:** Reddit-style (upvote/downvote) ✓
