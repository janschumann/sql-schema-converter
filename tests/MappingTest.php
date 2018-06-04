<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 04.06.18
 * Time: 10:39
 */

namespace SchumannIt\Tests\DBAL\Schema;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use SchumannIt\DBAL\Schema\Converter\ConverterChain;
use SchumannIt\DBAL\Schema\Converter\CopyConverter;
use SchumannIt\DBAL\Schema\Converter\DoctrineConverter;
use SchumannIt\DBAL\Schema\Converter\EnsureAutoIncrementPrimaryKeyConverter;
use SchumannIt\DBAL\Schema\Converter\RenamePrimaryKeyIfSingleColumnIndex;
use SchumannIt\DBAL\Schema\Mapping;
use PHPUnit\Framework\TestCase;

class MappingTest extends TestCase
{
    public function test()
    {
        $mapping = new Mapping();
        $chain = new ConverterChain($mapping);
        $chain->add(new CopyConverter());
        $chain->add(new DoctrineConverter());
        $chain->add(new EnsureAutoIncrementPrimaryKeyConverter());
        $chain->add(new RenamePrimaryKeyIfSingleColumnIndex());

        $table = new Table('TableCamelCase', [
            new Column('FieldCamelCase', Type::getType('integer')),
            new Column('FOOBarField', Type::getType('integer')),
            new Column('fieldID', Type::getType('integer')),
            new Column('field_already_underscored', Type::getType('integer')),
        ]);
        $table->setPrimaryKey(['fieldID']);
        $schema = new Schema([$table]);

        foreach ($chain as $key => $converter) {
            $schema->visit($converter);
            $schema = $converter->getResult();
        }

        $this->assertEquals('table_camel_case', $mapping->getTableName('TableCamelCase'));
        $this->assertEquals('field_camel_case', $mapping->getColumnName('TableCamelCase', 'FieldCamelCase'));
        $this->assertEquals('foo_bar_field', $mapping->getColumnName('TableCamelCase', 'FOOBarField'));
        $this->assertEquals('id', $mapping->getColumnName('TableCamelCase', 'fieldID'));
        $this->assertEquals('field_already_underscored', $mapping->getColumnName('TableCamelCase', 'field_already_underscored'));
    }

    public function testThrowsOnNonExistentTable()
    {
        $mapping = new Mapping();

        $this->expectException(\LogicException::class);
        $mapping->getTableName('does_not_exists');
    }

    public function testThrowsOnExistentTableAndNonExsitentColumn()
    {
        $mapping = new Mapping();
        $mapping->addConverter(new CopyConverter());
        $mapping->setTableMapping('foo', 'foo');
        $mapping->setColumnMapping('foo', 'foo', 'bar');
        $mapping->resolve();

        $this->expectException(\LogicException::class);
        $mapping->getColumnName('foo', 'does_not_exists');
    }

    public function testThrowsOnNonExistentTableAndNonExsitentColumn()
    {
        $mapping = new Mapping();
        $mapping->addConverter(new CopyConverter());
        $mapping->setTableMapping('foo', 'foo');
        $mapping->setColumnMapping('foo', 'foo', 'bar');
        $mapping->resolve();

        $this->expectException(\LogicException::class);
        $mapping->getColumnName('does_not_exists', 'does_not_exists');
    }
}
