<?php

namespace rcb\base;

class PgImage extends BaseObject
{

    /**
     * @var string
     */
    public $table = 'image';

    /**
     * @var string
     */
    protected $_filename = null;

    /**
     * @var string
     */
    protected $_extension = null;

    /**
     * @var string
     */
    protected $_mime = null;

    /**
     * @var PgConnection
     */
    protected $_connection = null;

    protected $_image = null;
    protected $_modified_time = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!$this->_filename) {
            $this->_filename = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        }
    }

    /**
     * @return PgConnection|null
     */
    public function getConnection(): PgConnection
    {
        if (!$this->_connection) {
            $config = require __DIR__ . '/../config/main.php';
            $this->_connection = new PgConnection($config['db']);
            $this->_connection->connect();
        }
        return $this->_connection;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): self
    {
        $this->_filename = $filename;
        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        if (!$this->_extension && $this->_filename) {
            $this->_extension = strtolower(pathinfo($this->_filename, PATHINFO_EXTENSION));
        }
        return $this->_extension;
    }

    /**
     * @return string
     */
    public function getMime()
    {
        if (!$this->_mime) {
            switch ($this->getExtension()) {
                case 'jpg'   :
                case 'jpeg'   :
                    $this->_mime = 'image/jpeg';
                    break;
                case 'gif'    :
                    $this->_mime = 'image/gif';
                    break;
                case 'png'    :
                    $this->_mime = 'image/png';
                    break;
                case 'svg'    :
                    $this->_mime = 'image/svg+xml';
                    break;
                case 'ico'    :
                    $this->_mime = 'image/x-icon';
                    break;
            }
        }
        return $this->_mime;
    }

    /**
     * @return string|bool
     */
    public function getId()
    {
        $connection = $this->getConnection();
        $result = $connection->query("SELECT id FROM {{" . $this->table . "}} WHERE filename = '" . $this->_filename . "'");
        $row = $connection->fetch_one($result);
        if (!$row || !isset($row['id'])) {
            return false;
        }
        return $row['id'];
    }

    /**
     * Gets image from the DB
     * @return PgImage
     * @throws Exception
     */
    public function get(): self
    {
        $connection = $this->getConnection();
        $result = $connection->query("SELECT image,modified_time FROM {{" . $this->table . "}} WHERE filename = '" . $this->_filename . "'");
        $row = $connection->fetch_one($result);
        if (!$row || !isset($row['image'])) {
            $code = 404;
            $status = 'Not found';
            header("HTTP/1.0 $code $status");
            header("HTTP/1.1 $code $status");
            header("Status: $code $status");
            //throw new Exception('Not found');
        }
        $this->_image = $row['image'];
        $this->_modified_time = strtotime(substr($row['modified_time'], 0, 19));
        return $this;
    }

    /**
     * Shows image
     *
     * @return $this
     */
    public function show(): self
    {
        header('Content-type: ' . $this->getMime());
        header('Last-Modified: ' . date('D, j M Y H:i:s ', $this->_modified_time) . 'GMT');
        echo pg_unescape_bytea($this->_image);
        die();
    }

}
