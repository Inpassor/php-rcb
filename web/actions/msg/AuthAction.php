<?php

namespace rcb\web\actions\msg;

use \rcb\web\Request;
use \rcb\web\Response;

class AuthAction extends \rcb\web\Action
{

    public $methods = [self::METHOD_POST];
    public $authType = self::AUTH_BEARER;

    /**
     * @throws \Exception
     */
    protected function _send403(): void
    {
        $this->app->response->sendForbidden();
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->authRequired = $this->app->centrifugo->authRequired;
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run(array $parameters = []): void
    {
        $app = $this->app;
        $centrifugo = $app->centrifugo;
        $params = $app->request->getBody(Request::FORMAT_JSON);
        $result = [];
        if (
            !$params['client']
            || !$params['channels']
            || !is_array($params['channels'])
            || count($params['channels']) < 1
        ) {
            $this->_send403();
        }
        foreach ($params['channels'] as $channelName) {
            $result[$channelName] = [
                'sign' => $centrifugo->generateChannelSign($params['client'], $channelName),
            ];
        }
        $app->response->send($result, Response::FORMAT_JSON);
    }

}
