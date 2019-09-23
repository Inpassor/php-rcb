<?php

namespace rcb\db\postgres;

/**
 * Class Migration
 * @package rcb\db\postgres
 * @method bool beginTransaction()
 * @method bool commit()
 * @method bool rollBack()
 * @method mixed query(string $query, array $parameters = [])
 * @method bool tableExists(string $table)
 * @method bool createTable(string $table, array $columns)
 * @method bool dropTable(string $table)
 * @method bool renameTable(string $table, string $newTable)
 * @method bool addColumn(string $table, string $column, string $dataType)
 * @method bool dropColumn(string $table, string $column)
 * @method bool renameColumn(string $table, string $column, string $newColumn)
 * @method bool addForeignKey(string $name, string $table, string $key, string $foreignTable, string $foreignKey, string $onUpdate = 'CASCADE', string $onDelete = 'CASCADE')
 * @method bool insert(string $table, array $columns)
 * @method ColumnSchemaBuilder primaryKey()
 * @method ColumnSchemaBuilder string(int $n = 255)
 * @method ColumnSchemaBuilder character(int $n = 255)
 * @method ColumnSchemaBuilder text()
 * @method ColumnSchemaBuilder boolean()
 * @method ColumnSchemaBuilder date()
 * @method ColumnSchemaBuilder time()
 * @method ColumnSchemaBuilder timeTZ()
 * @method ColumnSchemaBuilder timeStamp()
 * @method ColumnSchemaBuilder dateTime()
 * @method ColumnSchemaBuilder timeStampTZ()
 * @method ColumnSchemaBuilder dateTimeTZ()
 * @method ColumnSchemaBuilder smallInteger()
 * @method ColumnSchemaBuilder integer()
 * @method ColumnSchemaBuilder bigInteger()
 * @method ColumnSchemaBuilder numeric($precision, $scale = null)
 * @method ColumnSchemaBuilder decimal($precision, $scale = null)
 * @method ColumnSchemaBuilder double($precision, $scale = null)
 * @method ColumnSchemaBuilder real($precision, $scale = null)
 */
class Migration extends \rcb\base\BaseObject
{

    /**
     * @var Connection
     */
    public $connection = null;

    /**
     * @var string
     */
    public $db = 'db';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!$this->connection) {
            $this->connection = $this->app->getComponent($this->db)->connect();
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $connectionFnMap = [
            'beginTransaction',
            'commit',
            'rollBack',
            'query',
            'tableExists',
            'createTable',
            'dropTable',
            'renameTable',
            'addColumn',
            'dropColumn',
            'renameColumn',
            'addForeignKey',
            'insert',
        ];
        $columnSchemaBuilderFnmap = [
            'primaryKey',
            'string',
            'character',
            'text',
            'boolean',
            'date',
            'time',
            'timeTZ',
            'timeStamp',
            'dateTime',
            'timeStampTZ',
            'dateTimeTZ',
            'smallInteger',
            'integer',
            'bigInteger',
            'numeric',
            'decimal',
            'double',
            'real',
        ];
        if (in_array($name, $connectionFnMap)) {
            return call_user_func_array([$this->connection, $name], $arguments);
        }
        if (in_array($name, $columnSchemaBuilderFnmap)) {
            return call_user_func_array([new ColumnSchemaBuilder(), $name], $arguments);
        }
        throw new \Exception('Unknown method "' . $name . '"');
    }

    public function up(): void
    {
    }

    public function down(): void
    {
    }

}
