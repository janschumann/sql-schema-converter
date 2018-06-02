<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 01.06.18
 * Time: 19:21
 */

namespace NEOEN\SchemaConverter;


use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

class CamelCaseToUnderscoreConverter implements ConverterInterface
{
    /**
     * @var Schema
     */
    protected $schema;

    public function acceptSchema(Schema $schema)
    {
        $this->ensureSchemaState($schema, ConverterInterface::SCHEMA_STATE_NOT_EMPTY, "Schema is empty.");
        $this->schema = $schema;
    }

    public function acceptTable(Table $table)
    {
        $this->ensureVisitorOrder();

        $name = $table->getName();
        $this->schema->renameTable($name, $this->underscore($name));
    }

    public function acceptColumn(Table $table, Column $column)
    {
        $this->ensureVisitorOrder();

        $options = $column->toArray();
        unset($options['name']);
        $table->dropColumn($column->getName());
        $table->addColumn($this->underscore($column->getName()), $options['type']->getName(), $options);
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
     * Convert field and table names to underscore.
     * Make sure that all input values have proper CamelCase
     * E.g.:
     *  - ID => Id
     *  - FOOBar => FooBar
     *
     * @param string
     *
     * @return string
     */
    private function underscore($name)
    {
        $name = preg_replace_callback(
            '/[A-Z][A-Z]+/',
            function ($matches) {
                if (strlen($matches[0]) === 2) {
                    return ucfirst(strtolower($matches[0]));
                } else {
                    $lastChar = substr($matches[0], strlen($matches[0]) - 1, 1);
                    $subject = substr($matches[0], 0, strlen($matches[0]) - 1);

                    return ucfirst(strtolower($subject)).$lastChar;
                }
            },
            $name
        );

        return Inflector::tableize($name);
    }

    /**
     * @param Schema
     * @param string enum(ConverterInterface::SCHEMA_STATE_EMPTY,ConverterInterface::SCHEMA_STATE_NOT_EMPTY,ConverterInterface::SCHEMA_STATE_NOT_NULL)
     * @param string
     */
    private function ensureSchemaState(Schema $schema, string $state, string $message)
    {
        if ($state === ConverterInterface::SCHEMA_STATE_NOT_NULL && is_null($schema)) {
            throw new \LogicException($message);
        }

        $count = $schema->getTables();
        switch ($state) {
            case ConverterInterface::SCHEMA_STATE_EMPTY:
                if (0 === $count) {
                    throw new \LogicException($message);
                }
                break;
            case ConverterInterface::SCHEMA_STATE_NOT_EMPTY:
                if (0 !== $count) {
                    throw new \LogicException($message);
                }
                break;
            default:
                throw new \LogicException("Unknown schema state ".$state);
        }
    }

    private function ensureVisitorOrder()
    {
        $this->ensureSchemaState(
            $this->schema,
            ConverterInterface::SCHEMA_STATE_NOT_NULL,
            'Please start visiting on schema level.'
        );
    }
}
