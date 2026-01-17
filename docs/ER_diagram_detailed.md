# ER-диаграмма базы данных
## Детальное описание для построения диаграммы

---

## Инструкция для построения в Draw.io или другом инструменте

### 1. Сущности (Entities)

#### Сущность: USERS (Пользователи)
- **Атрибуты:**
  - id (PK, INT) - первичный ключ
  - email (UNIQUE, VARCHAR) - уникальный email
  - password_hash (VARCHAR) - хеш пароля
  - name (VARCHAR) - имя
  - role (ENUM) - роль пользователя
  - created_at (TIMESTAMP) - дата регистрации
  - last_login (TIMESTAMP) - последний вход
  - ip_address (VARCHAR) - IP-адрес

#### Сущность: RINKS (Катки)
- **Атрибуты:**
  - id (PK, INT) - первичный ключ
  - name (VARCHAR) - название
  - address (TEXT) - адрес
  - district (VARCHAR) - район
  - latitude (DECIMAL) - широта
  - longitude (DECIMAL) - долгота
  - phone, email, website (VARCHAR) - контакты
  - working_hours (TEXT) - график работы
  - is_paid (BOOLEAN) - платность
  - price (DECIMAL) - цена
  - has_equipment_rental, has_locker_room, has_cafe, has_wifi, has_atm, has_medpoint (BOOLEAN) - инфраструктура
  - is_disabled_accessible (BOOLEAN) - для инвалидов
  - capacity (INT) - вместимость
  - lighting, coverage (VARCHAR) - характеристики
  - created_at, updated_at (TIMESTAMP) - даты

#### Сущность: REVIEWS (Отзывы)
- **Атрибуты:**
  - id (PK, INT) - первичный ключ
  - rink_id (FK, INT) - внешний ключ к RINKS
  - user_id (FK, INT) - внешний ключ к USERS
  - text (TEXT) - текст отзыва
  - rating (TINYINT) - рейтинг 1-5
  - ice_condition (TINYINT) - состояние льда 1-5
  - crowd_level (TINYINT) - загруженность 1-5
  - created_at, updated_at (TIMESTAMP) - даты

#### Сущность: CHECKINS (Отметки присутствия)
- **Атрибуты:**
  - id (PK, INT) - первичный ключ
  - rink_id (FK, INT) - внешний ключ к RINKS
  - user_id (FK, INT) - внешний ключ к USERS
  - latitude (DECIMAL) - широта пользователя
  - longitude (DECIMAL) - долгота пользователя
  - distance (DECIMAL) - расстояние до катка
  - ip_address (VARCHAR) - IP-адрес
  - timestamp (TIMESTAMP) - время отметки

#### Сущность: SCHEDULES (Расписание)
- **Атрибуты:**
  - id (PK, INT) - первичный ключ
  - rink_id (FK, INT) - внешний ключ к RINKS
  - day_of_week (TINYINT) - день недели 0-6
  - start_time (TIME) - время начала
  - end_time (TIME) - время окончания
  - type (ENUM) - тип: working/section
  - description (TEXT) - описание
  - created_by (FK, INT) - внешний ключ к USERS

#### Сущность: SUSPICIOUS_ACTIVITY (Подозрительная активность)
- **Атрибуты:**
  - id (PK, INT) - первичный ключ
  - user_id (FK, INT, NULL) - внешний ключ к USERS (может быть NULL)
  - ip_address (VARCHAR) - IP-адрес
  - activity_type (VARCHAR) - тип активности
  - details (TEXT) - детали
  - timestamp (TIMESTAMP) - время события

---

### 2. Связи (Relationships)

#### Связь 1: USERS → REVIEWS
- **Тип:** Один ко многим (1:N)
- **Описание:** Один пользователь может оставить много отзывов
- **Кардинальность:** USERS (1) ----< (N) REVIEWS
- **Внешний ключ:** reviews.user_id → users.id
- **ON DELETE:** CASCADE (при удалении пользователя удаляются его отзывы)

#### Связь 2: RINKS → REVIEWS
- **Тип:** Один ко многим (1:N)
- **Описание:** Один каток может иметь много отзывов
- **Кардинальность:** RINKS (1) ----< (N) REVIEWS
- **Внешний ключ:** reviews.rink_id → rinks.id
- **ON DELETE:** CASCADE (при удалении катка удаляются его отзывы)

#### Связь 3: USERS → CHECKINS
- **Тип:** Один ко многим (1:N)
- **Описание:** Один пользователь может сделать много отметок
- **Кардинальность:** USERS (1) ----< (N) CHECKINS
- **Внешний ключ:** checkins.user_id → users.id
- **ON DELETE:** CASCADE

#### Связь 4: RINKS → CHECKINS
- **Тип:** Один ко многим (1:N)
- **Описание:** На одном катке может быть много отметок
- **Кардинальность:** RINKS (1) ----< (N) CHECKINS
- **Внешний ключ:** checkins.rink_id → rinks.id
- **ON DELETE:** CASCADE

#### Связь 5: RINKS → SCHEDULES
- **Тип:** Один ко многим (1:N)
- **Описание:** Один каток может иметь много записей расписания
- **Кардинальность:** RINKS (1) ----< (N) SCHEDULES
- **Внешний ключ:** schedules.rink_id → rinks.id
- **ON DELETE:** CASCADE

