<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 04.06.18
 * Time: 12:43
 */

namespace SchumannIt\Tests\DBAL\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use SchumannIt\DBAL\Schema\Converter\CopyConverter;
use SchumannIt\DBAL\Schema\Converter\DoctrineConverter;
use SchumannIt\DBAL\Schema\Mapping;
use SchumannIt\DBAL\Schema\Migration;
use PHPUnit\Framework\TestCase;
use SchumannIt\DBAL\Schema\Converter\ConverterChain;

class MigrateDataTest extends TestCase
{
    public function test()
    {
        $column = new Column('indexColumn', Type::getType('integer'));
        $table = new Table('foo', [$column]);
        $table->addIndex(['indexColumn']);

        $sourceConnection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('createSchema')->willReturn(new Schema([$table]));
        $platform = $this->createMock(AbstractPlatform::class);
        $schemaManager->method('getDatabasePlatform')->willReturn($platform);
        $sourceConnection->method('getSchemaManager')->willReturn($schemaManager);
        $stmt = $this->createMock(Statement::class);
        $this->count = 0;
        $stmt->method('fetchAll')->willReturn([['indexColumn' => 1]]);
        $sourceConnection->method('query')->willReturn($stmt);

        $targetConnection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('createSchema')->willReturn(new Schema([$table]));
        $platform = $this->createMock(AbstractPlatform::class);
        $schemaManager->method('getDatabasePlatform')->willReturn($platform);
        $targetConnection->method('getSchemaManager')->willReturn($schemaManager);
        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())->method('bindValue')->with('indexColumn', 1, 1);
        $targetConnection->method('prepare')->willReturn($stmt);

        $mapping = new Mapping();
        $chain = new ConverterChain($mapping);
        $chain->add(new CopyConverter());
        $mig = new Migration($sourceConnection, $targetConnection, $chain);

        $mig->migrateData();
    }
}
