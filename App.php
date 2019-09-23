<?php
defined('APP_BEGIN_TIME') or define('APP_BEGIN_TIME', time());
defined('APP_BEGIN_MICROTIME') or define('APP_BEGIN_MICROTIME', microtime(true));
defined('RCB_PATH') or define('RCB_PATH', realpath(__DIR__));
defined('APP_PATH') or define('APP_PATH', RCB_PATH);
defined('APP_ENV') or define('APP_ENV', file_exists(APP_PATH . DIRECTORY_SEPARATOR . '__ENV_DEV__') ? 'DEV' : 'PROD');
defined('APP_ENV_DEV') or define('APP_ENV_DEV', APP_ENV === 'DEV');
defined('APP_ENV_PROD') or define('APP_ENV_PROD', APP_ENV === 'PROD');
defined('APP_DEBUG') or define('APP_DEBUG', APP_ENV_DEV);

class App
{

    /** @var \rcb\web\Application|\rcb\console\Application */
    public static $app = null;

    public function __construct($class, $properties = [])
    {
        $coreProperties = [
            'charset' => 'UTF-8',
            'defaultLanguage' => 'en',
            'components' => [
                'user' => [
                    'class' => '\rcb\base\UserIdentity',
                ],
                'db' => [
                    'class' => '\rcb\db\postgres\Connection',
                ],
                'redis' => [
                    'class' => '\rcb\db\redis\Connection',
                ],
                'cache' => [
                    'class' => '\rcb\caching\RedisCache',
                ],
                'amqp' => [
                    'class' => '\rcb\components\Amqp',
                ],
                'centrifugo' => [
                    'class' => '\rcb\components\Centrifugo',
                ],
                'stat' => [
                    'class' => '\rcb\components\Stat',
                ],
            ],
        ];
        if (strpos($class, '\web\\') !== false) {
            defined('APP_TYPE') or define('APP_TYPE', 'web');
            $coreProperties['components']['request'] = [
                'class' => '\rcb\web\Request',
            ];
            $coreProperties['components']['response'] = [
                'class' => '\rcb\web\Response',
            ];
            $coreProperties['components']['oauth'] = [
                'class' => '\rcb\components\Oauth',
            ];
            $coreProperties['components']['session'] = [
                'class' => '\rcb\web\RedisSession',
            ];
        } elseif (strpos($class, '\console\\') !== false) {
            defined('APP_TYPE') or define('APP_TYPE', 'console');
        }
        $properties = array_replace_recursive($coreProperties, $properties);
        static::$app = new $class($properties);
    }

    public static function autoload($className)
    {
        $className = trim($className, '\\');
        $classFile = APP_PATH . '/' . $className . '.php';
        if (strpos($className, 'rcb\\') === 0) {
            $className = str_replace('rcb\\', '', $className);
            $classFile = RCB_PATH . '/' . $className . '.php';
        }
        if (strpos($className, 'app\\') === 0) {
            $className = str_replace('app\\', '', $className);
            $classFile = APP_PATH . '/' . $className . '.php';
        }
        $classFile = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $classFile);
        if (!is_file($classFile)) {
            return;
        }
        include($classFile);
    }

}

spl_autoload_register(['App', 'autoload'], true, true);
