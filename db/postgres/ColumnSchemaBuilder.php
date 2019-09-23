<?php

namespace rcb\db\postgres;

use \Exception;

class ColumnSchemaBuilder extends \rcb\base\BaseObject
{

    /**
     * @var array
     */
    public static $dataTypesMap = [
        'pk' => 'bigserial',
        'string' => 'varchar(255)',
        'ip' => 'cidr',
        'host' => 'inet',
    ];

    /**
     * @var array
     */
    public static $quoteValueMap = [
        'default' => 'DEFAULT',
    ];

    /**
     * @var bool
     */
    protected $_pk = null;

    /**
     * @var string
     */
    protected $_type = null;

    /**
     * @var bool
     */
    protected $_null = null;

    /**
     * @var string
     */
    protected $_comment = null;

    /**
     * @var string
     */
    protected $_default = null;

    /**
     * @param string $type
     * @return ColumnSchemaBuilder
     */
    protected function _setType(string $type): ColumnSchemaBuilder
    {
        $this->_type = $type;
        return $this;
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function primaryKey(): ColumnSchemaBuilder
    {
        $this->_pk = true;
        return $this->_setType('bigserial');
    }

    /**
     * @return bool
     */
    public function getPk()
    {
        return $this->_pk;
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function null(): ColumnSchemaBuilder
    {
        $this->_null = true;
        return $this;
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function notNull(): ColumnSchemaBuilder
    {
        $this->_null = false;
        return $this;
    }

    /**
     * @param mixed $value
     * @return ColumnSchemaBuilder
     */
    public function defaultValue($value): ColumnSchemaBuilder
    {
        $this->_default = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return ColumnSchemaBuilder
     */
    public function comment(string $value): ColumnSchemaBuilder
    {
        $this->_comment = $value;
        return $this;
    }

    /**
     * @param int $n
     * @return ColumnSchemaBuilder
     */
    public function string(int $n = 255): ColumnSchemaBuilder
    {
        return $this->_setType('varchar(' . $n . ')');
    }

    /**
     * @param int $n
     * @return ColumnSchemaBuilder
     */
    public function character(int $n = 255): ColumnSchemaBuilder
    {
        return $this->_setType('char(' . $n . ')');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function text(): ColumnSchemaBuilder
    {
        return $this->_setType('text');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function boolean(): ColumnSchemaBuilder
    {
        return $this->_setType('boolean');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function date(): ColumnSchemaBuilder
    {
        return $this->_setType('date');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function time(): ColumnSchemaBuilder
    {
        return $this->_setType('time');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function timeTZ(): ColumnSchemaBuilder
    {
        return $this->_setType('time with time zone');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function timeStamp(): ColumnSchemaBuilder
    {
        return $this->_setType('timestamp');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function dateTime(): ColumnSchemaBuilder
    {
        return $this->timeStamp();
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function timeStampTZ(): ColumnSchemaBuilder
    {
        return $this->_setType('timestamp with time zone');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function dateTimeTZ(): ColumnSchemaBuilder
    {
        return $this->timeStampTZ();
    }

    // TODO: add interval
    // @see https://www.postgresql.org/docs/9.2/static/datatype-datetime.html

    /**
     * @return ColumnSchemaBuilder
     */
    public function smallInteger(): ColumnSchemaBuilder
    {
        return $this->_setType('smallint');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function integer(): ColumnSchemaBuilder
    {
        return $this->_setType('integer');
    }

    /**
     * @return ColumnSchemaBuilder
     */
    public function bigInteger(): ColumnSchemaBuilder
    {
        return $this->_setType('bigint');
    }

    /**
     * @param string|int $precision
     * @param string|int $scale
     * @return ColumnSchemaBuilder
     */
    public function numeric($precision, $scale = null): ColumnSchemaBuilder
    {
        return $this->_setType('numeric(' . $precision . ($scale ? ',' . $scale : '') . ')');
    }

    /**
     * @param string|int $precision
     * @param string|int $scale
     * @return ColumnSchemaBuilder
     */
    public function decimal($precision, $scale = null): ColumnSchemaBuilder
    {
        return $this->numeric($precision, $scale);
    }

    /**
     * @param string|int $precision
     * @param string|int $scale
     * @return ColumnSchemaBuilder
     */
    public function double($precision, $scale = null): ColumnSchemaBuilder
    {
        return $this->numeric($precision, $scale);
    }

    /**
     * @param string|int $precision
     * @param string|int $scale
     * @return ColumnSchemaBuilder
     */
    public function real($precision, $scale = null): ColumnSchemaBuilder
    {
        return $this->numeric($precision, $scale);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function build(): string
    {
        if (!$this->_type) {
            throw new Exception('Data type not set');
        }
        $null = '';
        if ($this->_null !== null) {
            $null = ($this->_null ? '' : ' NOT') . ' NULL';
        }
        $default = $this->_default !== null ? " DEFAULT " . static::quoteValue($this->_default) : '';
        $comment = $this->_comment ? " COMMENT '" . $this->_comment . "'" : '';
        return $this->_type . $null . $default . $comment;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function quoteValue($value): string
    {
        if (is_string($value)) {
            $valueLower = strtolower($value);
            if (isset(static::$quoteValueMap[$valueLower])) {
                return static::$quoteValueMap[$valueLower];
            }
        }
        if (is_bool($value)) {
            return ($value ? 'TRUE' : 'FALSE');
        }
        return "'" . $value . "'";
    }

    /**
     * @param string $dataType
     * @return string
     */
    public static function buildFromString(string $dataType): string
    {
        $_dataType = strtolower($dataType);
        return isset(static::$dataTypesMap[$_dataType]) ? static::$dataTypesMap[$_dataType] : $dataType;
    }

}
