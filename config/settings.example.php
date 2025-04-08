<?php

return [
    'db' => [
        'dbname' => '',
        'user' => '',
        'password' => '',
        'host' => '',
        'driver' => '',
        'port' => '',
    ],
    'b24' => [
        'appId' => '',
        'appKey' => '',
        'PRODUCTS_CATALOG_IBLOCK_ID' => 15,
        'PRODUCTS_CATALOG_SECTION_ID' => 11,
        'PRODUCT_PARTS_CATALOG_IBLOCK_ID' => 15,
        'PRODUCT_PARTS_CATALOG_SECTION_ID' => 9,
        'VIRTUAL_PARTS_CATALOG_IBLOCK_ID' => 15,
        'VIRTUAL_PARTS_CATALOG_SECTION_ID' => 15,
        'PRODUCT_PARTS_PROP_ID' => 53, // ID Свойства с привязкой деталей к товару
        'TEHNOLOG_DEPARTMENT_ID' => 16,
        'IS_CATALOG_DOCUMENT_MODE_ENABLED' => true,
        'PRODUCTION_STORE_ID' => 1,
        'DETAILS_STORE_ID' => 2,
        'VIRTUAL_STORE_ID' => 3,
        'SYSTEM_USER_ID' => 1, // ID системного пользователя, для инициализации задач без исполнителя
    ],
    'environment' => 'dev' // prod
];
