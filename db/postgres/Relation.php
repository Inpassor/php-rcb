<?php

namespace rcb\db\postgres;

/**
 * Class Relation
 * @package rcb\db\postgres
 *
 * @property string $table
 * @property array $attributes
 */
class Relation extends \rcb\base\BaseObject
{

    const TYPE_ONE = 1;
    const TYPE_MANY = 2;

    /**
     * @var int
     */
    public $type = null;

    /**
     * @var string
     */
    public $name = null;

    /**
     * @var string
     */
    public $model = null;

    /**
     * @var string
     */
    public $fk = null;

    /**
     * @var string
     */
    public $pk = null;

    /**
     * @var string
     */
    public $parentTable = null;

    /**
     * @var mixed
     */
    public $conditions = null;

    /**
     * @var Model
     */
    protected $_model = null;

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
        if (!$this->type) {
            $this->type = static::TYPE_ONE;
        }
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->_model->table;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->_model->attributes;
    }

    /**
     * @param bool $concatinate
     * @return string
     */
    public function buildQuery($concatinate = false): string
    {
        $conditions = (new QueryBuilder())->buildConditions($this->conditions);
        return 'SELECT ' . $this->table . ($concatinate ? '::text' : '.*')
            . ' FROM ' . $this->table
            . ' WHERE ' . $this->table . '.' . $this->fk . ' = ' . $this->parentTable . '.' . $this->pk
            . ($conditions ? ' AND ' . $conditions : '')
            . ($this->type === static::TYPE_ONE ? ' LIMIT 1' : '');
    }

}
