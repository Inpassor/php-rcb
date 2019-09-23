<?php

namespace rcb\db\postgres;

use \Exception;
use MongoDB\Driver\Query;
use \rcb\helpers\ArrayHelper;

class QueryBuilder extends \rcb\base\BaseObject
{

    /**
     * @var \rcb\db\postgres\Model
     */
    public $model = null;

    /**
     * @var Connection
     */
    public $connection = null;

    /**
     * @var Model
     */
    protected $_model = null;

    /**
     * @var bool
     */
    protected $_asArray = false;

    /**
     * @var string
     */
    protected $_where = null;

    /**
     * @var string
     */
    protected $_order = null;

    /**
     * @var array
     */
    protected $_with = [];

    /**
     * @param mixed $conditions
     * @param string $operator
     * @return string|null
     */
    public function buildConditions($conditions, string $operator = 'AND')
    {
        if (is_array($conditions)) {
            $_conditions = [];
            foreach ($conditions as $key => $value) {
                if (!$value) {
                    continue;
                }
                if ($this->_model && strpos($key, '.') === false) {
                    $key = $this->_model->table . '.' . $key;
                }
                $_conditions[] = $key . ' = ' . ColumnSchemaBuilder::quoteValue($value);
            }
            $conditions = implode(' ' . $operator . ' ', $_conditions);
        }
        return $conditions;
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!$this->model) {
            return;
        }
        if (!$this->_model) {
            $this->_model = new $this->model();
        }
        if (!$this->connection) {
            $this->connection = $this->app->getComponent($this->_model->db)->connect();
        }
    }

    /**
     * @return $this
     */
    public function asArray()
    {
        $this->_asArray = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function where($where)
    {
        $this->_where = $this->buildConditions($where);
        return $this;
    }

    /**
     * @return $this
     */
    public function order($order)
    {
        $this->_order = $this->buildConditions($order);
        return $this;
    }

    /**
     * @param string $relationName
     * @param mixed $on
     * @return $this
     * @throws Exception
     */
    public function with(string $relationName, $on = null)
    {
        $relation = $this->_model->__get($relationName);
        if (!($relation instanceof Relation)) {
            throw new Exception('Relation "' . $relationName . '" is not defined properly');
        }
        $relation->name = $relationName;
        $relation->parentTable = $this->_model->table;
        if ($on) {
            $relation->conditions = $on;
        }
        $this->_with[] = $relation;
        return $this;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function _processRelationsData(array $data): array
    {
        if (!$this->_with) {
            return $data;
        }
        foreach ($this->_with as $relation) {
            if (!isset($data[$relation->name])) {
                continue;
            }
            $relationData = ArrayHelper::decodePgArray($data[$relation->name]);
            if (!$this->_asArray) {
                unset($data[$relation->name]);
            }
            if ($relation->type === Relation::TYPE_ONE && count($relationData)) {
                $relationData = array_combine($relation->attributes, $relationData[0]);
            } else {
                foreach ($relationData as $relationIndex => $relationRow) {
                    $relationRowData = array_combine($relation->attributes, $relationRow);
                    if ($this->_asArray) {
                        $relationData[$relationIndex] = $relationRowData;
                    } else {
                        $relationRowData['isNewRecord'] = false;
                        $relationData[$relationIndex] = new $relation->model($relationRowData);
                    }
                }
            }
            $data[$relation->name] = $relationData;
        }
        return $data;
    }

    /**
     * @param $data
     * @return array|Model
     */
    protected function _processResult($data)
    {
        $data = $this->_processRelationsData($data);
        if (!$this->_asArray) {
            $data['isNewRecord'] = false;
            $data = new $this->model($data);
        }
        return $data;
    }

    /**
     * @param bool $one
     * @return array|Model|null
     */
    protected function _exec(bool $one = true)
    {
        $selectFields = $this->_model->table . '.*';
        if ($this->_with) {
            foreach ($this->_with as $relation) {
                $selectFields .= ',(SELECT array(' . $relation->buildQuery(true) . ')) AS ' . $relation->name;
            }
        }
        $result = $this->connection->query(
            'SELECT ' . $selectFields . ' FROM ' . $this->_model->table
            . ($this->_where ? ' WHERE ' . $this->_where : '')
            . ($this->_order ? ' ORDER BY ' . $this->_order : '')
        )->asArray();
        $result = $one ? $result->one() : $result->all();
        if (!$result) {
            return null;
        }
        if ($one) {
            $result = $this->_processResult($result);
        } else {
            foreach ($result as $index => $data) {
                $result[$index] = $this->_processResult($data);
            }
        }
        return $result;
    }

    /**
     * @return array|Model|null
     */
    public function one()
    {
        return $this->_exec();
    }

    /**
     * @return array|null
     */
    public function all()
    {
        return $this->_exec(false);
    }

}
