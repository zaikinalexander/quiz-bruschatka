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

## Деплой

Рекомендуемый путь на сервере:

```bash
cd /var/www
git clone <repo> quiz.bruschatka.ru
cd /var/www/quiz.bruschatka.ru
cp website/.env.local.example website/.env.local
nano website/.env.local
COMPOSER_ALLOW_SUPERUSER=1 ./deploy/deploy.sh
```

Скрипт `deploy/deploy.sh` делает:

- `composer install --no-dev --optimize-autoloader`
- `php bin/console cache:clear --env=prod`
- `npm ci`
- `npm run build`
- копирование `dashboard/dist` в `website/public/admin`

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
