<?php

namespace rcb\base;

use \Exception;

/**
 * Class Application
 * @package rcb\base
 * @property \rcb\db\postgres\Connection $db
 * @property \rcb\db\redis\Connection $redis
 * @property \rcb\caching\RedisCache $cache
 * @property \rcb\components\Amqp $amqp
 * @property \rcb\components\Centrifugo $centrifugo
 * @property \rcb\components\Stat $stat
 * @property \rcb\base\UserIdentity $user
 */
class Application
{

    /**
     * @var string|int
     */
    public $id = 'App';

    /**
     * @var string
     */
    public $name = 'My Application';

    /**
     * @var string
     */
    public $charset = 'UTF-8';

    /**
     * @var string
     */
    public $version = '';

    /**
     * @var array
     */
    public $parameters = [];

    /**
     * @var string
     */
    public $defaultLanguage = 'en';

    /**
     * @var string
     */
    public $defaultAction = 'index';

    /**
     * @var string
     */
    public $errorAction = 'error';

    /**
     * @var string
     */
    public $actionsNamespace = null;

    /**
     * @var array
     */
    protected static $_componentsConfig = [];

    /**
     * @param array $parameters
     * @return string
     */
    protected function _getActionName(array $parameters = []): string
    {
        return '';
    }

    /**
     * @param string $actionName
     * @return string
     */
    protected function _getActionClass(string $actionName): string
    {
        $actionNameParts = explode('/', trim($actionName));
        $lastIndex = count($actionNameParts) - 1;
        $actionNameParts[$lastIndex] = ucfirst($actionNameParts[$lastIndex]);
        $actionName = strtr(implode('/', $actionNameParts), ['-' => '', '/' => '\\']);
        $actionClass = '\\' . trim($this->actionsNamespace, '\\') . '\\' . $actionName . 'Action';
        $coreActionClass = '\rcb\\' . (APP_TYPE === 'web' ? 'web' : 'console') . '\actions\\' . $actionName . 'Action';
        if (!class_exists($actionClass) && class_exists($coreActionClass)) {
            return $coreActionClass;
        }
        return $actionClass;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getComponent(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        if (!isset(static::$_componentsConfig[$name])) {
            return null;
        }
        $config = static::$_componentsConfig[$name];
        unset(static::$_componentsConfig[$name]);
        if (is_array($config) && isset($config['class'])) {
            $class = $config['class'];
            unset($config['class']);
        } else {
            $class = $config;
            $config = [];
        }
        if (!class_exists($class)) {
            throw new Exception('Class "' . $class . '" not found');
        }
        return $this->$name = new $class($config);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get(string $name)
    {
        return $this->getComponent($name);
    }

    /**
     * @param mixed $object
     * @param array $properties
     * @return mixed
     */
    public static function configure($object, array $properties = [])
    {
        foreach ($properties as $name => $value) {
            if ($name === 'components') {
                $object::$_componentsConfig = $value;
                continue;
            }
            $object->$name = $value;
        }
        return $object;
    }

    /**
     * Application constructor.
     * @param array $properties
     */
    public function __construct(array $properties = [])
    {
        static::configure($this, $properties);
    }

    /**
     * @param array $parameters
     * @throws Exception
     */
    public function run(array $parameters = []): void
    {
        $actionName = $this->_getActionName($parameters);
        $actionClass = $this->_getActionClass($actionName);
        if (!class_exists($actionClass)) {
            $actionClass = $this->_getActionClass($this->errorAction);
            if (!class_exists($actionClass)) {
                throw new Exception('Error action not found');
            }
            $actionOptions = [
                'app' => $this,
                'statusCode' => 404,
                'message' => 'The requested action "' . $actionName . '" not found on the server!',
            ];
            $parameters = [];
        } else {
            $actionOptions = [
                'app' => $this,
            ];
        }
        /** @var \rcb\base\Action $action */
        $action = new $actionClass($actionOptions);
        array_shift($parameters);
        $action->run($parameters);
    }

    /**
     * @param int|string $code
     */
    public function end($code = 0): void
    {
        die($code);
    }

}
