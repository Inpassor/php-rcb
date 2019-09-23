<?php

namespace rcb\web;

use \Exception;
use \Google\Protobuf\Internal\Message;

class Response extends \rcb\base\BaseObject
{

    public const FORMAT_NONE = 1; // Reserved! Do not use.
    public const FORMAT_PROTOBUF = 2;
    public const FORMAT_JSON = 4;
    public const FORMAT_PLAIN = 8;
    public const FORMAT_BIN = 16;

    /**
     * @var array
     */
    protected $_supportedFormats = [
        self::FORMAT_PROTOBUF => 'application/octet-stream',
        self::FORMAT_JSON => 'application/json',
        self::FORMAT_PLAIN => 'text/plain',
        self::FORMAT_BIN => 'application/octet-stream',
    ];

    /**
     * @var int
     */
    protected $_format = self::FORMAT_JSON;

    /**
     * @var array
     */
    protected $_headers = [];

    /**
     * @param int $format
     * @throws Exception
     */
    protected function _validateFormat(int $format): void
    {
        if (!isset($this->_supportedFormats[$format])) {
            throw new Exception('Response format is not supported');
        }
    }

    /**
     * @param int $format
     * @throws Exception
     */
    public function setFormat(int $format): void
    {
        $this->_validateFormat($format);
        $this->_format = $format;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->_format;
    }

    /**
     * @param int|null $format
     * @return string
     * @throws Exception
     */
    public function getContentType($format = null): string
    {
        if ($format === null) {
            $format = $this->_format;
        }
        $this->_validateFormat($format);
        $contentType = $this->_supportedFormats[$format];
        if (in_array($format, [self::FORMAT_JSON, self::FORMAT_PLAIN])) {
            $contentType .= ';charset=' . $this->app->charset;
        }
        return $contentType;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setHeader(string $name, string $value): void
    {
        $this->_headers[$name] = $value;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function formatter($data = null)
    {
        return $data;
    }

    /**
     * @param string|mixed|Message|null $data
     * @param int|null $format
     * @param bool $exitApp
     * @throws Exception
     */
    public function send($data = null, $format = null, bool $exitApp = null): void
    {
        $responseCode = 200;
        if (is_array($data) && isset($data['status'])) {
            $responseCode = $data['status'];
            unset($data['status']);
        }
        if ($data === null) {
            $responseCode = 204;
        }
        if (is_bool($format) && $exitApp === null) {
            $exitApp = $format;
            $format = null;
        }
        if ($format === null) {
            $format = $this->_format;
        }
        if ($exitApp === null) {
            $exitApp = true;
        }
        http_response_code($responseCode);
        foreach ($this->_headers as $name => $value) {
            header($name . ': ' . $value);
        }
        header('Last-Modified: ' . date('D, j M Y H:i:s ') . 'GMT');
        if ($data) {
            header('Content-Type: ' . $this->getContentType($format));
            $data = $this->formatter($data);
            switch ($format) {
                case self::FORMAT_PROTOBUF:
                    if (!($data instanceof Message)) {
                        throw new Exception('Data is not a Protobuf message');
                    }
                    echo $data->serializeToString();
                    break;
                case self::FORMAT_JSON:
                    echo json_encode($data);
                    break;
                default:
                    echo $data;
            }
        }
        if ($exitApp) {
            $this->app->end();
        }
    }

    /**
     * @throws Exception
     */
    public function sendBadRequest(): void
    {
        $this->send([
            'status' => 400,
        ]);
    }

    /**
     * @throws Exception
     */
    public function sendUnauthorized(): void
    {
        $this->send([
            'status' => 401,
        ]);
    }

    /**
     * @throws Exception
     */
    public function sendForbidden(): void
    {
        $this->send([
            'status' => 403,
        ]);
    }

    /**
     * @param string $url
     * @throws Exception
     */
    public function redirect(string $url): void
    {
        $this->setHeader('Location', $url);
        $this->send([
            'status' => 302,
        ]);
    }

    /**
     * @param string $view
     * @throws \Exception
     */
    public function render(string $view): void
    {
        $viewFile = APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $view . '.php';
        if (!file_exists($viewFile)) {
            $viewFile = RCB_PATH . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $view . '.php';
            if (!file_exists($viewFile)) {
                throw new \Exception('The view file "' . $viewFile . '" not found');
            }
        }
        $this->app->end(require($viewFile));
    }

}