#### Связь 6: USERS → SCHEDULES
- **Тип:** Один ко многим (1:N)
- **Описание:** Один пользователь может создать много записей расписания
- **Кардинальность:** USERS (1) ----< (N) SCHEDULES
- **Внешний ключ:** schedules.created_by → users.id
- **ON DELETE:** SET NULL (при удалении пользователя расписание остается)

#### Связь 7: USERS → SUSPICIOUS_ACTIVITY
- **Тип:** Один ко многим (1:N), опциональная
- **Описание:** Один пользователь может иметь много записей подозрительной активности (или NULL)
- **Кардинальность:** USERS (0..1) ----< (N) SUSPICIOUS_ACTIVITY
- **Внешний ключ:** suspicious_activity.user_id → users.id
- **ON DELETE:** SET NULL (при удалении пользователя записи остаются для анализа)

---

### 3. Визуальное представление (текстовая диаграмма)

```
                    ┌─────────────┐
                    │    USERS    │
                    ├─────────────┤
                    │ id (PK)     │
                    │ email       │
                    │ password... │
                    │ name        │
                    │ role        │
                    │ ...         │
                    └──────┬──────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        │ 1                │ 1                │ 0..1
        │                  │                  │
   ┌────▼────┐        ┌────▼────┐      ┌─────▼──────┐
   │ REVIEWS │        │ CHECKINS│      │SUSPICIOUS  │
   ├─────────┤        ├─────────┤      │ ACTIVITY   │
   │ id (PK) │        │ id (PK) │      ├────────────┤
   │ rink_id │        │ rink_id │      │ id (PK)    │
   │ user_id │        │ user_id │      │ user_id    │
   │ text    │        │ lat/lon │      │ ip_address │
   │ rating  │        │ distance│      │ ...        │
   │ ...     │        │ ...     │      └────────────┘
   └────┬────┘        └────┬────┘
        │                  │
        │ N                │ N
        │                  │
   ┌────▼──────────────────▼────┐
   │         RINKS               │
   ├─────────────────────────────┤
   │ id (PK)                     │
   │ name                        │
   │ address                     │
   │ latitude/longitude          │
   │ ...                         │
   └────┬────────────────────────┘
        │
        │ 1
        │
   ┌────▼────────┐
   │  SCHEDULES   │
   ├──────────────┤
   │ id (PK)      │
   │ rink_id      │
   │ day_of_week  │
   │ start/end    │
   │ type         │
   │ created_by   │
   └──────────────┘
```

---

### 4. Правила целостности данных

1. **Первичные ключи (PK):** Все таблицы имеют автоинкрементный id
2. **Внешние ключи (FK):** Все связи определены через FOREIGN KEY
3. **Каскадное удаление (CASCADE):**
   - При удалении пользователя → удаляются его отзывы и отметки
   - При удалении катка → удаляются его отзывы, отметки и расписание
4. **Установка NULL (SET NULL):**
   - При удалении пользователя → created_by в schedules становится NULL
   - При удалении пользователя → user_id в suspicious_activity становится NULL

---

### 5. Индексы для оптимизации

**Основные индексы:**
- users.email (UNIQUE) - для быстрой авторизации
- rinks.name - для поиска по названию
- rinks.district - для фильтрации по району
- rinks(latitude, longitude) - для геопоиска
- reviews.rink_id - для получения отзывов катка
- checkins.timestamp - для статистики по времени
- checkins.ip_address - для защиты от накруток

---

### 6. Нормализация

База данных находится в **третьей нормальной форме (3NF)**:
- Нет повторяющихся групп данных
- Все неключевые поля зависят от полного первичного ключа
- Нет транзитивных зависимостей

---

## Как построить диаграмму в Draw.io

1. Откройте https://app.diagrams.net/ (Draw.io)
2. Создайте новую диаграмму
3. Используйте элементы:
   - **Прямоугольники** для сущностей (таблиц)
   - **Ромбы** для связей (опционально)
   - **Линии** для связей между сущностями
4. На линиях укажите кардинальность (1, N, 0..1)
5. Добавьте атрибуты в прямоугольники сущностей
6. Выделите первичные ключи (PK) и внешние ключи (FK)

---

## Пример для копирования в Draw.io

**Сущности:**
- USERS (id PK, email, password_hash, name, role, ...)
- RINKS (id PK, name, address, district, latitude, longitude, ...)
- REVIEWS (id PK, rink_id FK, user_id FK, text, rating, ...)
- CHECKINS (id PK, rink_id FK, user_id FK, latitude, longitude, ...)
- SCHEDULES (id PK, rink_id FK, day_of_week, start_time, end_time, ...)
- SUSPICIOUS_ACTIVITY (id PK, user_id FK, ip_address, activity_type, ...)

**Связи:**
- USERS (1) → (N) REVIEWS
- RINKS (1) → (N) REVIEWS
- USERS (1) → (N) CHECKINS
- RINKS (1) → (N) CHECKINS
- RINKS (1) → (N) SCHEDULES
- USERS (1) → (N) SCHEDULES
- USERS (0..1) → (N) SUSPICIOUS_ACTIVITY
