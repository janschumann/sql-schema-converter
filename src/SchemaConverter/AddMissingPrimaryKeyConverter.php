<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 01.06.18
 * Time: 21:08
 */

namespace SchemaConverter;


use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

class AddMissingPrimaryKeyConverter implements ConverterInterface
{
    /**
     * @var Schema
     */
    protected $schema;

    public function acceptSchema(Schema $schema)
    {
        $this->ensureSchemaState($schema, ConverterInterface::SCHEMA_STATE_NOT_EMPTY, "Source schema is empty.");
        $this->schema = $schema;
    }

    public function acceptTable(Table $table)
    {
        $this->ensureVisitorOrder();

        // add primary key column and index
        if (!$table->hasPrimaryKey()) {
            $table->addColumn('id', 'integer', array(
                'Notnull' => true,
                'Autoincrement' => true
            ));
            $table->setPrimaryKey(array('id'));
        }
    }

    public function acceptColumn(Table $table, Column $column)
    {
    }

    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
    }

    public function acceptIndex(Table $table, Index $index)
    {
    }

    public function acceptSequence(Sequence $sequence)
    {
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

    private function ensureVisitorOrder()
    {
        $this->ensureSchemaState($this->schema, ConverterInterface::SCHEMA_STATE_NOT_NULL, 'Please start visiting on schema level.');
    }
}
