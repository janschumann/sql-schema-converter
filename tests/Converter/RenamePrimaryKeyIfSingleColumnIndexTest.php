<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 03.06.18
 * Time: 18:31
 */

namespace SchumannIt\Tests\DBAL\Schema\Converter;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use SchumannIt\DBAL\Schema\Converter\RenamePrimaryKeyIfSingleColumnIndex;

class RenamePrimaryKeyIfSingleColumnIndexTest extends TestCase
{

    public function test()
    {
        $table = new Table('foo', [
            new Column('fooID', Type::getType('integer')),
        ]);
        $table->setPrimaryKey(['fooID']);
        $sourceSchema = new Schema([$table]);

        $converter = new RenamePrimaryKeyIfSingleColumnIndex();

        $sourceSchema->visit($converter);

        $columns = $converter->getResult()->getTable('foo')->getColumns();

        $this->assertEquals(1, count($columns));
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayNotHasKey('fooID', $columns);

    }
}
