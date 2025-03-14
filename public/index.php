<?php
use App\Entities\BitrixGroupKanbanStage;
use App\Entities\ProductionSchemeStage;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Services\CRestService;
use App\Services\KanbanStageService;
use App\Services\ProductionSchemeService;
use App\Settings\Settings;
use App\Settings\SettingsInterface;
use App\Middlewares\{
    JsonResponseMiddleware,
    CorsResponseMiddleware
};
use App\Controllers\{
    AppController,
    InstallB24AppController,
    UsersController,
    ProductPartsController,
    DealsController,
    OperationTypesController,
    OperationPricesController,
    ProductProductionStagesController,
    ProductionSchemesController,
    GroupsController,
    B24EventsController,
    OperationLogsController,
    GoodsController,
    ReportsController
};
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

require __DIR__ . '/../vendor/autoload.php';

define('C_REST_IGNORE_SSL', true);//turn off validate ssl by curl

$container = new DI\Container([
    SettingsInterface::class => function () {
        return new Settings(require_once __DIR__ . '/../config/settings.php');
    },
    CRestService::class => DI\factory(function () {
        return new CRestService();
    }, [
        'cache' => false
    ]),
    KanbanStageService::class => DI\factory(function (CRestService $CRestService) {
        return new KanbanStageService($CRestService);
    }),
    ProductionSchemeService::class => DI\factory(function (CRestService $CRestService, EntityManagerInterface $entityManager) {
        return new ProductionSchemeService($CRestService, $entityManager);
    }, [
        'cache' => false
    ]),
    Connection::class => DI\factory(function (SettingsInterface $settings) {
        $dbSettings = $settings->get('db');
        $connectionParams = [
            'dbname' => $dbSettings['dbname'],
            'user' => $dbSettings['user'],
            'password' => $dbSettings['password'],
            'host' => $dbSettings['host'],
            'driver' => $dbSettings['driver'],
            'port' => $dbSettings['port'],
        ];
        return DriverManager::getConnection($connectionParams);
    }),
    EntityManagerInterface::class => DI\factory(function (Connection $connection) {
        static $instance = null;
        if ($instance === null) {
            $paths = [__DIR__ . '/../src/Entities'];
            $isDevMode = true;
            $config = ORMSetup::createAttributeMetadataConfiguration(
                paths: $paths,
                isDevMode: $isDevMode,
                proxyDir: null,
                cache: null
            );
            $driver = new AttributeDriver($paths);
            $config->setMetadataDriverImpl($driver);
            $config->setAutoGenerateProxyClasses(false);
            $instance = new EntityManager($connection, $config);
        }
        return $instance;
    })
]);
AppFactory::setContainer($container);

$b24Settings = $container->get(SettingsInterface::class)->get('b24');
define('C_REST_CLIENT_ID', $b24Settings['appId']);
define('C_REST_CLIENT_SECRET', $b24Settings['appKey']);

$app = AppFactory::create();
$twig = Twig::create(__DIR__ . '/../resources/templates', ['cache' => false]);//__DIR__ . '/../templatesCache'
$app->add(TwigMiddleware::create($app, $twig));
$app->add(JsonResponseMiddleware::class);
$app->add(CorsResponseMiddleware::class);

$app->setBasePath('/production-app/public');

// Routes
$app->group('/app/', function (RouteCollectorProxy $group) {
    $group->any('', [AppController::class, 'dashboard'])->setName('dashboard');
    $group->any('deal-production-scheme/', [AppController::class, 'dealProductionScheme'])->setName('deal-production-scheme');
    $group->any('install/', [InstallB24AppController::class, 'install'])->setName('install-as-b24-app');
});

