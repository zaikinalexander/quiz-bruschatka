# quiz.bruschatka.ru

Отдельный проект квиза для `quiz.bruschatka.ru`.

- `website/` - Symfony-style сайт с Twig, JSON-конфигом квиза и API
- `dashboard/` - Vue 3 + Vite админка
- `website/public/uploads/quiz/` - публичные картинки квиза
- `website/data/quiz-config.json` - тексты, ссылки на фоны и карточки
- `website/data/quiz-leads.json` - сохраненные заявки с порядковыми номерами

## Что уже есть

- desktop-сценарий из Figma на 4 шага: площадь, цвет, обработка, контакт
- mobile-сценарий в стиле Figma на 4 шага с тем же содержанием, что desktop: площадь, цвет, обработка, контакт
- графические материалы из Figma сохранены локально в `website/public/uploads/quiz/`
- настройки заголовков, редиректа, счетчика Метрики и email-получателей
- загрузка картинок через `/api/file/upload`
- отправка заявок через SMTP (`POST /api/lead`)
- сохранение заявок в JSON и просмотр в админке через `/api/leads`
- dashboard готов к выкладке в `/admin/`

## Локальный запуск

Website:

```bash
cd /Users/aleksandrzaikin/Documents/DEV/quiz.bruschatka.ru/website
composer install
php -S 127.0.0.1:8001 -t public public/router.php
```

Dashboard:

```bash
cd /Users/aleksandrzaikin/Documents/DEV/quiz.bruschatka.ru/dashboard
npm install
npm run dev
```

## SMTP для заявок квиза

В репозитории по умолчанию стоит безопасный заглушечный transport:

```dotenv
MAILER_DSN=null://null
MAILER_FROM_EMAIL=postmaster@bruschatka.ru
QUIZ_LEAD_TO=zaikinalexandr@gmail.com,info@bruschatka.ru
```

На сервере это нужно переопределить в `website/.env.local`:

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=fbb5e3cbf5fd310a1e80166df778ad2c
MAILER_DSN=smtp://postmaster%40bruschatka.ru:<mailgun-password>@smtp.eu.mailgun.org:587?encryption=tls&auth_mode=login
MAILER_FROM_EMAIL=postmaster@bruschatka.ru
QUIZ_LEAD_TO=zaikinalexandr@gmail.com,info@bruschatka.ru
```

Список адресов для заявок можно менять и из админки в разделе настроек квиза. Поддерживаются несколько адресов, по одному в строке.

## Метрика и цели

Счетчик Метрики подключен в `website/templates/base.html.twig`, ID берется из настройки `general.metrikaCounterId`.

Базовые цели для воронки:

- `quiz_page_view` - открытие страницы квиза
- `quiz_desktop_view`, `quiz_mobile_view` - определение версии
- `quiz_desktop_step_1_view` ... `quiz_desktop_step_4_view` - просмотры шагов desktop
- `quiz_mobile_step_1_view` ... `quiz_mobile_step_4_view` - просмотры шагов mobile
- `quiz_step_complete` - переход к следующему шагу
- `quiz_start` - первый выбор ответа
- `quiz_phone_focus` - фокус на телефоне
- `quiz_submit_click` - клик отправки
- `quiz_submit_error` - ошибка отправки или валидации
- `quiz_submit_success` и `ok_zakaz` - успешная заявка
- `quiz_success_screen_view` - показ экрана успеха
- `quiz_redirect` - редирект на `bruschatka.ru`

Детальные цели по ответам:

- `quiz_answer_area`, `quiz_answer_area_50_100`, `quiz_answer_area_100_200`, `quiz_answer_area_200_400`, `quiz_answer_area_400_plus`
- `quiz_answer_color`, `quiz_answer_color_gray`, `quiz_answer_color_dark`, `quiz_answer_color_green`, `quiz_answer_color_burgundy`, `quiz_answer_color_natural`
- `quiz_answer_finish`, `quiz_answer_finish_chipped`, `quiz_answer_finish_sawn`, `quiz_answer_finish_tumbled`, `quiz_answer_finish_unknown`

## Админка

Админка доступна по адресу `/admin/` и закрыта сессионным логином через API:

- `POST /api/admin/login`
- `GET /api/admin/me`
- `POST /api/admin/logout`

Публичной остается только отправка заявки из квиза: `POST /api/lead`. Все остальные API под `/api/` требуют входа в админку.

Логин и пароль задаются на сервере в `.env.local`:

```dotenv
ADMIN_USERNAME=admin
ADMIN_PASSWORD=<admin-password>
```

## Деплой

Рекомендуемый путь на сервере:

```bash
cd /var/www
git clone <repo> quiz.bruschatka.ru
cd /var/www/quiz.bruschatka.ru
cp website/.env.local.example website/.env.local
nano website/.env.local
WEB_USER=app WEB_GROUP=app COMPOSER_ALLOW_SUPERUSER=1 ./deploy/deploy.sh
```

Скрипт `deploy/deploy.sh` делает:

- `composer install --no-dev --optimize-autoloader`
- `php bin/console cache:clear --env=prod`
- `npm ci`
- `npm run build`
- копирование `dashboard/dist` в `website/public/admin`
- выставление writable-прав для пользователя PHP-FPM через `WEB_USER` / `WEB_GROUP`

## Nginx

Готовый шаблон конфига лежит в:

`deploy/nginx.quiz.bruschatka.ru.conf`

Ожидаемый `root`:

```text
/var/www/quiz.bruschatka.ru/website/public
```

После копирования конфига:

```bash
cp deploy/nginx.quiz.bruschatka.ru.conf /etc/nginx/sites-available/quiz.bruschatka.ru
ln -s /etc/nginx/sites-available/quiz.bruschatka.ru /etc/nginx/sites-enabled/quiz.bruschatka.ru
nginx -t
systemctl reload nginx
certbot --nginx -d quiz.bruschatka.ru
```

## Важно для сервера

- Для загрузки больших картинок нужен повышенный `upload_max_filesize` и `post_max_size`.
- Для этого добавлен `website/public/.user.ini`, но на некоторых серверах лимиты все равно надо продублировать в PHP-FPM pool / `php.ini`.
- Папки `website/var`, `website/public/uploads/quiz` и `website/data` должны быть writable для веб-сервера.
