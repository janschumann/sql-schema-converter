<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 03.06.18
 * Time: 11:58
 */

namespace SchumannIt\Tests\DBAL\Schema\Converter;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use SchumannIt\DBAL\Schema\Converter\ConverterChain;
use PHPUnit\Framework\TestCase;
use SchumannIt\DBAL\Schema\Converter\CopyConverter;
use SchumannIt\DBAL\Schema\Converter\DoctrineConverter;
use SchumannIt\DBAL\Schema\Converter\EnsureAutoIncrementPrimaryKeyConverter;
use SchumannIt\DBAL\Schema\Converter\RenamePrimaryKeyIfSingleColumnIndex;
use SchumannIt\DBAL\Schema\Mapping;

class ConverterChainTest extends TestCase
{

    public function test()
    {
        $chain = new ConverterChain($this->createMock(Mapping::class));
        $chain->add(new CopyConverter());
        $chain->add(new DoctrineConverter());
        $chain->add(new EnsureAutoIncrementPrimaryKeyConverter());

        $table = new Table('CamelCase', [
            new Column('CamelCase', Type::getType('integer')),
            new Column('FOOBar', Type::getType('integer')),
            new Column('fooID', Type::getType('integer')),
            new Column('already_underscored', Type::getType('integer')),
        ]);
        $schema = new Schema([$table]);

        foreach ($chain as $key => $converter) {
            $schema->visit($converter);
            $schema = $converter->getResult();
        }

        $table = $schema->getTable('camel_case');
        $columns = $table->getColumns();

        $this->assertArrayHasKey('camel_case', $columns);
        $this->assertArrayHasKey('foo_bar', $columns);
        $this->assertArrayHasKey('foo_id', $columns);
        $this->assertArrayHasKey('already_underscored', $columns);

        $indexColumnNames = $table->getPrimaryKey()->getColumns();
        $indexColumnName = reset($indexColumnNames);
        $indexColumn = $table->getColumn($indexColumnName);

        $this->assertEquals('id', $indexColumn->getName());
        $this->assertTrue($indexColumn->getAutoincrement());
        $this->assertTrue($indexColumn->getNotnull());

        $this->assertEquals(3, $chain->count());
    }
}
