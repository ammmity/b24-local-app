<?

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;

trait UnauthorizedResponseTrait
{
    private function unauthorizedResponse(): Response
    {
        $response = new \Slim\Psr7\Response(401);
        $response->getBody()->write(json_encode([
            'error' => 'Пользователь не авторизован, либо недостаточно прав'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
