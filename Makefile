SHELL := /bin/bash

COMPOSE      := docker compose
PHP_SERVICE  := php
NODE_SERVICE := node
APP_DIR      := /var/www

ENV_FILE     := src/.env
ENV_EXAMPLE  := src/.env.example

UID := $(shell id -u)
GID := $(shell id -g)

PHP_EXEC_USER  := $(COMPOSE) exec -u $(UID):$(GID) $(PHP_SERVICE)
NODE_EXEC_USER := $(COMPOSE) exec -u $(UID):$(GID) $(NODE_SERVICE)
PHP_EXEC_ROOT  := $(COMPOSE) exec -u 0:0 $(PHP_SERVICE)

.PHONY: help
help:
	@echo ""
	@echo "Commands:"
	@echo "  make up           Start containers (build if needed)"
	@echo "  make down         Stop containers"
	@echo "  make restart      Restart containers"
	@echo "  make logs         Follow logs"
	@echo "  make ps           Show containers"
	@echo ""
	@echo "  make init         First time setup (env/install/key/migrate/link/clear)"
	@echo "  make install      composer install + npm(ci|install)"
	@echo "  make key          php artisan key:generate --force"
	@echo "  make migrate      php artisan migrate"
	@echo "  make fresh        php artisan migrate:fresh --seed"
	@echo ""
	@echo "  make php          Enter php container"
	@echo "  make node         Enter node container"
	@echo "  make artisan c='...'  Run artisan command"
	@echo "  make composer c='...' Run composer command"
	@echo "  make npm c='...'      Run npm command"
	@echo "  make dev          Run Vite dev server (host=0.0.0.0)"
	@echo ""
	@echo "  make fix-perms    Ensure storage/bootstrap/cache perms (root)"
	@echo "  make chown        sudo chown -R (fallback)"
	@echo ""

.PHONY: up down restart logs ps
up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

restart: down up

logs:
	$(COMPOSE) logs -f --tail=200

ps:
	$(COMPOSE) ps

.PHONY: php node
php:
	$(COMPOSE) exec $(PHP_SERVICE) bash

node:
	$(COMPOSE) exec $(NODE_SERVICE) bash

.PHONY: artisan composer npm
artisan:
	@if [ -z "$(c)" ]; then echo "Usage: make artisan c='migrate'"; exit 1; fi
	$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan $(c)"

composer:
	@if [ -z "$(c)" ]; then echo "Usage: make composer c='install'"; exit 1; fi
	$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && composer $(c)"

npm:
	@if [ -z "$(c)" ]; then echo "Usage: make npm c='run dev'"; exit 1; fi
	$(NODE_EXEC_USER) bash -lc "cd $(APP_DIR) && npm $(c)"

.PHONY: env install npm-install key migrate seed fresh storage-link clear optimize fix-perms

env:
	@if [ ! -f "$(ENV_FILE)" ]; then \
		if [ -f "$(ENV_EXAMPLE)" ]; then \
			cp "$(ENV_EXAMPLE)" "$(ENV_FILE)"; \
			echo "✅ created: $(ENV_FILE) from $(ENV_EXAMPLE)"; \
		else \
			echo "❌ $(ENV_EXAMPLE) not found. Please prepare it."; \
			exit 1; \
		fi \
	else \
		echo "ℹ️  $(ENV_FILE) already exists"; \
	fi

npm-install:
	@$(NODE_EXEC_USER) bash -lc 'cd $(APP_DIR) && if [ -f package-lock.json ]; then npm ci; else npm install; fi'

install:
	@$(MAKE) composer c="install"
	@$(MAKE) npm-install

key:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan key:generate --force"

migrate:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan migrate"

seed:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan db:seed"

fresh:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan migrate:fresh --seed"

storage-link:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan storage:link || true"

clear:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan optimize:clear"

optimize:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan optimize"

fix-perms:
	@$(PHP_EXEC_ROOT) bash -lc "cd $(APP_DIR) && \
		mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs bootstrap/cache && \
		chown -R $(UID):$(GID) storage bootstrap/cache && \
		chmod -R ug+rwX storage bootstrap/cache"

.PHONY: dev build
dev:
	@$(NODE_EXEC_USER) bash -lc "cd $(APP_DIR) && npm run dev -- --host 0.0.0.0 --port 5173"

build:
	@$(NODE_EXEC_USER) bash -lc "cd $(APP_DIR) && npm run build"

.PHONY: init
init: up env fix-perms install key migrate storage-link clear
	@echo ""
	@echo "🎉 Setup completed!"
	@echo "Next (dev):"
	@echo "  make dev"
	@echo ""

.PHONY: chown
chown:
	sudo chown -R ri309:ri309 ./src
