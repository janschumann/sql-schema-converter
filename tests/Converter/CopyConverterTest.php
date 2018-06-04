<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 03.06.18
 * Time: 11:57
 */

use SchumannIt\DBAL\Schema\Converter\CopyConverter;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

class CopyConverterTest extends TestCase
{
    public function testTablesAreCopiedUnchanged()
    {
        $table = new Table('foo');
        $sourceSchema = new Schema([$table]);

        $converter = new CopyConverter();

        $sourceSchema->visit($converter);

        $this->assertEquals($sourceSchema, $converter->getResult());
    }

    public function testColumnsAreCopiedUnchanged()
    {
        $column = new Column('bar', Type::getType('integer'));
        $table = new Table('foo', [$column]);
        $sourceSchema = new Schema([$table]);

        $converter = new CopyConverter();

        $sourceSchema->visit($converter);

        $this->assertEquals($sourceSchema, $converter->getResult());
    }

    public function testPrimaryKeyIsCopiedUnchanged()
    {
        $column = new Column('id', Type::getType('integer'));
        $column->setAutoincrement(true);
        $table = new Table('foo', [$column]);
        $table->setPrimaryKey(['id']);
        $sourceSchema = new Schema([$table]);

        $converter = new CopyConverter();

        $sourceSchema->visit($converter);

        $this->assertEquals($sourceSchema, $converter->getResult());
    }

    public function testUniquIndexIsCopiedUnchanged()
    {
        $column = new Column('indexColumn', Type::getType('integer'));
        $table = new Table('foo', [$column]);
        $table->addUniqueIndex(['indexColumn']);
        $sourceSchema = new Schema([$table]);

        $converter = new CopyConverter();

        $sourceSchema->visit($converter);

        $this->assertEquals($sourceSchema, $converter->getResult());
    }

    public function testIndexIsCopiedUnchanged()
    {
        $column = new Column('indexColumn', Type::getType('integer'));
        $table = new Table('foo', [$column]);
        $table->addIndex(['indexColumn']);
        $sourceSchema = new Schema([$table]);

        $converter = new CopyConverter();

        $sourceSchema->visit($converter);

        $this->assertEquals($sourceSchema, $converter->getResult());
    }

    public function testIndexesAreLeftUnchangedUnchanged()
    {
        $table = new Table('foo', [
            new Column('fooID', Type::getType('integer')),
            new Column('barID', Type::getType('integer')),
        ]);
        $table->setPrimaryKey(['fooID', 'barID']);
        $sourceSchema = new Schema([$table]);

        $converter = new CopyConverter();

        $sourceSchema->visit($converter);

        $indexes = $converter->getResult()->getTable('foo')->getIndexes();
        $this->assertEquals(1, count($indexes));
    }

}
