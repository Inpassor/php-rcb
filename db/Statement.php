<?php

namespace rcb\db;

use \PDO;

class Statement extends \rcb\base\BaseObject
{

    /**
     * @var \PDOStatement
     */
    public $statement = null;

    /**
     * @var array
     */
    public $parameters = [];

    /**
     * @var bool
     */
    protected $_asArray = false;

    /**
     * @param array $parameters
     * @return bool
     */
    public function execute(array $parameters = []): bool
    {
        return $this->statement ? $this->statement->execute($parameters ?: $this->parameters) : false;
    }

    /**
     * @return $this
     */
    public function asArray(): Statement
    {
        $this->_asArray = true;
        return $this;
    }

    /**
     * @return mixed
     */
    public function one()
    {
        if (!$this->execute()) {
            return null;
        }
        return $this->statement->fetch($this->_asArray ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
    }

    /**
     * @return mixed
     */
    public function all()
    {
        if (!$this->execute()) {
            return null;
        }
        return $this->statement->fetchAll($this->_asArray ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
    }

    /**
     * @return mixed
     */
    public function scalar()
    {
        if (!$this->execute()) {
            return null;
        }
        return $this->statement->fetchColumn();
    }

}
