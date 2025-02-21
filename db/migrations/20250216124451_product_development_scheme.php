<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ProductDevelopmentScheme extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $stagesTable = $this->table('product_operation_types');
        $stagesTable
            ->addColumn('name', 'string')
            ->addColumn('machine', 'string')
            ->create();

//        $productPartsTable = $this->table('product_parts');
//        $productPartsTable
//            ->addColumn('name', 'string')
//            ->addColumn('xml_id', 'string')
//            ->addColumn('bitrix_id', 'integer')
//            ->create();

        $productProductionStages = $this->table('product_production_stages');
        $productProductionStages
            ->addColumn('product_part_id', 'integer')
            ->addColumn('operation_type_id', 'integer')
            ->addColumn('stage', 'integer')
            ->addColumn('created', 'datetime')
            ->addForeignKey('operation_type_id', 'product_operation_types', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('product_part_id', 'product_parts', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        $productOperationTypes = [
            [
                'name' => 'Раскрой',
                'machine' => 'Лазер',
            ],
            [
                'name' => 'Гильотина',
                'machine' => 'Гильотина',
            ],
            [
                'name' => 'Штамповка',
                'machine' => 'Штамповка',
            ],
            [
                'name' => 'Прокатка',
                'machine' => 'Прокатка',
            ],
            [
                'name' => 'Гибка',
                'machine' => 'Гибка',
            ],
            [
                'name' => 'Сварка',
                'machine' => 'Сварка',
            ],
            [
                'name' => 'Покраска',
                'machine' => 'Покраска',
            ],
            [
                'name' => 'Комплектация',
                'machine' => 'Комплектация',
            ],
            [
                'name' => 'Упаковка',
                'machine' => 'Упаковка',
            ],
            [
                'name' => 'Маркировка',
                'machine' => 'Маркировка',
            ],
        ];
        foreach ($productOperationTypes as $type) {
            $builder = $this->getQueryBuilder('insert');
            $builder
                ->insert(['name', 'machine'])
                ->into('product_operation_types')
                ->values($type)
                ->execute();
        }
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('product_production_stages')->drop();
        $this->table('product_operation_types')->drop();
    }
}
