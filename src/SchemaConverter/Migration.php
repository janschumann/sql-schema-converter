<?php
namespace NEOEN\SchemaConverter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use SchemaConverter\ConverterChain;
use SchemaConverter\CreateTargetSchemaConverter;

/**
 * Create and migrate a database
 *
 * @package NEOEN\SchemaConverter
 */
class Migration
{
    /**
     * @var Connection
     */
    private $sourceConnection;
    /**
     * @var string
     */
    private $sourcePlatform;
    /**
     * @var Schema
     */
    private $sourceSchema;

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
     * @var Schema
     */
    private $currentTargetSchema;
    /**
     * @var array
     */
    private $changes = array();
    /**
     * @var boolean
     */
    private $compared = false;

    public function __construct(Connection $source, Connection $target, ConverterChain $converterChain)
    {
        $this->sourceConnection = $source;
        $this->sourcePlatform = $this->sourceConnection->getSchemaManager()->getDatabasePlatform();
        $this->sourceSchema = $this->sourceConnection->getSchemaManager()->createSchema();

        $this->targetConnection = $target;
        $this->targetPlatform = $this->targetConnection->getSchemaManager()->getDatabasePlatform();
        $this->targetSchema = new \Doctrine\DBAL\Schema\Schema();
        $this->currentTargetSchema = $this->targetConnection->getSchemaManager()->createSchema();

        $createTargetSchema = new CreateTargetSchemaConverter();
        $createTargetSchema->setTargetSchema($this->targetSchema);
        $this->sourceSchema->visit($createTargetSchema);

        foreach ($converterChain as $converter) {
            $this->targetSchema->visit($converter);
        }
    }

    /**
     * @throws ConnectionException
     * @throws DBALException
     */
    public function createTargetSchema() {
        if (0 !== count($this->currentTargetSchema->getTables())) {
            throw new \RuntimeException("Target db not empty");
        }

        $this->targetConnection->beginTransaction();
        foreach ($this->targetSchema->toSql($this->targetPlatform) as $line) {
            $this->targetConnection->exec($line);
        }
        $this->targetConnection->commit();
    }

    private function compare() {
        if ($this->compared == true) {
            return;
        }
        $comparator = new Comparator();
        $diff = $comparator->compare($this->currentTargetSchema, $this->targetSchema);
        $this->changes = $diff->toSaveSql($this->targetPlatform);
        $this->compared = true;
    }

    /**
     * @return bool
     */
    public function hasChanges() {
        $this->compare();
        return 0 === count($this->changes);
    }

    /**
     * @return string
     */
    public function getChangesSql() {
        $this->compare();

        $sql = "";
        foreach ($this->changes as $line) {
            $sql .= $line . ";\n";
        }

        return $sql;
    }

    /**
     * @throws ConnectionException
     * @throws DBALException
     */
    public function applyChanges() {
        $this->compare();

        $this->targetConnection->beginTransaction();
        foreach ($this->changes as $line) {
            $this->targetConnection->exec($line);
        }
        $this->targetConnection->commit();

    }
}
