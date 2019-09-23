<?php

namespace rcb\console\actions;

use \Exception;

class AmqpAction extends \rcb\console\Action
{

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function run(array $parameters = []): void
    {
        if (!$parameters) {
            throw new Exception('You should point a AMQP worker');
        }
        $workerClassParts = explode('/', strtolower($parameters[0]));
        $lastIndex = count($workerClassParts) - 1;
        $workerClassParts[$lastIndex] = ucfirst($workerClassParts[$lastIndex]);
        $workerClass = '\\' . trim($this->app->amqp->workersNamespace, '\\') . '\\' . strtr(implode('/', $workerClassParts), ['-' => '', '/' => '\\']) . 'Worker';
        if (!class_exists($workerClass)) {
            throw new Exception('The requested AMQP worker "' . $workerClass . '" not found on server');
        }
        new $workerClass();
    }

}
