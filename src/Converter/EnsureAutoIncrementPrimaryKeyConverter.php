<?php

namespace SchumannIt\DBAL\Schema\Converter;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

class EnsureAutoIncrementPrimaryKeyConverter extends CopyConverter
{
    public function acceptTable(Table $table)
    {
        parent::acceptTable($table);

        // add primary key column and index
        if (!$table->hasPrimaryKey()) {
            $this->currentTable->addColumn('id', 'integer', array(
                'Notnull' => true,
                'Autoincrement' => true
            ));
            $this->currentTable->setPrimaryKey(array('id'));
        }
    }
}
