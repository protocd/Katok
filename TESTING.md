# Инструкция по тестированию

## Быстрый старт

### 1. Настройка базы данных

**Способ 1 (автоматический):**
1. Убедитесь, что MySQL запущен
2. Откройте в браузере:
   ```
   http://localhost/rinks-moscow-app/backend/test/setup_database.php
   ```
3. Скрипт создаст БД и все таблицы

**Способ 2 (через phpMyAdmin):**
1. Откройте http://localhost/phpmyadmin
2. Создайте БД `rinks_moscow`
3. Импортируйте `sql/database_final.sql`

### 2. Настройка config.php

1. Скопируйте `backend/config/config.example.php` в `backend/config/config.php`
2. Заполните настройки:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'rinks_moscow');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### 3. Тестирование

**Тест подключения:**
```
http://localhost/rinks-moscow-app/backend/test/test_connection.php
```

**Тест классов:**
```
http://localhost/rinks-moscow-app/backend/test/test_classes.php
```

**Тест API (через Postman или curl):**
```bash
# Получить катки
curl http://localhost/rinks-moscow-app/backend/api/rinks.php

# Регистрация
curl -X POST http://localhost/rinks-moscow-app/backend/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"test123","name":"Тест"}'
```

---

## Что проверять:

✅ Подключение к БД работает
✅ Все таблицы созданы
✅ Классы работают без ошибок
✅ API возвращает JSON ответы
✅ Авторизация работает

---

## Если что-то не работает:

1. Проверьте, запущен ли Apache и MySQL
2. Проверьте настройки в config.php
3. Проверьте права доступа к файлам
4. Посмотрите логи ошибок PHP
