<?php

namespace SchumannIt\DBAL\Schema;

/**
 * Stores mapping from source to target table and column names
 */
class Mapping
{
    /**
     * @var string[][]
     */
    private $tableMapping = [];
    /**
     * @var string[][][]
     */
    private $columnMapping = [];
    /**
     * @var string[]
     */
    private $resolvedTableMapping = [];
    /**
     * @var string[][]
     */
    private $resolvedColumnMapping = [];
    /**
     * @var string
     */
    private $currentConverter;

    public function reset()
    {
        $this->tableMapping = [];
        $this->columnMapping = [];
        $this->resolvedTableMapping = [];
        $this->resolvedColumnMapping = [];
        $this->currentConverter = '';
    }

    /**
     * @param Converter $converter
     */
    public function addConverter(Converter $converter)
    {
        $this->currentConverter = get_class($converter);
        $this->tableMapping[$this->currentConverter] = [];
    }

    /**
     * Store a table mapping for the current converter
     *
     * @param string $old
     * @param string $new
     */
    public function setTableMapping(string $old, string $new)
    {
        $this->tableMapping[$this->currentConverter][$old] = $new;
        $this->columnMapping[$this->currentConverter][$old] = [];
    }

    /**
     * Store a column mapping for the given db and the current converter
     *
     * @param string $table
     * @param string $old
     * @param string $new
     */
    public function setColumnMapping(string $table, string $old, string $new)
    {
        $this->columnMapping[$this->currentConverter][$table][$old] = $new;
    }

    /**
     * Walks through all converter specific mappings and creates a flat collection of
     * originalName => newName
     * for tables and columns
     */
    public function resolve()
    {
        $mappingNames = array_keys($this->tableMapping);
        if (0 === count($mappingNames)) {
            // nothing to resolve
            return;
        }
        $originalMappingName = array_shift($mappingNames);

        $originalTableNames = array_keys($this->tableMapping[$originalMappingName]);
        foreach ($originalTableNames as $originalTableName) {
            $newTableName = $this->tableMapping[$originalMappingName][$originalTableName];
            foreach ($mappingNames as $mappingName) {
                $newTableName = $this->tableMapping[$mappingName][$newTableName];
            }

            $originalColumnNames = array_keys($this->columnMapping[$originalMappingName][$originalTableName]);
            foreach ($originalColumnNames as $originalColumnName) {
                $newColumnName = $this->columnMapping[$originalMappingName][$originalTableName][$originalColumnName];
                foreach ($mappingNames as $mappingName) {
                    if (array_key_exists($newTableName, $this->columnMapping[$mappingName])) {
                        $newColumnName = $this->columnMapping[$mappingName][$newTableName][$newColumnName];
                    }
                    else {
                        $newColumnName = $this->columnMapping[$mappingName][$originalTableName][$newColumnName];
                    }
                }
                $this->resolvedColumnMapping[$originalTableName][$originalColumnName] = $newColumnName;
            }

            $this->resolvedTableMapping[$originalTableName] = $newTableName;
        }
    }

    /**
     * Fetch the converted table name
     *
     * @param string $oldName
     * @return string
     */
    public function getTableName(string $oldName)
    {
        if (!array_key_exists($oldName, $this->resolvedTableMapping)) {
            throw new \LogicException("Table '" . $oldName . "' does not exist in resolved mappings.");
        }
        return $this->resolvedTableMapping[$oldName];
    }

    /**
     * Fetch the converted column name
     *
     * @param string $oldTableName
     * @param string $oldName
     * @return string
     */
    public function getColumnName(string $oldTableName, string $oldName)
    {
        if (!array_key_exists($oldTableName, $this->resolvedTableMapping) || !array_key_exists($oldTableName, $this->resolvedColumnMapping)) {
            throw new \LogicException("Table '" . $oldName . "' does not exist in resolved mappings.");
        }
        if (!array_key_exists($oldName, $this->resolvedColumnMapping[$oldTableName])) {
            throw new \LogicException("Column '" . $oldName . "' does not exist in resolved mappings.");
        }
        return $this->resolvedColumnMapping[$oldTableName][$oldName];
    }
}
