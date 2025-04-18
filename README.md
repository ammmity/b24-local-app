# Локальное приложение Битрикс24 для автоматизации процессов производства

Приложение устанавливается как локальное приложение в Битрикс24 и добавляет пункт в верхнем меню карточки сделки.

## Технологии

### Backend
- **PHP 8.1 +**
- **Slim 4** — фреймворк для создания API.
- **Doctrine ORM** — работа с базой данных, только orm, не бандл.
- **PHP-DI** — контейнер для внедрения зависимостей.
- **Twig** — шаблонизатор для генерации HTML.

### Frontend
- **Vite** — сборщик.
- **Vue 3** — фреймворк для создания пользовательского интерфейса.

## Установка

### backend
- `composer install`
- `cp config/settings.example.php config/settings.php`
- заполнить подключение к бд в config/settings.php
- `./bin/doctrine orm:schema-tool:update --force`
- `./bin/doctrine orm:generate-proxies`

### frontend
- `npm run build`
- подставить в `resources/templates/deal-detail.html.twig` собранный js и css из public/dist
- подставить в `resources/templates/app.html.twig` собранный js и css из public/dist

## Запуск
- `php -S localhost:8080`
- `npm run dev`

#### При разработке в локальной среде отключаем auth и tehnolog мидлвары в public/index.php)

## Установка в Битрикс24

### Необходимые права
1. crm
2. placement
3. catalog
4. user
7. task
6. tasks
6. tasks_extended
5. socialnetwork
8. sonet_group

### Локальное приложение
1. Укажите путь к вашему обработчику:  
   `example.test/app/`

2. Для первоначальной установки используйте путь:  
   `example.test/app/install/`

### Вывести свои данные в карточку CRM
1. Заголовок виджета Русский (ru)*: 
    Производство
2. URL обработчика виджета*:
    `example.test/app/deal/`
3. Места вывода виджета:
    CRM_DEAL_DETAIL_TAB
---
