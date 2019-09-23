<?php

namespace rcb\web;

class Action extends \rcb\base\Action
{

    public const METHOD_GET = 1;
    public const METHOD_HEAD = 2;
    public const METHOD_OPTIONS = 4;
    public const METHOD_POST = 8;
    public const METHOD_PUT = 16;
    public const METHOD_DELETE = 32;
    public const METHOD_PATCH = 64;

    public const AUTH_NONE = 0;
    public const AUTH_BEARER = 1;

    /**
     * @var array
     */
    public $methods = [self::METHOD_GET];

    /**
     * @var int
     */
    public $authType = self::AUTH_NONE;

    /**
     * @var bool
     */
    public $authRequired = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $app = $this->app;
        if (!$this->validateHeaders($app->request->headers)) {
            $app->response->sendBadRequest();
        }
        $this->filterMethod();
        if ($this->authType !== self::AUTH_NONE && $this->authRequired === null) {
            $this->authRequired = true;
        }
        $this->authenticate();
    }

    /**
     * @param array $headers
     * @return bool
     */
    public function validateHeaders(array $headers): bool
    {
        return true;
    }

    /**
     * @throws \Exception
     */
    public function filterMethod(): void
    {
        $app = $this->app;
        if (!in_array($app->request->method, $this->methods)) {
            $app->response->sendBadRequest();
        }
    }

    /**
     * @throws \Exception
     */
    public function authenticate(): void
    {
        switch ($this->authType) {
            case self::AUTH_BEARER:
                (new \rcb\web\auth\Bearer())->authenticate($this->authRequired);
                break;
        }
    }

    /**
     * @param string $view
     * @return string
     */
    public function getViewFileName(string $view): string
    {
        return strtr(strtolower((strpos($view, '/') === false && strpos($view, '\\') === false) ? $this->app->request->route . '/' . $view : $view), [
            '-' => '',
            '/' => DIRECTORY_SEPARATOR,
            '\\' => DIRECTORY_SEPARATOR
        ]);
    }

    /**
     * @param string $view
     * @throws \Exception
     */
    public function render(string $view): void
    {
        $this->app->response->render($this->getViewFileName($view));
    }

}
