<?php

namespace NEOEN\SchemaConverter;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Visitor\Visitor as SchemaVisitor;

interface ConverterInterface extends SchemaVisitor
{
    const SCHEMA_STATE_EMPTY = 'schema_empty';
    const SCHEMA_STATE_NOT_EMPTY = 'schema_not_empty';
    const SCHEMA_STATE_NOT_NULL = 'schema_not_null';
    const TARGET_TABLE_STATE_CREATED = 'table_created';
}
