ifneq (,$(wildcard ./.env))
    include .env
    export $(shell sed 's/=.*//' .env)
endif

COMPOSE = docker-compose
APP_NAME = symfony_app
DB_NAME = symfony_db

# Сборка и запуск контейнеров
up:
	$(COMPOSE) up -d --scale worker=$(WORKER_COUNT)

# Остановка контейнеров
down:
	$(COMPOSE) down

# Перезапуск контейнеров
restart:
	$(COMPOSE) down
	$(COMPOSE) up -d --scale worker=$(WORKER_COUNT)

# Остановка контейнеров с очисткой
down-clear:
	$(COMPOSE) down -v --remove-orphans

# Сборка образов
build:
	$(COMPOSE) build

# Масштабирование воркеров
scale-workers:
	sh project/docker/scripts/scale_workers.sh &> /dev/null &

# Инициализация приложения
app-init:
	$(COMPOSE) run --rm $(APP_NAME) composer install

# Удаление базы данных (повторная попытка подключения на случай не готовности контейнера)
db-drop:
	sleep 15 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:database:drop --force || (sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:database:drop --force)

# Создание базы данных (повторная попытка подключения на случай не готовности контейнера)
db-create:
	sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:database:create || (sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:database:create)

# Выполнение миграций с повторными попытками подключения (повторная попытка подключения на случай не готовности контейнера)
db-migrate:
	sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:migrations:migrate --no-interaction || (sleep 5 && $(COMPOSE) exec $(APP_NAME) php bin/console doctrine:migrations:migrate --no-interaction)

# Инициализация базы данных
db-init: db-drop db-create db-migrate

# Инициализация контейнеров
init: down-clear build up app-init db-init  scale-workers

.PHONY: up down restart down-clear build app-init db-init db-drop db-create db-migrate init scale-workers