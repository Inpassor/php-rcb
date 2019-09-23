<?php

namespace rcb\db\postgres;

class Connection extends \rcb\db\Connection
{

    /**
     * @inheritdoc
     */
    public $driver = 'pgsql';

    /**
     * @inheritdoc
     */
    public $port = 5432;

    /**
     * @param mixed $dataType
     * @return string
     * @throws \Exception
     */
    protected function _getDataType($dataType): string
    {
        if ($dataType instanceof ColumnSchemaBuilder) {
            return $dataType->build();
        }
        return ColumnSchemaBuilder::buildFromString($dataType);
    }

    /**
     * @param array $values
     * @return array
     */
    protected function _quoteValues(array $values): array
    {
        foreach ($values as $index => $value) {
            $values[$index] = ColumnSchemaBuilder::quoteValue($value);
        }
        return $values;
    }

    /**
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        return $this->query("
            SELECT EXISTS (
                SELECT * FROM information_schema.tables
                WHERE
                    table_schema = 'public'
                    AND table_name  = '" . $table . "'
            )
        ")->scalar();
    }

    /**
     * @param string $table
     * @param array $columns
     * @return bool
     * @throws \Exception
     */
    public function createTable(string $table, array $columns): bool
    {
        $_columns = [];
        $pk = null;
        $query = 'CREATE TABLE ' . $table . ' (';
        foreach ($columns as $columnName => $dataType) {
            if (is_int($columnName) && is_string($dataType)) {
                $_columns[] = $dataType;
                continue;
            }
            if (
                ($dataType instanceof ColumnSchemaBuilder && $dataType->pk)
                || (is_string($dataType) && strtolower($dataType) === 'pk')
            ) {
                $pk = 'PRIMARY KEY (' . $columnName . ')';
            }
            $_columns[] = $columnName . ' ' . $this->_getDataType($dataType);
        }
        if ($pk) {
            $_columns[] = $pk;
        }
        $query .= implode(', ', $_columns) . ')';
        return $this->query($query)->execute();
    }

    /**
     * @param string $table
     * @return bool
     */
    public function dropTable(string $table): bool
    {
        return $this->query('DROP TABLE ' . $table . ' CASCADE')->execute();
    }

    /**
     * @param string $table
     * @param string $newTable
     * @return bool
     */
    public function renameTable(string $table, string $newTable): bool
    {
        return $this->query('ALTER TABLE ' . $table . ' RENAME TO ' . $newTable)->execute();
    }

    /**
     * @param string $table
     * @param string $column
     * @param string $dataType
     * @return bool
     * @throws \Exception
     */
    public function addColumn(string $table, string $column, string $dataType): bool
    {
        return $this->query('ALTER TABLE ' . $table . ' ADD ' . $column . ' ' . $this->_getDataType($dataType))->execute();
    }

    /**
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function dropColumn(string $table, string $column): bool
    {
        return $this->query('ALTER TABLE ' . $table . ' DROP ' . $column . ' CASCADE')->execute();
    }

    /**
     * @param string $table
     * @param string $column
     * @param string $newColumn
     * @return bool
     */
    public function renameColumn(string $table, string $column, string $newColumn): bool
    {
        return $this->query('ALTER TABLE ' . $table . ' RENAME ' . $column . ' TO ' . $newColumn)->execute();
    }

    /**
     * @param string $name
     * @param string $table
     * @param string $key
     * @param string $foreignTable
     * @param string $foreignKey
     * @param string $onUpdate
     * @param string $onDelete
     * @return bool
     */
    public function addForeignKey(string $name, string $table, string $key, string $foreignTable, string $foreignKey, string $onUpdate = 'CASCADE', string $onDelete = 'CASCADE'): bool
    {
        return $this->query(
            'ALTER TABLE ' . $table
            . ' ADD CONSTRAINT "' . $name . '"'
            . ' FOREIGN KEY (' . $key . ')'
            . ' REFERENCES ' . $foreignTable . ' (' . $foreignKey . ')'
            . ' ON UPDATE ' . $onUpdate
            . ' ON DELETE ' . $onDelete
        )->execute();
    }

    /**
     * @param string $table
     * @param array $columns
     * @return mixed
     */
    public function insert(string $table, array $columns)
    {
        $statement = $this->query(
            'INSERT INTO ' . $table
            . ' (' . implode(', ', array_keys($columns)) . ')'
            . ' VALUES (' . implode(', ', $this->_quoteValues(array_values($columns))) . ')'
            . ' RETURNING *'
        );
        return $statement->asArray()->one();
    }

    /**
     * @param string $table
     * @param array $columns
     * @param mixed $where
     * @return bool
     */
    public function update(string $table, array $columns, $where = null): bool
    {
        $where = (new QueryBuilder())->buildConditions($where);
        return $this->query(
            'UPDATE ' . $table
            . ' SET (' . implode(', ', array_keys($columns)) . ')'
            . ' = (' . implode(', ', $this->_quoteValues(array_values($columns))) . ')'
            . ($where ? ' WHERE ' . $where : '')
        )->execute();
    }

    /**
     * @param string $table
     * @param mixed $where
     * @return bool
     */
    public function delete(string $table, $where = null): bool
    {
        $where = (new QueryBuilder())->buildConditions($where);
        return $this->query(
            'DELETE FROM ' . $table
            . ($where ? ' WHERE ' . $where : '')
        )->execute();
    }

}
