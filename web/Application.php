<?php

namespace rcb\web;

/**
 * Class Application
 * @package rcb\web
 *
 * @property \rcb\web\Request $request
 * @property \rcb\web\Response $response
 * @property \rcb\web\RedisSession $session
 * @property \rcb\components\Oauth $oauth
 */
class Application extends \rcb\base\Application
{

    /**
     * @inheritdoc
     */
    public $actionsNamespace = '\app\actions';

    /**
     * @var string
     */
    public $baseUrl = '';

    /**
     * @inheritdoc
     */
    public function __construct(array $properties = [])
    {
        $this->baseUrl = 'http' . ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['SERVER_NAME'];
        parent::__construct($properties);
    }

    /**
     * @inheritdoc
     */
    protected function _getActionName(array $parameters = []): string
    {
        return $this->request->route ?: $this->defaultAction;
    }

}