$app->group('/api/', function (RouteCollectorProxy $group) {
//    $group->any('deals', [DealsController::class, 'list'])->setName('deals-list');
    $group->any('deals/{id}', [DealsController::class, 'get'])->setName('deal-resource');

    $group->any('users', [UsersController::class, 'list'])->setName('users-list');
    $group->any('users/{id}', [UsersController::class, 'get'])->setName('user-resource');

    $group->get('production-schemes/{id}', [ProductionSchemesController::class, 'get'])->setName('get-deal-production-scheme');
    $group->post('production-schemes', [ProductionSchemesController::class, 'store'])->setName('deal-production-scheme-resource');
    $group->patch('production-schemes/{id}', [ProductionSchemesController::class, 'update'])->setName('update-deal-production-scheme');
    $group->get('production-schemes/{id}/sync', [ProductionSchemesController::class, 'sync'])->setName('sync-scheme-and-stages-statuses');

    $group->any('product-parts', [ProductPartsController::class, 'list'])->setName('products-parts-list');
    $group->any('product-parts/{id}', [ProductPartsController::class, 'get'])->setName('product-parts-resource');
    $group->any('product-parts/import/', [ProductPartsController::class, 'import'])->setName('import-product-parts-from-b24');

    $group->get('operation-types', [OperationTypesController::class, 'list'])->setName('operation-types-list');
    $group->get('operation-types/{id}', [OperationTypesController::class, 'get'])->setName('operation-type-resource');
    $group->post('operation-types', [OperationTypesController::class, 'create'])->setName('add-operation-type');
    $group->patch('operation-types/{id}', [OperationTypesController::class, 'update'])->setName('update-operation-type');
    $group->delete('operation-types/{id}', [OperationTypesController::class, 'remove'])->setName('delete-operation-type');

    $group->get('product-operation-stages', [ProductProductionStagesController::class, 'list'])->setName('product-operation-stages-list');
    $group->get('product-operation-stages/{id}', [ProductProductionStagesController::class, 'get'])->setName('product-operation-stages-resource');
    $group->post('product-operation-stages', [ProductProductionStagesController::class, 'store'])->setName('add-product-operation-stages');
    $group->patch('product-operation-stages/{id}', [ProductProductionStagesController::class, 'update'])->setName('update-product-operation-stages');
    $group->delete('product-operation-stages/{id}', [ProductProductionStagesController::class, 'delete'])->setName('delete-product-operation-stages');
    $group->patch('product-operation-stages/reorder/', [ProductProductionStagesController::class, 'reorder'])->setName('reorder-product-operation-stages');

    $group->get('groups', [GroupsController::class, 'list'])->setName('b24-groups-list');


    $group->get('b24-events/bind-event-handlers', [B24EventsController::class, 'bindEventHandlers'])->setName('b24-bind-event-handlers');
    $group->post('b24-events/task-updated', [B24EventsController::class, 'taskUpdated'])->setName('b24-task-updated');

    $group->get('operation-prices', [OperationPricesController::class, 'list'])->setName('operation-prices-list');
    $group->get('operation-prices/{id}', [OperationPricesController::class, 'get'])->setName('operation-price-resource');
    $group->post('operation-prices', [OperationPricesController::class, 'create'])->setName('add-operation-price');
    $group->patch('operation-prices/{id}', [OperationPricesController::class, 'update'])->setName('update-operation-price');
    $group->delete('operation-prices/{id}', [OperationPricesController::class, 'delete'])->setName('delete-operation-price');

    $group->get('operation-logs', [OperationLogsController::class, 'list'])->setName('operation-logs-list');
    $group->get('operation-logs/users', [OperationLogsController::class, 'getUsers'])->setName('operation-logs-users');
    $group->get('operation-logs/{id}', [OperationLogsController::class, 'get'])->setName('operation-log-resource');
    $group->post('operation-logs', [OperationLogsController::class, 'create'])->setName('add-operation-log');
    $group->get('operation-logs/deal/{dealId}', [OperationLogsController::class, 'getByDealId'])->setName('get-operation-logs-by-deal');

    // Маршруты для товаров
    $group->get('goods', [GoodsController::class, 'list']);
    $group->get('goods/{id}', [GoodsController::class, 'get']);
    $group->post('goods', [GoodsController::class, 'create']);
    $group->put('goods/{id}', [GoodsController::class, 'update']);
    $group->delete('goods/{id}', [GoodsController::class, 'delete']);
    $group->any('goods/import/', [GoodsController::class, 'import']);

    // Отчеты
    $group->get('reports/employee-operations', [ReportsController::class, 'employeeOperations'])->setName('employee-operations-report');
    $group->get('reports/operation-users', [ReportsController::class, 'getOperationUsers'])->setName('operation-users-report');
});

