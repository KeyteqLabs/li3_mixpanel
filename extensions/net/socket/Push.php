<?php

namespace li3_mixpanel\extensions\net\socket;

/**
 * A "fire and forget" push socket adapter that uses f* methods
 * to fire out data and not listening to any response
 */
class Push extends \lithium\net\Socket {

    /**
     * Connection timeout value.
     *
     * @var integer
     */
    protected $_timeout = 1;

    protected $_classes = array(
        'request' => 'lithium\net\http\Request',
        'response' => 'lithium\net\Message'
    );

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = array()) {
        $defaults = array('scheme' => 'http');
        parent::__construct($config + $defaults);
        $this->timeout($this->_config['timeout']);
    }

    /**
     * Opens the socket and sets its timeout value.
     *
     * @param array $options Update the config settings.
     * @return mixed Returns `false` if the socket configuration does not contain the
     *         `'scheme'` or `'host'` settings, or if configuration fails, otherwise returns a
     *         resource stream.
     */
    public function open(array $options = array()) {
        $config = $this->_config;

        if (!$config['scheme'] || !$config['host']) {
            return false;
        }
        $this->_resource = fsockopen($config['host'], $config['port'], $errno, $errstr, $this->_timeout);
        return $this->_resource;
    }

    /**
     * Closes the socket connection.
     *
     * @return boolean Success.
     */
    public function close() {
        if (!is_resource($this->_resource)) {
            return true;
        }
        fclose($this->_resource);
        if (is_resource($this->_resource)) {
            $this->close();
        }
        return true;
    }

    /**
     * End of file test for this socket connection. Does not apply to this implementation.
     *
     * @return boolean Success.
     */
    public function eof() {
        if (!is_resource($this->_resource)) {
            return true;
        }
        return feof($this->_resource);
    }

    /**
     * Reads from the socket. Does not apply to this implementation.
     *
     * @return void
     */
    public function read() {
        return false;
    }

    /**
     * Writes to the socket.
     *
     * @param string $data Data to write.
     * @return boolean Success
     */
    public function write($data = null) {
        if (!is_resource($this->_resource)) {
            return false;
        }
        $config = $this->_config;

        if (!is_object($data)) {
            $data = $this->_instance($this->_classes['request'], (array) $data + $config);
        }

        $payload = $data->to('string');
        $bytes = fwrite($this->_resource, $payload, strlen($payload));
        return $bytes > 0;
    }

    /**
     * Sets the timeout on the socket *connection*.
     *
     * @param integer $time Seconds after the connection times out.
     * @return integer Current, or newly set, timeout value
     */
    public function timeout($time = null) {
        if ($time !== null) {
            $this->_timeout = $time;
        }
        return (float) $this->_timeout;
    }

    /**
     * Sets the encoding of the socket connection. Does not apply to this implementation.
     *
     * @param string $charset The character set to use.
     * @return boolean `true` if encoding has been set, `false` otherwise.
     */
    public function encoding($charset = null) {
        return false;
    }
}
