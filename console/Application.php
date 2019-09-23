<?php

namespace rcb\console;

class Application extends \rcb\base\Application
{

    /**
     * @inheritdoc
     */
    public $actionsNamespace = '\app\commands';

    /**
     * @inheritdoc
     */
    protected function _getActionName(array $parameters = []): string
    {
        return isset($parameters[0]) ? $parameters[0] : $this->defaultAction;
    }

}
