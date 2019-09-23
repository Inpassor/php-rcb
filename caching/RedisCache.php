<?php

namespace rcb\caching;

class RedisCache extends Cache
{

    /**
     * @var \rcb\db\redis\Connection
     */
    protected $_redis = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        if (!$this->_redis) {
            $this->_redis = $this->app->redis;
        }
    }

    /**
     * @inheritdoc
     */
    public function exists(string $key): bool
    {
        return (bool)$this->_redis->executeCommand('EXISTS', [$this->buildKey($key)]);
    }

    /**
     * @inheritdoc
     */
    protected function getValue(string $key)
    {
        return $this->_redis->executeCommand('GET', [$key]);
    }

    /**
     * @inheritdoc
     */
    protected function setValue(string $key, $value, int $expire): bool
    {
        if ($expire == 0) {
            return (bool)$this->_redis->executeCommand('SET', [$key, $value]);
        } else {
            $expire = (int)($expire * 1000);
            return (bool)$this->_redis->executeCommand('SET', [$key, $value, 'PX', $expire]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function addValue(string $key, $value, int $expire): bool
    {
        if ($expire == 0) {
            return (bool)$this->_redis->executeCommand('SET', [$key, $value, 'NX']);
        } else {
            $expire = (int)($expire * 1000);
            return (bool)$this->_redis->executeCommand('SET', [$key, $value, 'PX', $expire, 'NX']);
        }
    }

    /**
     * @inheritdoc
     */
    protected function deleteValue(string $key): bool
    {
        return (bool)$this->_redis->executeCommand('DEL', [$key]);
    }

    /**
     * @inheritdoc
     */
    protected function flushValues(): bool
    {
        return (bool)$this->_redis->executeCommand('FLUSHDB');
    }

}
