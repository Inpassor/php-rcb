<?php

namespace rcb\db;

use \Exception;
use \PDO;

class Connection extends \rcb\base\BaseObject
{

    /**
     * @var string
     */
    public $driver = null;

    /**
     * @var string
     */
    public $host = 'localhost';

    /**
     * @var int|string
     */
    public $port = null;

    /**
     * @var string
     */
    public $dbName = null;

    /**
     * @var null|string
     */
    public $tablePrefix = null;

    /**
     * @var string
     */
    public $userName = null;

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var PDO
     */
    protected $_pdo = null;

    /**
     * @var array
     */
    protected $_dataTypesMap = [];

    /**
     * @param string $query
     * @return string
     */
    protected function _addTablePrefix(string $query): string
    {
        if ($this->tablePrefix) {
            preg_match_all('/{{(.*?)}}/', $query, $matches);
            if (count($matches) === 2) {
                $replacements = [];
                foreach ($matches[1] as $tableName) {
                    $replacements['{{' . $tableName . '}}'] = $this->tablePrefix . $tableName;
                }
                $query = strtr($query, $replacements);
            }
        }
        return $query;
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function init(): void
    {
        if (!$this->driver) {
            throw new Exception('$driver should be declared');
        }
        if (!$this->dbName) {
            throw new Exception('$dbName should be declared');
        }
        if (!$this->userName) {
            throw new Exception('$username should be declared');
        }
        $this->driver = strtolower($this->driver);
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function connect(): Connection
    {
        if (!$this->_pdo) {
            if (!in_array($this->driver, PDO::getAvailableDrivers())) {
                throw new Exception('The DB driver "' . $this->driver . '" is not available');
            }
            $dsn = $this->driver . ':host=' . $this->host . ($this->port ? ';port=' . $this->port : '') . ';dbname=' . $this->dbName;
            $this->_pdo = new PDO($dsn, $this->userName, $this->password, $this->options);
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->_pdo->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        return $this->_pdo->commit();
    }

    /**
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->_pdo->rollBack();
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return Statement
     */
    public function query(string $query, array $parameters = []): Statement
    {
        $statement = $this->_pdo->prepare($this->_addTablePrefix($query));
        return new Statement($statement ? [
            'statement' => $statement,
            'parameters' => $parameters,
        ] : []);
    }

}
