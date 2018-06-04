<?php
namespace SchumannIt\DBAL\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
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
     * @return string
     */
    public function getChangesSql()
    {
        $sql = "";
        foreach ($this->changes as $line) {
            $sql .= $line . ";\n";
        }

        return $sql;
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
     * @throws DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function migrateData(array $tables = [])
    {
        if ($this->hasChanges()) {
            throw new \LogicException("Please apply changes before migrating data.");
        }

        $targetSchema = $this->targetConnection->getSchemaManager()->createSchema();
        foreach ($this->sourceConnection->getSchemaManager()->createSchema()->getTables() as $table) {
            if (count($tables) > 0 && !array_key_exists($table->getName(), array_flip($tables))) {
                continue;
            }

            $tableName = $table->getName();
            $newTableName = $this->mapping->getTableName($tableName);
            $newTable = $targetSchema->getTable($newTableName);

            $stmt = $this->sourceConnection->query("SELECT * FROM ${tableName}");
            $rows = $stmt->fetchAll();
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

    public function generateMigDataCommands()
    {
        $count = 0;
        $commandCount = 1;
        $tables = [];
        $commands = [];
        foreach ($this->sourceConnection->getSchemaManager()->createSchema()->getTables() as $table) {
            if ($count == 2) {
                $commands[] = 'if ($argv[1] == ' . $commandCount . ') $mig->migrateData(["' . implode('","', $tables) . '"])';
                $commandCount++;
                $count = 0;
                $tables = [];
            }
            $count++;
            $tables[] = $table->getName();
        }
        $commands[] = 'if ($argv[1] == ' . $commandCount . ') $mig->migrateData(["' . implode('","', $tables) . '"])';
        foreach ($commands as $command) {
            echo $command . ";\n";
        }
    }

    public function getMigratedRows()
    {
        foreach ($this->sourceConnection->getSchemaManager()->createSchema()->getTables() as $table) {
            $old = $table->getName();
            $stmt = $this->sourceConnection->query("SELECT count(*) FROM ${old}");
            $data = $stmt->fetchAll();
            echo $old . ': ' . $data[0]['count(*)'] . ' :: ';

            $new = $this->mapping->getTableName($old);
            $stmt = $this->targetConnection->query("SELECT count(*) FROM ${new}");
            $data = $stmt->fetchAll();
            echo $new . ': ' . $data[0]['count(*)'] . "\n";
        }
    }
}
