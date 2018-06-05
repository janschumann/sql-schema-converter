<?php
namespace SchumannIt\DBAL\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use SchumannIt\DBAL\Schema\Converter\ConverterChain;
use SchumannIt\DBAL\Schema\Converter\DoctrineConverter;

/**
 * Create and migrate a database
 */
class Migration
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
     * @var string
     */
    private $targetPlatform;
    /**
     * @var Schema
     */
    private $targetSchema;
    /**
     * @var array
     */
    private $changes;
    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * @param Connection $source
     * @param Connection $target
     * @param ConverterChain $converterChain
     */
    public function __construct(Connection $source, Connection $target, ConverterChain $converterChain)
    {
        $this->sourceConnection = $source;

        $this->targetConnection = $target;
        $this->targetPlatform = $this->targetConnection->getSchemaManager()->getDatabasePlatform();

        $this->convert($converterChain);
        $this->compare();
    }

    /**
     * Returns true if the target schema needs update
     *
     * @return bool
     */
    public function hasChanges()
    {
        return 0 < count($this->changes);
    }

    /**
     * Fetches the sql commands needed to sync target schema
     *
     * @return array
     */
    public function getChangesSql()
    {
        return $this->changes;
    }

    /**
     * Apply schema changes to the target db
     *
     * @throws ConnectionException
     * @throws DBALException
     */
    public function applyChanges()
    {
        $this->targetConnection->beginTransaction();
        foreach ($this->changes as $line) {
            $this->targetConnection->exec($line);
        }
        $this->targetConnection->commit();

    }

    /**
     * Migrate data to the target db, assuming the target db is empty
     *
     * @param array $tables The tables to sync
     * @param bool $force Sync even if no data seems to be changed
     * @throws DBALException
     */
    public function migrateData(array $tables = [], $force = false)
    {
        if ($this->hasChanges()) {
            throw new \LogicException("Please apply changes before migrating data.");
        }

        $chagedData = [];
        if (!$force) {
            $chagedData = $this->compareRecordCount();
        }

        if (!$force && 0 === count($chagedData)) {
            // nothing to do
            return;
        }

        // create the target schema from db
        $this->targetSchema = $this->targetConnection->getSchemaManager()->createSchema();

        foreach ($this->sourceConnection->getSchemaManager()->createSchema()->getTables() as $table) {
            // ensure only given tables are processed, all tables by default
            if (count($tables) > 0 && !array_key_exists($table->getName(), array_flip($tables))) {
                continue;
            }
            // change data is only filled if force != true
            if (!$force && !array_key_exists($table->getName(), $chagedData)) {
                continue;
            }

            $this->processTable($table);
        }
    }

    /**
     * @param bool $all return diff for all tables instead of only changed
     * @return array
     * @throws DBALException
     */
    public function compareRecordCount(bool $all = false)
    {
        $out = [];

        foreach ($this->sourceConnection->getSchemaManager()->createSchema()->getTables() as $table) {
            $originalTableName = $table->getName();
            $stmt = $this->sourceConnection->query("SELECT count(*) FROM ${originalTableName}");
            $row = $stmt->fetch();
            $sourceCount = $row['count(*)'];

            $targetTableName = $this->mapping->getTableName($originalTableName);
            $stmt = $this->targetConnection->query("SELECT count(*) FROM ${targetTableName}");
            $row = $stmt->fetch();
            $targetCount = $row['count(*)'];

            if ($all || $sourceCount != $targetCount) {
                $out[$originalTableName] = [
                    'originalCount' => $sourceCount,
                    'targetCount' => $targetCount,
                    'targetTableName' => $targetTableName
                ];
            }
        }

        return $out;
    }

    /**
     * @param Table $table
     * @throws DBALException
     */
    private function processTable(Table $table)
    {
        $tableName = $table->getName();
        $stmt = $this->sourceConnection->query("SELECT * FROM ${tableName}");
        $rows = $stmt->fetchAll();

        $this->processRows($tableName, $rows);
    }

    /**
     * @param string $tableName
     * @param array $rows
     * @throws DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function processRows(string $tableName, array $rows)
    {
        $newTableName = $this->mapping->getTableName($tableName);
        $newTable = $this->targetSchema->getTable($newTableName);
        foreach ($rows as $row) {
            $binds = [];
            $data = [];
            foreach ($row as $col => $value) {
                $colName = $this->mapping->getColumnName($tableName, $col);
                $binds[] = ':'.$colName;
                $data[$colName] = $value;
            }
            $insert = 'INSERT INTO ' . $newTableName .' (' . implode(',', array_keys($data)) . ') VALUES (' . implode(',', $binds) . ')';
            $insertStmt = $this->targetConnection->prepare($insert);
            foreach ($data as $col => $value) {
                $insertStmt->bindValue($col, $value, $newTable->getColumn($col)->getType()->getBindingType());
            }
            $insertStmt->execute();
        }
    }

    private function convert(ConverterChain $converterChain)
    {
        $schema = $this->sourceConnection->getSchemaManager()->createSchema();
        foreach ($converterChain as $converter) {
            $schema->visit($converter);
            $schema = $converter->getResult();
        }

        $this->mapping = $converterChain->getMapping();
        $this->targetSchema = $schema;
    }

    private function compare()
    {
        $comparator = new Comparator();
        $diff = $comparator->compare($this->targetConnection->getSchemaManager()->createSchema(), $this->targetSchema);
        $this->changes = $diff->toSql($this->targetPlatform);
    }
}
