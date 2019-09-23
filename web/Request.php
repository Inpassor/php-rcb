<?php

namespace rcb\web;

use \Exception;
use \Google\Protobuf\Internal\Message;

/**
 * Class Request
 * @package rcb\web
 * @property int $method
 * @property string $route
 * @property array $headers
 * @property array $params
 * @property bool $isGet
 * @property bool $isHead
 * @property bool $isOptions
 * @property bool $isPost
 * @property bool $isPut
 * @property bool $isDelete
 * @property bool $isPatch
 */
class Request extends \rcb\base\BaseObject
{

    public const METHOD_GET = 1;
    public const METHOD_HEAD = 2;
    public const METHOD_OPTIONS = 4;
    public const METHOD_POST = 8;
    public const METHOD_PUT = 16;
    public const METHOD_DELETE = 32;
    public const METHOD_PATCH = 64;

    public const FORMAT_NONE = 1; // Reserved! Do not use.
    public const FORMAT_PROTOBUF = 2;
    public const FORMAT_JSON = 4;

    protected $_methodsMap = [
        'GET' => self::METHOD_GET,
        'HEAD' => self::METHOD_HEAD,
        'OPTIONS' => self::METHOD_OPTIONS,
        'POST' => self::METHOD_POST,
        'PUT' => self::METHOD_PUT,
        'DELETE' => self::METHOD_DELETE,
        'PATCH' => self::METHOD_PATCH,
    ];

    /**
     * @var int
     */
    protected $_method = null;

    /**
     * @var string
     */
    protected $_route = null;

    /**
     * @var array
     */
    protected $_headers = null;

    /**
     * @var array
     */
    protected $_params = null;

    /**
     * @var array
     */
    protected $_dangerousWords = [
        'truncate',
        'drop',
        'alert',
        '<script',
        'cookie',
        'function',
        'select',
        'update',
        'grant',
        'insert',
        'eval',
        'onafterprint',
        'onbeforeprint',
        'onbeforeunload',
        'onerror',
        'onhashchange',
        'onload',
        'onmessage',
        'onoffline',
        'ononline',
        'onpagehide',
        'onpageshow',
        'onpopstate',
        'onresize',
        'onstorage',
        'onunload',
        'onblur',
        'onchange',
        'oncontextmenu',
        'onfocus',
        'oninput',
        'oninvalid',
        'onreset',
        'onsearch',
        'onselect',
        'onsubmit',
        'onkeydown',
        'onkeypress',
        'onkeyup',
        'onclick',
        'ondblclick',
        'ondrag',
        'ondragend',
        'ondragenter',
        'ondragleave',
        'ondragover',
        'ondragstart',
        'ondrop',
        'onmousedown',
        'onmousemove',
        'onmouseout',
        'onmouseover',
        'onmouseup',
        'onmousewheel',
        'onscroll',
        'onwheel',
        'oncopy',
        'oncut',
        'onpaste',
        'onabort',
        'oncanplay',
        'oncanplaythrough',
        'oncuechange',
        'ondurationchange',
        'onemptied',
        'onended',
        'onerror',
        'onloadeddata',
        'onloadedmetadata',
        'onloadstart',
        'onpause',
        'onplay',
        'onplaying',
        'onprogress',
        'onratechange',
        'onseeked',
        'onseeking',
        'onstalled',
        'onsuspend',
        'ontimeupdate',
        'onvolumechange',
        'onwaiting',
        'onerror',
        'onshow',
        'ontoggle',
    ];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        // Prevent HTTP response splitting (CWE-113)
        $headers = $this->headers;
        array_walk_recursive($headers, function ($value) {
            if (strpbrk($value, "\r\n")) {
                header("HTTP/1.0 403 Forbidden");
                exit();
            }
        });
        $this->getParams();
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getMethod(): string
    {
        if (!$this->_method) {
            $method = $_SERVER['REQUEST_METHOD'];
            if (!isset($this->_methodsMap[$method])) {
                throw new Exception('Request method "' . $method . '" is not supported');
            }
            $this->_method = $this->_methodsMap[$method];
        }
        return $this->_method;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->_route ?: $this->_route = trim(explode('?', $_SERVER['REQUEST_URI'])[0], '/');
    }

