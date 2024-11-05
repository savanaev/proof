COMPOSE = docker-compose
APP_NAME = app
DB_NAME = db

# Сборка и запуск контейнеров
up:
	$(COMPOSE) up -d

# Остановка контейнеров
down:
	$(COMPOSE) down

# Перезапуск контейнеров
restart:
	$(COMPOSE) down
	$(COMPOSE) up -d

# Остановка контейнеров с очисткой
down-clear:
	$(COMPOSE) down -v --remove-orphans

# Сборка образов
build:
	$(COMPOSE) build

# Инициализация приложения
app-init:
	$(COMPOSE) run --rm $(APP_NAME) composer install

# Удаление базы данных
db-drop:
	sleep 15 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:database:drop --force || (sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:database:drop --force)

# Создание базы данных с повторными попытками подключения
db-create:
	sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:database:create || (sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:database:create)

# Выполнение миграций с повторными попытками подключения
db-migrate:
	sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:migrations:migrate  --no-interaction || (sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:migrations:migrate --no-interaction)

# Инициализация базы данных
db-init: db-drop db-create db-migrate

# Инициализация контейнеров
init: down-clear build up app-init db-init

.PHONY: up down restart down-clear build app-init db-init db-drop db-create db-migrate init