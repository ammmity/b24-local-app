<?php

use App\Entities\BitrixGroupKanbanStage;
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
    ProductProductionStagesController,
    ProductionSchemesController,
    GroupsController
};
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

require __DIR__ . '/../vendor/autoload.php';

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
});

$app->get('/test', function (Request $request, Response $response, $args) use ($container) {
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