$app->get('/fill-log', function (Request $request, Response $response, $args) use ($container) {
    $entityManager = $container->get(EntityManagerInterface::class);

    // Создаем тестовых пользователей
    $users = [
        ['id' => 101, 'name' => 'Иванов Иван'],
        ['id' => 102, 'name' => 'Петров Петр'],
        ['id' => 103, 'name' => 'Сидорова Анна']
    ];

    // Создаем тестовые операции
    $operations = [
        'Фрезеровка',
        'Токарная обработка',
        'Шлифовка',
        'Сверление',
        'Сборка',
        'Покраска',
        'Полировка',
        'Контроль качества'
    ];

    // Создаем тестовые детали
    $details = [
        ['id' => 201, 'name' => 'Корпус'],
        ['id' => 202, 'name' => 'Крышка'],
        ['id' => 203, 'name' => 'Вал'],
        ['id' => 204, 'name' => 'Шестерня'],
        ['id' => 205, 'name' => 'Подшипник'],
        ['id' => 206, 'name' => 'Втулка'],
        ['id' => 207, 'name' => 'Фланец'],
        ['id' => 208, 'name' => 'Кронштейн']
    ];

    // Создаем тестовые сделки
    $deals = [501, 502, 503, 504, 505];

    // Создаем тестовые задачи
    $tasks = [
        ['id' => 301, 'link' => 'https://example.com/task/301'],
        ['id' => 302, 'link' => 'https://example.com/task/302'],
        ['id' => 303, 'link' => 'https://example.com/task/303'],
        ['id' => 304, 'link' => 'https://example.com/task/304'],
        ['id' => 305, 'link' => 'https://example.com/task/305']
    ];

    // Генерируем случайные даты в диапазоне 2024-2025 годов
    $startDate = new DateTime('2024-01-01');
    $endDate = new DateTime('2025-12-31');
    $startTimestamp = $startDate->getTimestamp();
    $endTimestamp = $endDate->getTimestamp();

    // Создаем 30 тестовых записей
    $createdCount = 0;

    for ($i = 0; $i < 30; $i++) {
        // Выбираем случайные значения из массивов
        $user = $users[array_rand($users)];
        $operation = $operations[array_rand($operations)];
        $detail = $details[array_rand($details)];
        $deal = $deals[array_rand($deals)];
        $task = $tasks[array_rand($tasks)];

        // Генерируем случайные значения для количества и цены
        $quantity = rand(1, 100);
        $price = rand(500, 10000);

        // Создаем случайную дату
        $randomTimestamp = rand($startTimestamp, $endTimestamp);
        $randomDate = new DateTime();
        $randomDate->setTimestamp($randomTimestamp);

        // Создаем запись в журнале
        $operationLog = new \App\Entities\OperationLog(
            $task['link'],
            $task['id'],
            $deal,
            $detail['id'],
            $detail['name'],
            $quantity,
            $user['name'],
            $user['id'],
            $price,
            $operation
        );

        // Устанавливаем случайную дату создания
        $reflection = new ReflectionClass($operationLog);
        $createdDateProperty = $reflection->getProperty('createdDate');
        $createdDateProperty->setAccessible(true);
        $createdDateProperty->setValue($operationLog, $randomDate);

        $entityManager->persist($operationLog);
        $createdCount++;
    }

    $entityManager->flush();

    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => "Создано $createdCount тестовых записей в журнале операций"
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/test', function (Request $request, Response $response, $args) use ($container) {
//    $entityManager = $container->get(EntityManagerInterface::class);
//    $nextStage = $entityManager->getRepository(ProductionSchemeStage::class)
//        ->findOneBy(['productPart' => 1, 'stageNumber' => 2]);
//
//    $response->getBody()->write(json_encode($nextStage->toArray()));
//    return $response;

    $cRestService = $container->get(CRestService::class);
    $result = $cRestService->callMethod('event.get');
    $response->getBody()->write(json_encode($result));
    return $response;

    $cRestService = $container->get(CRestService::class);
    $entityManager = $container->get(EntityManagerInterface::class);
    $currentUser = $cRestService->currentUser();

    $updateTaskResult = $cRestService->updateTask([
       'taskId' => 51,
        'fields' => [
            'RESPONSIBLE_ID' => 11
        ]
    ]);



    $response->getBody()->write(json_encode($updateTaskResult));
    return $response;

    $newStage = new BitrixGroupKanbanStage(
        5,
        '123',
        'test',
    );

    $entityManager->persist($newStage);
    $entityManager->flush();

    $stages = $cRestService->groupKanbanStages(5);
    $response->getBody()->write(json_encode($newStage));
    return $response;
    // Сохраним связь в бд
    foreach ($stages as $stage) {
        $bitrixGroupKanbanRepository = $entityManager->getRepository(BitrixGroupKanbanStage::class);
        $isStageExists = $bitrixGroupKanbanRepository->findOneBy(['bitrix_group_id' => 5]);
        if (!$isStageExists) {
            $newStage = new BitrixGroupKanbanStage(
                5,
                $stage['id'],
                $stage['title'],
            );
            $entityManager->persist($newStage);
        }
    }

    $entityManager->flush();

//    $result = $cRestService->addTask([
//        'fields' => [
//            'TITLE' => 'Задача с бека юзер технолог', // Название задачи
//            //'DEADLINE' => '2023-12-31T23:59:59', // Крайний срок
//            'CREATED_BY' => $currentUser['ID'], // Идентификатор постановщика
//            'RESPONSIBLE_ID' => 11, // Идентификатор исполнителя
//            // Пример передачи нескольких значений в поле UF_CRM_TASK
//            'UF_CRM_TASK' => [
//                'D_' . '7'// Привязка к сделке
//            ],
//        ]
//    ]);
    $response->getBody()->write(json_encode($stages));
    return $response;
});

$app->get('/', function (Request $request, Response $response, $args) use ($container) {

    $response->getBody()->write('Application');
    return $response;
});

$app->run();
