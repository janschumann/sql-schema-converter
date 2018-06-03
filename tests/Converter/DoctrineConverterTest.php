<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 03.06.18
 * Time: 11:58
 */

namespace SchumannIt\Tests\DBAL\Schema\Converter;

use SchumannIt\DBAL\Schema\Converter\DoctrineConverter;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;


class DoctrineConverterTest extends TestCase
{
    public function testTableNameIsChanged()
    {
        $sourceSchema = new Schema([
            new Table('CamelCase'),
            new Table('FOOBar'),
            new Table('already_underscored')
        ]);

        $converter = new DoctrineConverter();

        $sourceSchema->visit($converter);

        $result = $converter->getResult();

        $this->assertEquals('camel_case', $result->getTable('camel_case')->getName());
        $this->assertEquals('foo_bar', $result->getTable('foo_bar')->getName());
        $this->assertEquals('already_underscored', $result->getTable('already_underscored')->getName());
    }

    public function testColumnNameIsChanged()
    {
        $table = new Table('foo', [
            new Column('CamelCase', Type::getType('integer')),
            new Column('FOOBar', Type::getType('integer')),
            new Column('fooID', Type::getType('integer')),
            new Column('already_underscored', Type::getType('integer')),
        ]);
        $sourceSchema = new Schema([$table]);

        $converter = new DoctrineConverter();

        $sourceSchema->visit($converter);

        $columns = $converter->getResult()->getTable('foo')->getColumns();

        $this->assertArrayHasKey('camel_case', $columns);
        $this->assertArrayHasKey('foo_bar', $columns);
        $this->assertArrayHasKey('foo_id', $columns);
        $this->assertArrayHasKey('already_underscored', $columns);
    }

    public function testIndexColumnNamesAreChanged()
    {
        $table = new Table('foo', [
            new Column('fooID', Type::getType('integer')),
        ]);
        $table->setPrimaryKey(['fooID']);
        $sourceSchema = new Schema([$table]);

        $converter = new DoctrineConverter();

        $sourceSchema->visit($converter);

        $table = $converter->getResult()->getTable('foo');

        $columns = $table->getColumns();
        $this->assertArrayHasKey('foo_id', $columns);

        $columns = $table->getPrimaryKey()->getColumns();
        $this->assertArrayHasKey('foo_id', array_flip($columns));
    }
}
