<?php
namespace App\Controllers;

use App\Entities\OperationType;
use App\Services\CRestService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class OperationTypesController {
    /**
     * @throws \Exception
     */
    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager
    )
    {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $operationTypes = $this->entityManager->getRepository(OperationType::class)->findAll();

        if (!empty($operationTypes)) {
            $operationTypes = array_map(fn($operationType) => [
                'id' => $operationType->getId(),
                'name' => $operationType->getName(),
                'machine' => $operationType->getMachine(),
            ], $operationTypes);
        }

        $response->getBody()->write(json_encode($operationTypes));

        return $response;
    }
}
