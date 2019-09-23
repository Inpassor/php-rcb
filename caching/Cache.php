<?php

namespace rcb\caching;

use \rcb\helpers\StringHelper;

abstract class Cache extends \rcb\base\BaseObject implements \ArrayAccess
{

    /**
     * @var null|string
     */
    public $keyPrefix = null;

    /**
     * @var int
     */
    public $defaultDuration = 0;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if ($this->keyPrefix === null) {
            $this->keyPrefix = substr(md5(StringHelper::getRandomString()), 0, 5) . '_';
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function buildKey(string $key): string
    {
        if (is_string($key)) {
            $key = ctype_alnum($key) && mb_strlen($key, '8bit') <= 32 ? $key : md5($key);
        } else {
            $key = md5(json_encode($key));
        }

        return $this->keyPrefix . $key;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        $value = $this->getValue($this->buildKey($key));
        return $value ? unserialize($value) : $value;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return $this->getValue($this->buildKey($key)) !== false;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $duration
     * @return bool
     */
    public function set(string $key, $value, int $duration = null): bool
    {
        if ($duration === null) {
            $duration = $this->defaultDuration;
        }
        $value = serialize($value);
        $key = $this->buildKey($key);
        return $this->setValue($key, $value, $duration);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $duration
     * @return bool
     */
    public function add(string $key, $value, int $duration = null): bool
    {
        $value = serialize($value);
        $key = $this->buildKey($key);
        return $this->addValue($key, $value, $duration);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->deleteValue($this->buildKey($key));
    }

    /**
     * @return bool
     */
    public function flush(): bool
    {
        return $this->flushValues();
    }

    /**
     * @param string $key
     * @return mixed
     */
    abstract protected function getValue(string $key);

    /**
     * @param string $key
     * @param mixed $value
     * @param int $duration
     * @return bool
     */
    abstract protected function setValue(string $key, $value, int $duration): bool;

    /**
     * @param string $key
     * @param mixed $value
     * @param int $duration
     * @return bool
     */
    abstract protected function addValue(string $key, $value, int $duration): bool;

    /**
     * @param string $key
     * @return bool
     */
    abstract protected function deleteValue(string $key): bool;

    /**
     * @return bool
     */
    abstract protected function flushValues(): bool;

    /**
     * @param string $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->exists($key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * @param string $key
     */
    public function offsetUnset($key): void
    {
        $this->delete($key);
    }

}
