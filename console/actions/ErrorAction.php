<?php

namespace rcb\console\actions;

class ErrorAction extends \rcb\console\Action
{

    /**
     * @var int
     */
    public $statusCode = 0;

    /**
     * @var string
     */
    public $message = '';

    /**
     * @inheritdoc
     */
    public function run(array $parameters = []): void
    {
        $this->app->end($this->message);
    }

}
