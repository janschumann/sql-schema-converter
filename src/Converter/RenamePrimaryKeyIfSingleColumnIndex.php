<?php

namespace SchumannIt\DBAL\Schema\Converter;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

class RenamePrimaryKeyIfSingleColumnIndex extends CopyConverter
{
    /**
     * @param Table $table
     * @param Index $index
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function acceptIndex(Table $table, Index $index)
    {
        if ($index->isPrimary() && 1 === count($index->getColumns()) ) {
            $columns = $index->getColumns();
            $column = $this->currentTable->getColumn(reset($columns));
            $options = $column->toArray();
            unset($options['name']);
            $this->currentTable->dropColumn($column->getName());
            $this->currentTable->addColumn('id', $options['type']->getName(), $options);
            $this->currentTable->setPrimaryKey(['id']);

            $oldTableName = $table->getName();
            $oldColumnName = $column->getName();
            $newColumnName = 'id';

            $this->setColumnMapping($oldTableName, $oldColumnName, $newColumnName);
        }
    }
}
