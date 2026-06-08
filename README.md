# PizzaLumina 🍕

Backend для интернет-магазина пиццы и напитков на Laravel 13.8

## Требования

- **Docker** (версия 20.10+)
- **Docker Compose** (версия 2.0+)
- **Make** (опционально, но удобно)
- **Git**

## Быстрый старт

### 1. Клонировать репозиторий
```bash
git clone https://github.com/Skyredingsan/PizzaLumina.git
cd PizzaLumina
```

### 2. Настроить окружение
# Скопировать .env для Laravel
```
cp src/.env.example src/.env
```

### 3. Запустить контейнеры
```
make build
make up
```

### 4. Установить зависимости и настроить Laravel
```
make composer-install
make artisan cmd="key:generate"
make artisan cmd="migrate"   # после появления миграций
```

### 5. Открыть в браузере
http://localhost:8080