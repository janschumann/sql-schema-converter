<?php

namespace SchumannIt\DBAL\Schema\Converter;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use SchumannIt\DBAL\Schema\Converter;

/**
 * A .
 *
 * E.g. The Table representation normalizes column names in Table::dropColumn in a way that CamelCase coumns can
 * never be removed from a schema. This class fixes that by converting CamelCase to under_score names.
 */
class CopyConverter implements Converter
{
    /**
     * @var Schema
     */
    protected $sourceSchema;
    /**
     * @var Schema
     */
    protected $targetSchema;
    /**
     * @var Table
     */
    protected $currentTable;

    public function __construct()
    {
        $this->targetSchema = new Schema();
        $this->sourceSchema = new Schema();
    }

    /**
     * @return Schema
     */
    public function getResult()
    {
        return $this->targetSchema;
    }

    public function acceptSchema(Schema $schema)
    {
        $this->sourceSchema = $schema;
    }

    public function acceptTable(Table $table)
    {
        $this->currentTable = $this->targetSchema->createTable($table->getName());
    }

    public function acceptColumn(Table $table, Column $column)
    {
        $options = $column->toArray();
        unset($options['name']);
        $this->currentTable->addColumn($column->getName(), $options['type']->getName(), $options);
    }

    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
    }

    public function acceptIndex(Table $table, Index $index)
    {
        $columns = $index->getColumns();
        switch (true)
        {
            case $index->isPrimary():
                $this->currentTable->setPrimaryKey($columns);
                break;

            case $index->isUnique():
                $this->currentTable->addUniqueIndex($columns);
                break;

            default:
                $this->currentTable->addIndex($columns);
                break;
        }
    }

    public function acceptSequence(Sequence $sequence)
    {
    }
}
