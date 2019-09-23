<?php

namespace rcb\components;

use \Centrifugo\Centrifugo as Client;
use \Centrifugo\{
    Request, Response, BatchResponse
};

/**
 * Class Centrifugo
 * @package rcb\components
 *
 * @method Request request(string $method, array $params = [])
 * @method Response publish(string $channel, array $data)
 * @method Response broadcast(array $channels, array $data)
 * @method Response unsubscribe(string $channel, string $userId)
 * @method Response disconnect(string $userId)
 * @method Response presence(string $channel)
 * @method Response history(string $channel)
 * @method Response channels()
 * @method Response stats()
 * @method Response node(string $endpoint)
 * @method string generateClientToken(string $user, string $timestamp, string $info = '')
 * @method string generateChannelSign(string $client, string $channel, string $info = '')
 * @method Response sendRequest(string $method, array $params = [])
 * @method BatchResponse sendBatchRequest(array $requests)
 * @method Response getLastResponse()
 */
class Centrifugo extends \rcb\base\BaseObject
{

    /**
     * @var string
     */
    public $host = 'localhost';

    /**
     * @var int
     */
    public $port = 8000;

    /**
     * @var string
     */
    public $secret = '';

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var bool
     */
    public $authRequired = false;

    /**
     * @var Client
     */
    protected $_client = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $transportConfig = [];
        if ($this->app->redis instanceof \rcb\db\redis\Connection) {
            $transportConfig['redis'] = [
                'host' => $this->app->redis->host,
                'port' => $this->app->redis->port,
                'db' => $this->app->redis->database,
                'timeout' => $this->app->redis->connectionTimeout,
                'shardsNumber' => 0,
            ];
        }
        $transportConfig['http'] = [
            CURLOPT_TIMEOUT => 5,
        ];
        $this->_client = new Client('http://' . $this->host . ':' . $this->port, $this->secret, $transportConfig);
    }

    /**
     * Magic call a method of $_client
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->_client, $name], $arguments);
    }

    /**
     * @param string $uid
     * @param array $info
     * @return array
     */
    public function getConnectionParams(string $uid, $info = null): array
    {
        if ($info) {
            $info = json_encode($info);
        }
        $result = [
            'url' => $this->url,
            'authEndpoint' => $this->app->baseUrl . '/msg/auth',
            'user' => $uid,
            'timestamp' => (string)APP_BEGIN_TIME,
            'token' => $this->_client->generateClientToken($uid, APP_BEGIN_TIME, $info),
        ];
        if ($info) {
            $result['info'] = $info;
        }
        return $result;
    }

    /**
     * @param string|array $channel
     * @param array $data
     * @param bool $exitApp
     * @throws \Exception
     */
    public function send($channel, $data = [], $exitApp = true): void
    {
        if (!$channel) {
            return;
        }
        if (is_array($channel)) {
            $this->_client->broadcast($channel, $data);
        } else {
            $this->_client->publish($channel, $data);
        }
        if (APP_TYPE === 'web' && $exitApp) {
            $this->app->response->send();
        }
    }

    /**
     * @param string $channel
     * @param array $data
     * @param bool $exitApp
     * @throws \Exception
     */
    public function sendPrivate($channel = null, $data = null, $exitApp = null): void
    {
        if (is_bool($data) && $exitApp === null) {
            $exitApp = $data;
            $data = [];
        }
        if (!is_string($channel)) {
            $data = $channel;
            $channel = '$user_' . $this->app->user->token;
        }
        if ($exitApp === null) {
            $exitApp = true;
        }
        $this->send($channel, $data, $exitApp);
    }

}
