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
}
