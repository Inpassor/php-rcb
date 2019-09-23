<?php

namespace rcb\web\actions;

use \rcb\web\Response;

class ErrorAction extends \rcb\web\Action
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
        $this->app->response->send([
            'status' => $this->statusCode,
            'message' => $this->message,
        ], Response::FORMAT_JSON);
    }

}
