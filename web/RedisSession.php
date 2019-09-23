<?php

namespace rcb\web;

use \Exception;
use \rcb\db\redis\Connection;

/**
 * Redis Session implements a session component using [redis](http://redis.io/) as the storage medium.
 *
 * Redis Session requires redis version 2.6.12 or higher to work properly.
 *
 * It needs to be configured with a redis [[Connection]] that is also configured as an application component.
 * By default it will use the `redis` application component.
 *
 * To use redis Session as the session application component, configure the application as follows,
 *
 * ~~~
 * [
 *     'components' => [
 *         'session' => [
 *             'class' => '\rcb\web\RedisSession',
 *         ],
 *         'redis' => [
 *             'class' => '\rcb\db\redis\Connection',
 *         ],
 *     ],
 * ]
 * ~~~
 */
class RedisSession extends \rcb\web\Session
{
    /**
     * @var Connection|string|array the Redis [[Connection]] object or the application component ID of the Redis [[Connection]].
     * This can also be an array that is used to create a redis [[Connection]] instance in case you do not want do configure
     * redis connection as an application component.
     * After the Session object is created, if you want to change this property, you should only assign it
     * with a Redis [[Connection]] object.
     */
    public $redis = 'redis';

    /**
     * @var string a string prefixed to every cache key so that it is unique. If not set,
     * it will use a prefix generated from [[Application::id]]. You may set this property to be an empty string
     * if you don't want to use key prefix. It is recommended that you explicitly set this property to some
     * static value if the cached data needs to be shared among multiple applications.
     */
    public $keyPrefix;

    /**
     * Initializes the redis RedisSession component.
     * This method will initialize the [[redis]] property to make sure it refers to a valid redis connection.
     * @throws Exception if [[redis]] is invalid.
     */
    public function init(): void
    {
        if (is_string($this->redis)) {
            $this->redis = $this->app->getComponent($this->redis);
        } elseif (is_array($this->redis) && isset($this->redis['class'])) {
            $this->redis = new $this->redis['class']($this->redis);
        }
        if (!$this->redis instanceof Connection) {
            throw new Exception("RedisSession::redis must be either a Redis connection instance or the application component ID of a Redis connection.");
        }
        if ($this->keyPrefix === null) {
            $this->keyPrefix = substr(md5($this->app->id), 0, 5);
        }
        parent::init();
    }

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     * @throws Exception
     */
    public function readSession(string $id): string
    {
        $data = $this->redis->executeCommand('GET', [$this->calculateKey($id)]);
        return $data === false || $data === null ? '' : $data;
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return bool whether session write is successful
     * @throws Exception
     */
    public function writeSession(string $id, string $data): bool
    {
        return (bool)$this->redis->executeCommand('SET', [$this->calculateKey($id), $data, 'EX', $this->getTimeout()]);
    }

    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return bool whether session is destroyed successfully
     * @throws Exception
     */
    public function destroySession(string $id): bool
    {
        $this->redis->executeCommand('DEL', [$this->calculateKey($id)]);
        // @see https://github.com/yiisoft/yii2-redis/issues/82
        return true;
    }

    /**
     * Generates a unique key used for storing session data in cache.
     * @param string $id session variable name
     * @return string a safe cache key associated with the session variable name
     */
    protected function calculateKey(string $id): string
    {
        return $this->keyPrefix . md5(json_encode([__CLASS__, $id]));
    }

}
