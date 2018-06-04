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
use SchumannIt\DBAL\Schema\Mapping;

/**
 * Copys a schema by visiting schema, tables, columns and indices
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
    /**
     * @var Mapping
     */
    protected $mapping;

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

    public function setSchemaMapping(Mapping $mapping)
    {
        $this->mapping = $mapping;
        $this->mapping->addConverter($this);
    }

    public function setTableMapping(string $oldName, string $newName)
    {
        if (!is_null($this->mapping)) {
            $this->mapping->setTableMapping($oldName, $newName);
        }
    }

    public function setColumnMapping(string $tableName, string $oldName, string $newName)
    {
        if (!is_null($this->mapping)) {
            $this->mapping->setColumnMapping($tableName, $oldName, $newName);
        }
    }

    public function acceptSchema(Schema $schema)
    {
        $this->sourceSchema = $schema;
    }

    public function acceptTable(Table $table)
    {
        $this->currentTable = $this->targetSchema->createTable($table->getName());
        $this->setTableMapping($table->getName(), $table->getName());
    }

    public function acceptColumn(Table $table, Column $column)
    {
        $options = $column->toArray();
        unset($options['name']);
        $this->currentTable->addColumn($column->getName(), $options['type']->getName(), $options);
        $this->setColumnMapping($table->getName(), $column->getName(), $column->getName());
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
