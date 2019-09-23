<?php

namespace rcb\components;

use \Exception;
use \AMQPConnection;
use \AMQPChannel;
use \AMQPExchange;
use \AMQPQueue;

class Amqp extends \rcb\base\BaseObject
{

    /**
     * @var string The hostname of the RabbitMQ server
     */
    public $host = 'localhost';

    /**
     * @var int|string The port of the RabbitMQ server
     */
    public $port = 5672;

    /**
     * @var string The username for connection to RabbitMQ server
     */
    public $user = 'guest';

    /**
     * @var string The password for connection to RabbitMQ server
     */
    public $password = 'guest';

    /**
     * @var string
     */
    public $vhost = '/';

    /**
     * @var float
     */
    public $timeout = null;

    /**
     * @var string
     */
    public $workersNamespace = '\app\workers';

    /**
     * @var AMQPConnection
     */
    protected $_connection = null;

    /**
     * @var AMQPChannel
     */
    protected $_channel = null;

    /**
     * @var AMQPExchange
     */
    protected $_exchange = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!extension_loaded('amqp')) {
            throw new Exception('Extension php_amqp required');
        }
    }

    /**
     * @return $this
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function connect(): Amqp
    {
        if (!$this->_connection) {
            $this->_connection = new AMQPConnection();
            $this->_connection->setHost($this->host);
            $this->_connection->setPort($this->port);
            $this->_connection->setLogin($this->user);
            $this->_connection->setPassword($this->password);
            $this->_connection->setVhost($this->vhost);
            if ($this->timeout) {
                $this->_connection->setReadTimeout($this->timeout);
                $this->_connection->setWriteTimeout($this->timeout);
            }
            $this->_connection->connect();
            $this->_channel = new AMQPChannel($this->_connection);
            $this->_exchange = new AMQPExchange($this->_channel);
        }
        return $this;
    }

    /**
     * @param string $data
     * @param string $routing_key
     * @return bool
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     * @throws \AMQPQueueException
     */
    public function send(string $data, $routing_key = 'app'): bool
    {
        $queue = new AMQPQueue($this->_channel);
        $queue->setName($routing_key);
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();
        return $this->_exchange->publish($data, $routing_key);
    }

    /**
     * @param callable $callback
     * @param string $routing_key
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function consume(callable $callback, string $routing_key = 'app'): void
    {
        $queue = new AMQPQueue($this->_channel);
        $queue->setName($routing_key);
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();
        $queue->consume($callback);
    }

    public function close(): void
    {
        if ($this->_connection) {
            $this->_connection->disconnect();
            $this->_connection = null;
            $this->_channel = null;
            $this->_exchange = null;
        }
    }

}
