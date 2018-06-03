<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 03.06.18
 * Time: 11:59
 */

namespace SchumannIt\Tests\DBAL\Schema\Converter;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use SchumannIt\DBAL\Schema\Converter\EnsureAutoIncrementPrimaryKeyConverter;
use PHPUnit\Framework\TestCase;

class EnsureAutoIncrementPrimaryKeyConverterTest extends TestCase
{
    public function test()
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

}
