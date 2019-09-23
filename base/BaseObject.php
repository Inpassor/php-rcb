<?php

namespace rcb\base;

use \Exception;

class BaseObject
{

    /**
     * @var \rcb\web\Application|\rcb\console\Application
     */
    public $app = null;

    /**
     * BaseObject constructor.
     * Configures BaseObject with $config parameters and calls init() method after that.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if ($config) {
            Application::configure($this, $config);
        }
        $this->app = \App::$app;
        $this->init();
    }

    /**
     * Initializes the BaseObject. Called by BaseObject constructor after configuring. Should be overridden in a derivative class.
     */
    public function init(): void
    {
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get(string $name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . $name)) {
            throw new Exception('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws Exception
     */
    public function __set(string $name, $value): void
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new Exception('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new Exception('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

}
