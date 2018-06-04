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

/**
 * This converter aims to convert a db schema so that the new schema filly supports doctrine schema representation.
 *
 * E.g. The Table representation normalizes column names in Table::dropColumn in a way that CamelCase coumns can
 * never be removed from a schema. This class fixes that by converting CamelCase to under_score names.
 *
 * We also create an autoincrement column id and set is as primary key, if no primary key exists
 */
class DoctrineConverter extends CopyConverter
{
    public function acceptTable(Table $table)
    {
        $oldTableName = $table->getName();
        $newTableName = $this->underscore($oldTableName);
        $this->currentTable = $this->targetSchema->createTable($newTableName);

        $this->setTableMapping($oldTableName, $newTableName);
    }

    public function acceptColumn(Table $table, Column $column)
    {
        $oldTableName = $table->getName();
        $oldColumnName = $column->getName();
        $newColumnName = $this->underscore($oldColumnName);

        $options = $column->toArray();
        unset($options['name']);
        $this->currentTable->addColumn($newColumnName, $options['type']->getName(), $options);

        $this->setColumnMapping($oldTableName, $oldColumnName, $newColumnName);
    }

    public function acceptIndex(Table $table, Index $index)
    {
        $columns = $index->getColumns();
        foreach ($columns as $i => $column) {
            $columns[$i] = $this->underscore($columns[$i]);
        }

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


    /**
     * Convert field and table names to underscore.
     * Makes sure that all input values have proper CamelCase
     * E.g.:
     *  - ID => Id
     *  - FOOBar => FooBar
     *
     * @param string $name
     * @return string
     */
    private function underscore(string $name)
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
}