    /**
     * @return bool
     */
    public function getIsGet(): bool
    {
        return $this->method === self::METHOD_GET;
    }

    /**
     * @return bool
     */
    public function getIsHead(): bool
    {
        return $this->method === self::METHOD_HEAD;
    }

    /**
     * @return bool
     */
    public function getIsOptions(): bool
    {
        return $this->method === self::METHOD_OPTIONS;
    }

    /**
     * @return bool
     */
    public function getIsPost(): bool
    {
        return $this->method === self::METHOD_POST;
    }

    /**
     * @return bool
     */
    public function getIsPut(): bool
    {
        return $this->method === self::METHOD_PUT;
    }

    /**
     * @return bool
     */
    public function getIsDelete(): bool
    {
        return $this->method === self::METHOD_DELETE;
    }

    /**
     * @return bool
     */
    public function getIsPatch(): bool
    {
        return $this->method === self::METHOD_PATCH;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        if ($this->_headers === null) {
            $this->_headers = [];
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            } elseif (function_exists('http_get_request_headers')) {
                $headers = http_get_request_headers();
            } else {
                foreach ($_SERVER as $name => $value) {
                    if (strncmp($name, 'HTTP_', 5) === 0) {
                        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        $this->_headers[$name] = $value;
                    }
                }
                return $this->_headers;
            }
            foreach ($headers as $name => $value) {
                $this->_headers[$name] = $value;
            }
        }
        return $this->_headers;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getHeader(string $name, $default = null)
    {
        return isset($this->headers[$name]) ? $this->headers[$name] : $default;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        if ($this->_params === null) {
            $this->_params = [];
            if (in_array($this->method, [self::METHOD_HEAD, self::METHOD_OPTIONS, self::METHOD_PUT, self::METHOD_DELETE, self::METHOD_PATCH])) {
                return $this->_params;
            }
            switch ($this->method) {
                /*
                case self::METHOD_PUT:
                    $putdata = explode('&', file_get_contents('php://input'));
                    foreach ($putdata as $pair) {
                        $item = explode('=', $pair);
                        if (count($item) == 2) {
                            $this->_params[urldecode($item[0])] = urldecode($item[1]);
                        }
                    }
                    break;
                */
                case self::METHOD_GET:
                    $this->_params = $_GET;
                    break;
                case self::METHOD_POST:
                    $this->_params = $_POST;
                    break;
            }
            array_walk_recursive($this->_params, function (&$value) {
                array_walk($this->_dangerousWords, function ($dangerousWord) use (&$value) {
                    $strlower = mb_strtolower($value, 'UTF-8');
                    if (false !== ($pos = mb_strpos($strlower, $dangerousWord, 0, 'UTF-8'))) {
                        $value = mb_substr($value, 0, $pos, 'UTF-8') . mb_substr($value, ($pos + mb_strlen($dangerousWord, 'UTF-8')), mb_strlen($value, 'UTF-8'), 'UTF-8');
                    }
                });
            });
        }
        return $this->_params;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getParam(string $name, $default = null)
    {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }

    /**
     * Gets request body
     * @param int|Message|null $formatOrProto
     * @return Message|mixed|null|string
     * @throws Exception
     * @throws \Google\Protobuf\Internal\Exception
     */
    public function getBody($formatOrProto = null)
    {
        if (in_array($this->method, [self::METHOD_GET, self::METHOD_HEAD, self::METHOD_OPTIONS, self::METHOD_DELETE])) {
            return null;
        }
        $body = file_get_contents('php://input');
        if ($formatOrProto instanceof Message) {
            $formatOrProto->mergeFromString($body);
            return $formatOrProto;
        }
        switch ($formatOrProto) {
            case self::FORMAT_JSON:
                return json_decode($body, true);
                break;
            case null:
                return $body;
                break;
        }
        throw new Exception('Body format is not recognized');
    }

}
