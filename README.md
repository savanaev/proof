# Документация по приложению для получения данных из API

Это приложение предназначено для получения и сохранения данных по всем сущностям **Post** из API [proof.moneymediagroup.co.uk](https://proof.moneymediagroup.co.uk/api/docs) в локальную базу данных. Приложение построено на основе Symfony 7 и использует Docker, MySQL и PHP 8.3.

## Описание функциональности

- **Получение данных из API**: Приложение извлекает данные по сущностям **Post** через REST API.
- **Сохранение данных**: Все данные, включая поле **body** для каждого поста, сохраняются в локальную базу данных MySQL.
- **Обработка ошибок**: Приложение обрабатывает случайные ошибки и таймауты, возникающие при запросах к API, чтобы обеспечить стабильную работу.
- **Ограничение запросов**: Учитывается ограничение на количество запросов к API (не более 100 запросов в минуту с одного IP-адреса).
- **Периодическая загрузка**: Опционально, приложение может быть настроено для ежедневной загрузки новых данных с помощью cron.



## Конфигурация окружения

Приложение использует переменные окружения, которые определяются в файлах `.env` и `.env.local`. Основные настройки приложения:

### Файл `.env`

```env

# Тип клиента используемый для загрузки даннх
#  * direct - прямая загрузка, без использования стронних сервисов
#  * proxy -  загрузка с использованием прокси
HTTP_CLIENT_TYPE=proxy

# Максимальное количество страниц для обработки, при однократном вызове команды app:fetch-posts
REQUESTS_LIMIT=7

# Максимальное количество постов для повторной обработки, при однократном вызове команды app:retry-failed-requests
FAILED_REQUESTS_LIMIT=100

###> PROXY LIST ###
# brightdata.com
PROXY_HOST="brd.superproxy.io:22225"
USERNAME="brd-customer-hl_4f0d8683-zone-residential_proxy1"
PASSWORD=kxqyitd9j33r

# api.proxyscrape.com
PROXY_API_URL="https://api.proxyscrape.com/v4/free-proxy-list/get?request=display_proxies&proxy_format=protocolipport&format=json"
API_KEY=a1eeh5n0o98gnlm3z6qx
###< PROXY LIST ###

```

- **HTTP_CLIENT_TYPE**: Тип клиента для загрузки данных (например, `direct` или `proxy`).
- **REQUESTS_LIMIT**: Максимальное количество страниц для обработки при вызове команды `app:fetch-posts`.
- **FAILED_REQUESTS_LIMIT**: Максимальное количество постов для повторной обработки при вызове команды `app:retry-failed-requests`.
- **Прокси настройки**: Данные для подключения к прокси необходимо заменить на свои. В данной сборке реализовано два прокси провайдера на выбор, по умолчанию используется BrightData Provider.

## Команды Makefile

### Сборка и запуск контейнеров

```bash
make up
```

Эта команда соберет образы и запустит контейнеры в фоновом режиме.

### Остановка контейнеров

```bash
make down
```

Остановить все запущенные контейнеры.

### Перезапуск контейнеров

```bash
make restart
```

Остановить и заново запустить контейнеры.

### Остановка контейнеров с очисткой

```bash
make down-clear
```

Остановить контейнеры и удалить все объемы данных, созданные в процессе.

### Сборка образов

```bash
make build
```

Собрать образы для всех сервисов, определенных в `docker-compose.yml`.

### Инициализация приложения

```bash
make app-init
```

Установить зависимости проекта с помощью Composer.

### Удаление базы данных

```bash
make db-drop
```

Удалить базу данных. Если база данных не доступна, будет предпринята попытка повторного подключения.

### Создание базы данных

```bash
make db-create
```

Создать базу данных. Если база данных не доступна, будет предпринята попытка повторного подключения.

### Выполнение миграций

```bash
make db-migrate
```

Запустить миграции базы данных. Если база данных не доступна, будет предпринята попытка повторного подключения. Выполнение миграций происходит без необходимости ввода данных пользователем.

### Инициализация базы данных

```bash
make db-init
```

Полная инициализация базы данных: удаление, создание и выполнение миграций.

### Инициализация контейров

```bash
make init
```

Полная инициализация: остановка контейнеров, сборка образов, запуск контейнеров, инициализация приложения и базы данных.

## Заметки

- Команды `db-drop`, `db-create`, и `db-migrate` содержат временные задержки (sleep) для обеспечения доступности базы данных перед выполнением команд.

### Cron

Приложение также настроено для автоматического запуска команд через cron:

```cron
# Запуск команды для получения записей
* * * * * /usr/local/bin/php /var/www/html/bin/console app:fetch-posts >> /var/log/cron.log 2>&1

# Запуск команды для обработки неудачных запросов
*/10 * * * * /usr/local/bin/php /var/www/html/bin/console app:retry-failed-requests >> /var/log/cron.log 2>&1
```
## Установка

Перед использованием убедитесь, что у вас установлены [Docker](https://www.docker.com/) и [Docker Compose](https://docs.docker.com/compose/).

```bash
make init
```

## Команды приложения

В приложении реализованы две команды, которые можно вызывать через консоль:

### 1. Получение данных из API

```bash
php bin/console app:fetch-posts --limit [число]
```

- **Описание**: Эта команда извлекает данные из API и сохраняет их в локальную базу данных.
- **Параметр `--limit`**: Максимальное количество страниц для обработки за один раз (по умолчанию берется значение из переменной окружения `REQUESTS_LIMIT`).
- **Блокировка**: Если команда уже выполняется, будет выведено предупреждение и команда не запустится.

### 2. Повторное получение неудачных запросов

```bash
php bin/console app:retry-failed-requests --limit [число]
```

- **Описание**: Эта команда повторно извлекает данные для запросов, которые не удалось выполнить.
- **Параметр `--limit`**: Максимальное количество записей для обработки за один раз (по умолчанию берется значение из переменной окружения `FAILED_REQUESTS_LIMIT`).
- **Блокировка**: Если команда уже выполняется, будет выведено предупреждение и команда не запустится.


## Лицензия

Этот проект лицензирован под лицензией MIT. См. файл [LICENSE](LICENSE) для подробной информации.