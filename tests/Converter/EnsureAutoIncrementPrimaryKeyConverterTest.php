<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 03.06.18
 * Time: 11:59
 */

namespace SchumannIt\Tests\DBAL\Schema\Converter;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use SchumannIt\DBAL\Schema\Converter\EnsureAutoIncrementPrimaryKeyConverter;
use PHPUnit\Framework\TestCase;

class EnsureAutoIncrementPrimaryKeyConverterTest extends TestCase
{
    public function testPrimaryKeyIsAdded()
    {
        $sourceSchema = new Schema([new Table('FOOBar')]);

        $converter = new EnsureAutoIncrementPrimaryKeyConverter();

        $sourceSchema->visit($converter);

        $table = $converter->getResult()->getTable('FOOBar');
        $indexColumnNames = $table->getPrimaryKey()->getColumns();
        $indexColumnName = reset($indexColumnNames);
        $indexColumn = $table->getColumn($indexColumnName);

        $this->assertEquals('id', $indexColumn->getName());
        $this->assertTrue($indexColumn->getAutoincrement());
        $this->assertTrue($indexColumn->getNotnull());
    }

    public function testNoPrimaryKeyIsAdded()
    {
        $table = new Table('foo', [
            new Column('foo_id', Type::getType('integer')),
            new Column('bar_id', Type::getType('integer')),
        ]);
        $table->setPrimaryKey(['foo_id', 'bar_id']);
        $sourceSchema = new Schema([$table]);

        $converter = new EnsureAutoIncrementPrimaryKeyConverter();

        $sourceSchema->visit($converter);

        $table = $converter->getResult()->getTable('foo');
        $columns = $table->getColumns();
        $this->assertArrayHasKey('foo_id', $columns);
        $this->assertArrayHasKey('bar_id', $columns);
        $this->assertArrayNotHasKey('id', $columns);
        $indexColumnNames = $table->getPrimaryKey()->getColumns();
        $this->assertArrayHasKey('foo_id', array_flip($indexColumnNames));
        $this->assertArrayHasKey('bar_id', array_flip($indexColumnNames));
    }
}
