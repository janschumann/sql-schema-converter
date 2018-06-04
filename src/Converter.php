<?php

namespace SchumannIt\DBAL\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Visitor\Visitor as DoctrineSchemaVisitor;

interface Converter extends DoctrineSchemaVisitor
{
    /**
     * @return Schema
     */
    public function getResult();

    public function setSchemaMapping(Mapping $mapping);

    public function setTableMapping(string $oldName, string $newName);

    public function setColumnMapping(string $tableName, string $oldName, string $newName);

}
