<?php

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
use App\Settings\Settings;
use App\Settings\SettingsInterface;
use App\Middlewares\{
    JsonResponseMiddleware,
    CorsResponseMiddleware
};
use App\Controllers\{
    DashboardController,
    InstallB24AppController,
    UsersController,
    ProductPartsController,
    DealsController,
    OperationTypesController,
    ProductProductionStagesController
};
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

require __DIR__ . '/../vendor/autoload.php';

$container = new DI\Container([
    SettingsInterface::class => function () {
        return new Settings(require_once __DIR__ . '/../config/settings.php');
    },
    CRestService::class => DI\factory(function () {
        return new CRestService();
    }),
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
    $group->any('', [DashboardController::class, 'dashboard'])->setName('dashboard');
    $group->any('deal/', [DashboardController::class, 'deal'])->setName('deal');
    $group->any('install/', [InstallB24AppController::class, 'install'])->setName('install-as-b24-app');
});

$app->group('/api/', function (RouteCollectorProxy $group) {
//    $group->any('deals', [DealsController::class, 'list'])->setName('deals-list');
    $group->any('deals/{id}', [DealsController::class, 'get'])->setName('deal-resource');

    $group->any('users', [UsersController::class, 'list'])->setName('users-list');
    $group->any('users/{id}', [UsersController::class, 'get'])->setName('user-resource');

    $group->any('products', [ProductPartsController::class, 'list'])->setName('products-list');
    $group->any('products/{id}', [ProductPartsController::class, 'get'])->setName('product-resource');
    $group->any('products/import/', [ProductPartsController::class, 'import'])->setName('import-products-from-b24');

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
});


$app->get('/', function (Request $request, Response $response, $args) use ($container) {
    $response->getBody()->write('Application');
    return $response;
});

$app->run();
