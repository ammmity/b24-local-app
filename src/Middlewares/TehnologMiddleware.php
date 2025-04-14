<?

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class TehnologMiddleware implements MiddlewareInterface 
{
    use UnauthorizedResponseTrait;
    
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $isTehnlogUser = $request->getAttribute(AuthMiddleware::ATTR_IS_TEHNOLOG);
        if (!$isTehnlogUser) {
            return $this->unauthorizedResponse();
        }
        return $handler->handle($request);
    }
}
