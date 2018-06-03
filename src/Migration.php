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
    private $changes = [];
    /**
     * @var boolean
     */
    private $compared = false;
    /**
     * @var ConverterChain
     */
    private $converterChain;

    /**
     * @param Connection $source
     * @param Connection $target
     * @param ConverterChain $converterChain
     */
    public function __construct(Connection $source, Connection $target, ConverterChain $converterChain)
    {
        $this->converterChain = $converterChain;

        $this->sourceConnection = $source;

        $this->targetConnection = $target;
        $this->targetPlatform = $this->targetConnection->getSchemaManager()->getDatabasePlatform();

        $this->convert();
        $this->compare();
    }

    /**
     * @return bool
     */
    public function hasChanges()
    {
        $this->compare();

        return 0 < count($this->changes);
    }

    /**
     * @return string
     */
    public function getChangesSql()
    {
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
    public function applyChanges()
    {
        $this->compare();

        $this->targetConnection->beginTransaction();
        foreach ($this->changes as $line) {
            $this->targetConnection->exec($line);
        }
        $this->targetConnection->commit();

    }

    private function compare()
    {
        if ($this->compared == true) {
            return;
        }
        $comparator = new Comparator();
        $diff = $comparator->compare($this->targetConnection->getSchemaManager()->createSchema(), $this->targetSchema);
        $this->changes = $diff->toSql($this->targetPlatform);

        $this->compared = true;
    }

    private function convert()
    {
        $schema = $this->sourceConnection->getSchemaManager()->createSchema();
        foreach ($this->converterChain as $converter) {
            $schema->visit($converter);
            $schema = $converter->getResult();
        }

        $this->targetSchema = $schema;
    }
}
