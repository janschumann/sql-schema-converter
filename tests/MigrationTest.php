<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 03.06.18
 * Time: 11:56
 */

namespace SchumannIt\Tests\DBAL\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\MockObject\MockObject;
use SchumannIt\DBAL\Schema\Converter;
use SchumannIt\DBAL\Schema\Converter\CopyConverter;
use SchumannIt\DBAL\Schema\Converter\DoctrineConverter;
use SchumannIt\DBAL\Schema\Migration;
use PHPUnit\Framework\TestCase;
use SchumannIt\DBAL\Schema\Converter\ConverterChain;

class MigrationTest extends TestCase
{
    /**
     * @var Connection
     */
    private $sourceConnection;
    /**
     * @var Connection
     */
    private $targetConnection;
    /**
     * @var ConverterChain
     */
    private $converterChain;

    public function setUp()
    {
        $this->sourceConnection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())->method('visit');
        $schemaManager->method('createSchema')->willReturn($schema);
        $this->sourceConnection->method('getSchemaManager')->willReturn($schemaManager);

        $this->targetConnection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        // mock the target platform to return a create table statement
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('getCreateTableSQL')->willReturn(['CREATE TABLE foo']);
        $schemaManager->method('getDatabasePlatform')->willReturn($platform);
        // mock the target schema, which should be empty ba default
        $schema = $this->createMock(Schema::class);
        $schema->method('getNamespaces')->willReturn([]);
        $schema->method('getTables')->willReturn([]);
        $schema->method('getSequences')->willReturn([]);
        $schemaManager->method('createSchema')->willReturn($schema);
        $this->targetConnection->method('getSchemaManager')->willReturn($schemaManager);

        $this->converterChain = $this->converterChainMock();
    }

    /**
     * @param array $items
     *
     * @return MockObject
     */
    private function converterChainMock()
    {
        $someIterator = $this->createMock(ConverterChain::class);
        $converter = $this->createMock(Converter::class);
        $schema = $this->createMock(Schema::class);
        // mock the resulting schema to add one table, so we get a diff
        $schema->method('getNamespaces')->willReturn([]);
        $table = $this->createMock(Table::class);
        $table->method('getForeignKeys')->willReturn([]);
        $schema->method('getTable')->willReturn($table);
        $table = $this->createMock(Table::class);
        $table->method('getForeignKeys')->willReturn([]);
        $schema->method('getTables')->willReturn([$table]);
        $schema->method('getSequences')->willReturn([]);
        $converter->method('getResult')->willReturn($schema);
        $iterator = new \ArrayIterator([$converter]);

        $someIterator
            ->expects($this->any())
            ->method('rewind')
            ->willReturnCallback(function () use ($iterator) {
                $iterator->rewind();
            })
        ;

        $someIterator
            ->expects($this->any())
            ->method('current')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->current();
            })
        ;

        $someIterator
            ->expects($this->any())
            ->method('key')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->key();

            })
        ;

        $someIterator
            ->expects($this->any())
            ->method('next')
            ->willReturnCallback(function () use ($iterator) {
                $iterator->next();
            })
        ;

        $someIterator
            ->expects($this->any())
            ->method('valid')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->valid();
            })
        ;

        return $someIterator;
    }

    public function testConvertersAreAppliedOnConstruct()
    {
        new Migration($this->sourceConnection, $this->targetConnection, $this->converterChain);
    }

    public function testDiffIsCreatedInCorrecrOrder()
    {
        $migration = new Migration($this->sourceConnection, $this->targetConnection, $this->converterChain);

        $this->assertTrue($migration->hasChanges());
        $this->assertEquals(["CREATE TABLE foo"], $migration->getChangesSql());
    }

    public function testApplyChangesWorksOnTraget()
    {
        // exec should be called with a create table statement, as this is mocked in the target platform mock
        $this->targetConnection->expects($this->once())->method('exec')->with('CREATE TABLE foo');

        $migration = new Migration($this->sourceConnection, $this->targetConnection, $this->converterChain);
        $migration->applyChanges();
    }


}
