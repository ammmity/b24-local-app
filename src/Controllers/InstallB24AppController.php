<?php
namespace App\Controllers;

use App\Services\CRestService;
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
        private CRestService $CRestService
    )
    {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function install(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $install_result = $this->CRestService->installApp($request);
        $view = Twig::fromRequest($request);
        
        return $view->render($response, 'install-b24-app.html.twig', [
            'install_result' => $install_result
        ]);
    }
}
