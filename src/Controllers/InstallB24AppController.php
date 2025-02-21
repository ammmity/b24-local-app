<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use App\Crest\CRest;

class InstallB24AppController {

    /**
     * @throws \Exception
     */
    public function __construct(
        protected CRest $CRest
    )
    {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function install(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $install_result = $this->CRest::installApp($request);
        $view = Twig::fromRequest($request);
//        $response->getBody()->write(print_r((array)$request->getCookieParams(), true));
//        return $response;
        return $view->render($response, 'install-b24-app.html.twig', [
            'install_result' => $install_result
        ]);
    }
}
