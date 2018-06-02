<?php

namespace SchemaConverter;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Visitor\Visitor as SchemaVisitor;

/**
 * This converter only migrates the schema to the target platform
 *
 * @package SchemaConverter
 */
class CreateTargetSchemaConverter implements ConverterInterface
{
    /**
     * @var Schema
     */
    protected $targetSchema;
    /**
     * @var Schema
     */
    protected $sourceSchema;
    /**
     * @var Table
     */
    protected $currentTable;

    public function setTargetSchema(Schema $schema)
    {
        $this->ensureSchemaState($schema, ConverterInterface::SCHEMA_STATE_EMPTY, "Target schema is not empty.");
        $this->targetSchema = $schema;
    }

    public function acceptSchema(Schema $schema)
    {
        $this->ensureSchemaState($schema, ConverterInterface::SCHEMA_STATE_NOT_EMPTY, "Source schema is empty.");
        $this->sourceSchema = $schema;
    }

    /**
     * @param Schema
     * @param string enum(ConverterInterface::SCHEMA_STATE_EMPTY,ConverterInterface::SCHEMA_STATE_NOT_EMPTY,ConverterInterface::SCHEMA_STATE_NOT_NULL)
     * @param string
     */
    private function ensureSchemaState(Schema $schema, string $state, string $message) {
        if ($state === ConverterInterface::SCHEMA_STATE_NOT_NULL && is_null($schema)) {
            throw new \LogicException($message);
        }

        $count = $schema->getTables();
        switch ($state) {
            case ConverterInterface::SCHEMA_STATE_EMPTY:
                if (0 === $count) throw new \LogicException($message);
                break;
            case ConverterInterface::SCHEMA_STATE_NOT_EMPTY:
                if (0 !== $count) throw new \LogicException($message);
                break;
            default:
                throw new \LogicException("Unknown schema state " . $state);
        }
    }

    private function ensureCurrentTable()
    {
        if (is_null($this->currentTable)) {
            throw new \LogicException('Please start visiting on schema level.');
        }
    }

    private function ensureVisitorOrder(bool $table = true)
    {
        $this->ensureSchemaState($this->sourceSchema, ConverterInterface::SCHEMA_STATE_NOT_NULL, 'Please start visiting on schema level.');
        if ($table) {
            $this->ensureCurrentTable();
        }
    }

    public function acceptTable(Table $table)
    {
        $this->ensureVisitorOrder(false);
        $this->currentTable = $this->targetSchema->createTable($table->getName());
    }

    public function acceptColumn(Table $table, Column $column)
    {
        $this->ensureVisitorOrder();

        $options = $column->toArray();
        unset($options['name']);
        $this->currentTable->addColumn($column->getName(), $options['type']->getName(), $options);
    }

    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
    }

    public function acceptIndex(Table $table, Index $index)
    {
        $this->ensureVisitorOrder();

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
