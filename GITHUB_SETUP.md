# Как создать репозиторий на GitHub

## Шаг 1: Создать аккаунт на GitHub (если еще нет)

1. Перейдите на https://github.com
2. Нажмите "Sign up"
3. Заполните форму регистрации
4. Подтвердите email

---

## Шаг 2: Создать новый репозиторий

1. Войдите в свой аккаунт GitHub
2. Нажмите кнопку **"+"** в правом верхнем углу
3. Выберите **"New repository"**

4. Заполните форму:
   - **Repository name:** `rinks-moscow-app` (или другое название)
   - **Description:** "Веб-приложение для поиска катков Москвы"
   - **Visibility:** 
     - ✅ **Public** (все видят) - для курсового проекта подойдет
     - ⚠️ **Private** (только вы видите) - если хотите скрыть
   - **НЕ ставьте галочки:**
     - ❌ Add a README file (у нас уже есть)
     - ❌ Add .gitignore (у нас уже есть)
     - ❌ Choose a license (можно добавить потом)

5. Нажмите **"Create repository"**

---

## Шаг 3: Подключить локальный репозиторий к GitHub

После создания репозитория GitHub покажет инструкцию. Выполните в терминале:

### Вариант 1: Если репозиторий пустой (рекомендуется)

```bash
cd C:\Users\kauiw\rinks-moscow-app

# Добавить удаленный репозиторий (замените YOUR_USERNAME на ваш логин GitHub)
git remote add origin https://github.com/YOUR_USERNAME/rinks-moscow-app.git

# Отправить код на GitHub
git push -u origin master
```

**Пример:**
Если ваш логин `grigory-yamanov`, то команда будет:
```bash
git remote add origin https://github.com/grigory-yamanov/rinks-moscow-app.git
git push -u origin master
```

### Вариант 2: Если GitHub предлагает другую команду

GitHub может предложить команду типа:
```bash
git remote add origin git@github.com:USERNAME/rinks-moscow-app.git
```

Используйте ту команду, которую показывает GitHub!

---

## Шаг 4: Авторизация

При первом `git push` GitHub попросит авторизоваться:

1. **Через браузер (рекомендуется):**
   - Git откроет браузер
   - Войдите в GitHub
   - Разрешите доступ

2. **Через Personal Access Token:**
   - Если браузер не открывается, создайте токен:
     - GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
     - Generate new token
     - Выберите права: `repo`
     - Скопируйте токен
     - Используйте токен как пароль при `git push`

---

## Шаг 5: Проверка

После успешного `git push`:

1. Обновите страницу репозитория на GitHub
2. Должны увидеть все ваши файлы
3. В истории коммитов должен быть ваш первый коммит

---

## Полезные команды Git:

```bash
# Проверить статус
git status

# Посмотреть все коммиты
git log

# Посмотреть подключенные удаленные репозитории
git remote -v

# Отправить изменения на GitHub
git push

# Получить изменения с GitHub
git pull
```

---

## Если что-то пошло не так:

### Ошибка "remote origin already exists":
```bash
git remote remove origin
git remote add origin https://github.com/YOUR_USERNAME/rinks-moscow-app.git
```

### Ошибка авторизации:
- Проверьте логин и пароль
- Используйте Personal Access Token вместо пароля
- Убедитесь, что репозиторий существует на GitHub

---

## Готово! ✅

После успешного `git push` ваш код будет на GitHub, и преподаватель сможет его посмотреть!
