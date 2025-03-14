<?php

namespace App\Controllers;

use App\Entities\OperationLog;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DateTime;

class ReportsController 
{
    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {}

    public function employeeOperations(Request $request, Response $response): Response 
    {
        $queryParams = $request->getQueryParams();
        $startDate = new DateTime($queryParams['startDate'] ?? 'today');
        $endDate = new DateTime($queryParams['endDate'] ?? 'today');
        $userId = $queryParams['userId'] ?? null;

        // Получаем все операции за период
        $queryBuilder = $this->entityManager->getRepository(OperationLog::class)
            ->createQueryBuilder('o')
            ->where('o.createdDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate->format('Y-m-d 00:00:00'))
            ->setParameter('endDate', $endDate->format('Y-m-d 23:59:59'));

        if ($userId) {
            $queryBuilder->andWhere('o.userId = :userId')
                      ->setParameter('userId', $userId);
        }

        $operations = $queryBuilder->getQuery()->getResult();

        // Формируем отчет
        $report = [];
        foreach ($operations as $operation) {
            $report[] = [
                'date' => $operation->getCreatedDate()->format('Y-m-d H:i:s'),
                'employee' => $operation->getUsername(),
                'detail' => $operation->getDetailName(),
                'quantity' => $operation->getQuantity(),
                'price' => $operation->getPrice(),
                'amount' => $operation->getPrice() * $operation->getQuantity()
            ];
        }

        // Группировка по сотрудникам
        $summary = [];
        foreach ($report as $record) {
            if (!isset($summary[$record['employee']])) {
                $summary[$record['employee']] = 0;
            }
            $summary[$record['employee']] += $record['amount'];
        }

        $response->getBody()->write(json_encode([
            'details' => $report,
            'summary' => $summary
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getOperationUsers(Request $request, Response $response): Response 
    {
        $users = $this->entityManager->getRepository(OperationLog::class)
            ->createQueryBuilder('o')
            ->select('DISTINCT o.userId, o.username')
            ->getQuery()
            ->getResult();

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }
}