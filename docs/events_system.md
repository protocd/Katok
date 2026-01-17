# Система мероприятий на катках

## Описание

Пользователи могут создавать мероприятия для катков (например, "догонялки", "игра в хоккей" и т.д.) и присоединяться к ним.

---

## Правила доступа

### Создание мероприятия:
- ✅ Может создать только пользователь, который **отметился на катке больше 5 раз** (более 5 `visits` для этого катка)
- ✅ Проверка в приложении: `SELECT COUNT(*) FROM visits WHERE user_id = ? AND rink_id = ?` (должно быть > 5)
- ✅ Цель: только опытные посетители катка могут создавать мероприятия

### Присоединение к мероприятию:
- ✅ Может присоединиться только пользователь, который **уже отмечался на этом катке** (есть хотя бы один `visit` для катка мероприятия)
- ✅ Проверка в приложении: `SELECT COUNT(*) FROM visits WHERE user_id = ? AND rink_id = (SELECT rink_id FROM events WHERE id = ?)` (должно быть >= 1)
- ✅ Кнопка "Приду" показывается только если пользователь имеет хотя бы один `visit` для катка

---

## Структура таблиц

### Таблица `events` (Мероприятия)

**Поля:**
- `id` - уникальный идентификатор
- `rink_id` - ID катка
- `created_by` - ID пользователя, создавшего мероприятие
- `title` - название (например, "Догонялки")
- `description` - описание
- `event_date` - дата проведения
- `event_time` - время проведения
- `max_participants` - максимальное количество участников (NULL = без ограничений)
- `status` - статус: active, cancelled, completed

**Связи:**
- N:1 с `rinks` (много мероприятий на одном катке)
- N:1 с `users` (много мероприятий от одного пользователя)

### Таблица `event_participants` (Участники)

**Поля:**
- `id` - уникальный идентификатор
- `event_id` - ID мероприятия
- `user_id` - ID пользователя
- `status` - статус участия: confirmed (приду), maybe (возможно приду)

**Связи:**
- N:1 с `events` (много участников в одном мероприятии)
- N:1 с `users` (один пользователь может участвовать в многих мероприятиях)
- UNIQUE (user_id, event_id) - один пользователь = одно участие

---

## Логика работы

### 1. Создание мероприятия:

```php
// Проверка: отметился ли пользователь на катке больше 5 раз?
$visitsCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM visits WHERE user_id = ? AND rink_id = ?",
    [$userId, $rinkId]
);

if ($visitsCount['count'] < 5) {
    $remaining = 5 - $visitsCount['count'];
    throw new Exception("Для создания мероприятия нужно отметитьсь на катке минимум 5 раз. Осталось: {$remaining}");
}

// Создание мероприятия
$eventId = $db->insert(
    "INSERT INTO events (rink_id, created_by, title, description, event_date, event_time, max_participants) 
     VALUES (?, ?, ?, ?, ?, ?, ?)",
    [$rinkId, $userId, $title, $description, $eventDate, $eventTime, $maxParticipants]
);
```

### 2. Присоединение к мероприятию:

```php
// Получаем каток мероприятия
$event = $db->fetchOne("SELECT rink_id FROM events WHERE id = ?", [$eventId]);

// Проверка: был ли пользователь на катке?
$hasVisit = $db->fetchOne(
    "SELECT COUNT(*) as count FROM visits WHERE user_id = ? AND rink_id = ?",
    [$userId, $event['rink_id']]
);

if ($hasVisit['count'] == 0) {
    throw new Exception("Вы должны сначала посетить этот каток, чтобы присоединиться к мероприятию");
}

// Проверка: не превышен ли лимит участников?
if ($event['max_participants']) {
    $participantsCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM event_participants WHERE event_id = ? AND status = 'confirmed'",
        [$eventId]
    );
    
    if ($participantsCount['count'] >= $event['max_participants']) {
        throw new Exception("Мероприятие уже заполнено");
    }
}

// Присоединение
$db->insert(
    "INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, 'confirmed')
     ON DUPLICATE KEY UPDATE status = 'confirmed'",
    [$eventId, $userId]
);
```

### 3. Отображение кнопки "Создать мероприятие":

```php
// Проверка: отметился ли пользователь на катке больше 5 раз?
$visitsCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM visits WHERE user_id = ? AND rink_id = ?",
    [$userId, $rinkId]
);

$canCreateEvent = $visitsCount['count'] >= 5;

// В frontend:
if ($canCreateEvent) {
    // Показать кнопку "Создать мероприятие"
} else {
    $remaining = 5 - $visitsCount['count'];
    // Показать сообщение: "Для создания мероприятия нужно отметитьсь на катке минимум 5 раз. Осталось: {$remaining}"
}
```

### 4. Отображение кнопки "Приду":

```php
// Проверка: был ли пользователь на катке?
$hasVisit = $db->fetchOne(
    "SELECT COUNT(*) as count FROM visits WHERE user_id = ? AND rink_id = ?",
    [$userId, $rinkId]
);

$canJoin = $hasVisit['count'] > 0;

// В frontend:
if ($canJoin) {
    // Показать кнопку "Приду"
} else {
    // Показать сообщение: "Сначала посетите каток, чтобы присоединиться к мероприятию"
}
```

---

## API эндпоинты

### GET /api/events.php?rink_id={id}
Получить мероприятия катка

### GET /api/events.php?id={id}
Получить одно мероприятие с участниками

### POST /api/events.php
Создать мероприятие
- Проверка: есть ли у пользователя больше 5 visits для катка
- Ошибка 403, если меньше 5 посещений

### POST /api/events/join.php
Присоединиться к мероприятию
- Проверка: есть ли visit у пользователя для катка мероприятия

### DELETE /api/events/leave.php
Покинуть мероприятие

### GET /api/events/participants.php?event_id={id}
Получить участников мероприятия

---

## Примеры использования

### Пример 1: Создание мероприятия "Догонялки"

```json
POST /api/events.php
{
  "rink_id": 5,
  "title": "Догонялки на катке",
  "description": "Играем в догонялки, все желающие!",
  "event_date": "2025-01-20",
  "event_time": "18:00",
  "max_participants": 20
}
```

### Пример 2: Присоединение к мероприятию

```json
POST /api/events/join.php
{
  "event_id": 10,
  "status": "confirmed"
}
```

---

## Защита от накруток

1. **Проверка количества visits перед созданием:**
   - Пользователь должен иметь **более 5 посещений** катка для создания мероприятия
   - Только опытные посетители могут создавать мероприятия
   - Предотвращает создание мероприятий новичками или спамерами

2. **Проверка visit перед присоединением:**
   - Пользователь должен иметь хотя бы одно посещение катка
   - Нельзя присоединиться к мероприятию на катке, на котором не был

3. **UNIQUE constraint:**
   - Один пользователь = одно участие в мероприятии
   - Предотвращает дубликаты

4. **Логирование:**
   - Подозрительная активность: много мероприятий от одного пользователя
   - Много присоединений с одного IP

---

## Преимущества системы

1. **Социальное взаимодействие:**
   - Пользователи могут организовывать игры и встречи
   - Поиск единомышленников

2. **Безопасность:**
   - Только реальные посетители катка могут создавать/присоединяться
   - Защита от спама и накруток

3. **Актуальность:**
   - Мероприятия создаются людьми, которые знают каток
   - Реальные встречи на реальных катках
