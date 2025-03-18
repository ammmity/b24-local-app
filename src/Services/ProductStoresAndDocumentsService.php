<?php
namespace App\Services;

use App\Services\CRestService;
use App\Settings\SettingsInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProductStoresAndDocumentsService
{

    public function __construct(
        protected CRestService $CRestService,
        protected EntityManagerInterface $entityManager,
        protected SettingsInterface $settings
    )
    {}

    // Включен ли складской учет
    protected function isDocumentModeEnabled(): bool
    {
        return $this->settings->get('b24')['IS_CATALOG_DOCUMENT_MODE_ENABLED'];
    }

    // Добавить товар на склад ( с проведением документа если включен складской учет )
    public function addProductRemains(int $catalogElementId, int $storeId, int $quantity)
    {
        if ($this->isDocumentModeEnabled()) {
            $title = 'Модуль производства: Внесение материала ';
            $comment = $title .= '('.$this->getStoreName($storeId).')';
            $documentId = $this->CRestService->addCatalogDocument($title, $comment);
            $this->CRestService->addElementToCatalogDocument($documentId, 0, $storeId, $catalogElementId, $quantity);

            return $this->CRestService->conductDocument($documentId);
        } else {
            // добавить quantity без проведения документа
        }
    }

    // Списывает товар со склада ( с проведением документа если включен складской учет )
    public function removeProductFromStore(int $catalogElementId, int $storeId, int $quantity)
    {
        if ($this->isDocumentModeEnabled()) {
            $title = 'Модуль производства: Удаление материала ';
            $comment = $title .= '('.$this->getStoreName($storeId).')';
            $documentId = $this->CRestService->addCatalogDocument($title, $comment, 'D');
            $this->CRestService->addElementToCatalogDocument($documentId, 0, $storeId, $catalogElementId, $quantity);

            return $this->CRestService->conductDocument($documentId);
        } else {
            // Уменьшить quantity без проведения документа
        }
    }

    protected function getStoreName(int $storeId): string
    {
        $storeNames = [
            $this->settings->get('b24')['PRODUCTION_STORE_ID'] => 'Склад производства',
            $this->settings->get('b24')['DETAILS_STORE_ID'] => 'Склад деталей',
            $this->settings->get('b24')['VIRTUAL_STORE_ID'] => 'Виртуальный склад'
        ];

        return $storeNames[$storeId] ?? 'Неизвестный склад';
    }
}
