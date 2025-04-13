<?php

namespace App\Middlewares;

use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class JsonResponseMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        if ($request->getHeaderLine('Content-Type') === 'application/json') {
            $response = $response->withHeader('Content-Type', 'application/json');
        }

        return $response;
    }
}
