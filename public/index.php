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
use App\Services\ProductStoresAndDocumentsService;
use App\Settings\Settings;
use App\Settings\SettingsInterface;
use App\Middlewares\{
    JsonResponseMiddleware,
    CorsResponseMiddleware,
    AuthMiddleware
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
    ReportsController,
    VirtualPartsController
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
    ProductionSchemeService::class => DI\factory(function (CRestService $CRestService, EntityManagerInterface $entityManager, SettingsInterface $settings, ProductStoresAndDocumentsService $productStoresAndDocumentsService) {
        return new ProductionSchemeService($CRestService, $entityManager, $settings, $productStoresAndDocumentsService);
    }, [
        'cache' => false
    ]),
    ProductStoresAndDocumentsService::class => DI\factory(function (CRestService $CRestService, EntityManagerInterface $entityManager, SettingsInterface $settings) {
        return new ProductStoresAndDocumentsService($CRestService, $entityManager, $settings);
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


if ($container->get(SettingsInterface::class)->isProduction()) {
    $app->setBasePath('/production-app/public');
}

// Routes
$app->group('/app/', function (RouteCollectorProxy $group) {
    $group->any('', [AppController::class, 'dashboard'])->setName('dashboard');
    $group->any('deal-production-scheme/', [AppController::class, 'dealProductionScheme'])->setName('deal-production-scheme');
    $group->any('install/', [InstallB24AppController::class, 'install'])->setName('install-as-b24-app');
});

$app->group('/api/', function (RouteCollectorProxy $group) use ($container) {

    // $group->any('deals', [DealsController::class, 'list'])->setName('deals-list');
    $group->any('deals/{id}', [DealsController::class, 'get'])->setName('deal-resource');

    $group->any('users', [UsersController::class, 'list'])->setName('users-list');
    $group->get('users/me', [UsersController::class, 'me'])->setName('current-user');
    $group->any('users/{id}', [UsersController::class, 'get'])->setName('user-resource');
    $group->get('system-user', [UsersController::class, 'getSystemUser'])->setName('get-system-user-id'); // Получает id системного пользователя

    $group->get('groups', [GroupsController::class, 'list'])->setName('b24-groups-list');

    // Функциональные маршруты только для технологов
    $group->group('', function (RouteCollectorProxy $group) use ($container) {
        $group->get('production-schemes/{id}', [ProductionSchemesController::class, 'get'])->setName('get-deal-production-scheme');
        $group->post('production-schemes', [ProductionSchemesController::class, 'store'])->setName('deal-production-scheme-resource');
        $group->patch('production-schemes/{id}', [ProductionSchemesController::class, 'update'])->setName('update-deal-production-scheme');
        $group->get('production-schemes/{id}/sync', [ProductionSchemesController::class, 'sync'])->setName('sync-scheme-and-stages-statuses');
        $group->get('production-schemes/{id}/virtual-parts', [ProductionSchemesController::class, 'virtualParts'])->setName('get-deal-production-scheme-virtual-parts');
    
        $group->get('product-parts', [ProductPartsController::class, 'list'])->setName('products-parts-list');
        $group->get('product-parts/{id}', [ProductPartsController::class, 'get'])->setName('product-parts-resource');
        $group->delete('product-parts/{id}', [ProductPartsController::class, 'delete'])->setName('delete-product-parts-resource');
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
    
        $group->get('virtual-parts', [VirtualPartsController::class, 'list']);
        $group->get('virtual-parts/{id}', [VirtualPartsController::class, 'get']);
        $group->post('virtual-parts', [VirtualPartsController::class, 'create']);
        $group->put('virtual-parts/{id}', [VirtualPartsController::class, 'update']);
        $group->delete('virtual-parts/{id}', [VirtualPartsController::class, 'delete']);
        $group->any('virtual-parts/import/', [VirtualPartsController::class, 'import']);
    
        // Отчеты
        $group->get('reports/employee-operations', [ReportsController::class, 'employeeOperations'])->setName('employee-operations-report');
        $group->get('reports/operation-users', [ReportsController::class, 'getOperationUsers'])->setName('operation-users-report');
    
    })->add(AuthMiddleware::class);
 
    // for testing purposes
    // $group->get('update-scheme-stages-manually/{taskId}', [ProductionSchemesController::class, 'updateSchemeStagesManually'])->setName('update-scheme-stages-manually');
});

$app->get('/', function (Request $request, Response $response, $args) use ($container) {

    $response->getBody()->write('Application');
    return $response;
});

$app->run();
