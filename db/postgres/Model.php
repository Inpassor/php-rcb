<?php

namespace rcb\db\postgres;

use \Exception;

/**
 * Class Model
 * @package rcb\db\postgres
 *
 * @property array $data
 */
class Model extends \rcb\db\Model
{

    /**
     * @var string
     */
    public $db = 'db';

    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @var bool
     */
    public $isNewRecord = true;

    /**
     * @var Connection
     */
    protected $_connection = null;

    /**
     * @var string
     */
    protected $_table = null;

    /**
     * @var array
     */
    protected $_oldData = [];

    /**
     * @var array
     */
    protected $_data = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!$this->isNewRecord) {
            $this->_oldData = $this->_data;
        }
    }

    /**
     * @return Connection
     * @throws Exception
     */
    public function getConnection(): Connection
    {
        if (!$this->_connection) {
            $this->_connection = $this->app->getComponent($this->db)->connect();
        }
        return $this->_connection;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection): void
    {
        $this->_connection = $connection;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        if (!$this->_table) {
            $className = get_class($this);
            $tableName = trim(strtolower(strtr(preg_replace("([A-Z])", "_$0", $className), [
                'rcb\console\models\\' => '',
                'rcb\web\models\\' => '',
                'rcb\models\\' => '',
                'app\models\\' => '',
                '\\' => '',
            ])), '_');
            $this->_table = '{{' . $tableName . '}}';
        }
        return $this->_table;
    }

    /**
     * @param string $tableName
     */
    public function setTable(string $tableName): void
    {
        $this->_table = '{{' . trim($tableName, '{}') . '}}';
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get(string $name)
    {
        if (in_array($name, $this->attributes)) {
            return isset($this->_data[$name]) ? $this->_data[$name] : null;
        }
        return parent::__get($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        $setter = 'set' . $name;
        if (in_array($name, $this->attributes)) {
            $this->_data[$name] = $value;
        } elseif (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->$name = $value;
        }
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->_data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->attributes)) {
                $this->_data[$key] = $value;
            }
        }
    }

    /**
     * @param int $type
     * @param string $className
     * @param string|array $keys
     * @param mixed $conditions
     * @return Relation
     */
    protected function _has(int $type, string $className, $keys, $conditions = null): Relation
    {
        if (is_string($keys)) {
            $keys = [$keys => 'id'];
        }
        $fk = key($keys);
        $pk = $keys[$fk];
        $relation = new Relation([
            'type' => $type,
            'model' => $className,
            'fk' => $fk,
            'pk' => $pk,
        ]);
        if ($conditions) {
            $relation->conditions = $conditions;
        }
        return $relation;
    }

    /**
     * @param string $className
     * @param string|array $keys
     * @param mixed $conditions
     * @return Relation
     */
    public function hasOne(string $className, $keys, $conditions = null): Relation
    {
        return $this->_has(Relation::TYPE_ONE, $className, $keys, $conditions);
    }

    /**
     * @param string $className
     * @param string|array $keys
     * @param mixed $conditions
     * @return Relation
     */
    public function hasMany(string $className, $keys, $conditions = null): Relation
    {
        return $this->_has(Relation::TYPE_MANY, $className, $keys, $conditions);
    }

    public function beforeSave(): void
    {
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        if ($this->beforeSave() === false) {
            return false;
        }
        if ($this->isNewRecord) {
            $requestFields = [];
            foreach ($this->attributes as $attributeName) {
                if (!array_key_exists($attributeName, $this->_data)) {
                    $this->_data[$attributeName] = 'DEFAULT';
                    $requestFields[] = $attributeName;
                }
            }
            $result = $this->connection->insert($this->table, $this->_data);
            if ($result) {
                $this->_data = $result;
                $this->isNewRecord = false;
                $result = true;
            }
        } else {
            $data = [];
            foreach ($this->_data as $key => $val) {
                if (!array_key_exists($key, $this->_oldData) || $this->_oldData[$key] !== $val) {
                    $data[$key] = $val;
                }
            }
            $result = $data ? $this->connection->update($this->table, $data, $this->_oldData) : true;
        }
        $this->_oldData = $this->_data;
        return $result;
    }

    /**
     * @param mixed $where
     * @return QueryBuilder
     */
    public static function find($where = null): QueryBuilder
    {
        $queryBuilder = new QueryBuilder([
            'model' => get_called_class(),
        ]);
        if ($where) {
            $queryBuilder->where($where);
        }
        return $queryBuilder;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function delete(): bool
    {
        if ($this->isNewRecord) {
            throw new Exception('Could not delete a new record!');
        }
        return $this->connection->delete($this->table, $this->_oldData);
    }

}
