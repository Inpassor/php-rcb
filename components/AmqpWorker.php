<?php

namespace rcb\components;

use \AMQPQueue;
use \AMQPEnvelope;

class AmqpWorker extends \rcb\base\BaseObject
{

    /**
     * @var string
     */
    public $routingKey = 'app';

    /**
     * @var \rcb\components\Amqp
     */
    protected $_amqp = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->_amqp = $this->app->amqp;
        $this->_amqp->connect()->consume([$this, 'receive'], $this->routingKey);
        register_shutdown_function([$this->_amqp, 'close']);
    }

    /**
     * @param AMQPEnvelope $message
     * @param AMQPQueue $queue
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     */
    public function receive(AMQPEnvelope $message, AMQPQueue $queue): void
    {
        $this->run($message, $queue);
        $queue->nack($message->getDeliveryTag());
    }

    /**
     * @param AMQPEnvelope $message
     * @param AMQPQueue $queue
     */
    public function run(AMQPEnvelope $message, AMQPQueue $queue): void
    {
    }

}
