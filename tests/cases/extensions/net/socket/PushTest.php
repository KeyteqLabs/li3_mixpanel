<?php

namespace li3_mixpanel\tests\cases\extensions\net\socket;

use lithium\net\http\Request;
use lithium\test\Mocker;
use li3_mixpanel\extensions\net\socket\Push;

class PushTest extends \lithium\test\unit {

    protected $_testConfig = array(
        'scheme' => 'http',
        'host' => 'google.com',
        'port' => 80,
        'timeout' => 1,
        'classes' => array('request' => 'lithium\net\http\Request')
    );

    public function setUp() {
        $base = 'li3_mixpanel\extensions\net\socket';
        Mocker::overwriteFunction("{$base}\\fopen", function($url) {
            return fopen("php://memory", "rw");
        });
        Mocker::overwriteFunction("{$base}\\fclose", function(&$resource) {
            $resource = null;
            return;
        });
        Mocker::overwriteFunction("{$base}\\fwrite", function($resource, $string, $length = false) {
            return strlen($string);
        });
    }

    public function tearDown() {
        Mocker::overwriteFunction(false);
    }

    public function testAllMethodsNoConnection() {
        $stream = new Push(array('scheme' => null));
        $this->assertFalse($stream->open());
        $this->assertTrue($stream->close());
        $this->assertInternalType('float', $stream->timeout(2));
        $this->assertFalse($stream->write(null));
        $this->assertFalse($stream->read());
    }

    public function testOpen() {
        $stream = new Push($this->_testConfig);
        $this->assertNotEmpty($stream->open());
        $this->assertInternalType('resource', $stream->resource());
    }

    public function testClose() {
        $stream = new Push($this->_testConfig);
        $this->assertNotEmpty($stream->open());
        $this->assertTrue($stream->close());
        $this->assertNotInternalType('resource', $stream->resource());
    }

    public function testTimeout() {
        $stream = new Push($this->_testConfig);
        $result = $stream->open();
        $stream->timeout(10);
        $result = $stream->resource();
        $this->assertEqual((float) 10, $stream->timeout());
        $this->assertInternalType('resource', $result);
    }

    public function testSendWithNull() {
        $stream = new Push($this->_testConfig);
        $stream->open();
        $result = $stream->send(
            new Request($this->_testConfig),
            array('response' => 'lithium\net\http\Response')
        );
        $this->assertInstanceOf('lithium\net\http\Response', $result);
        $this->assertPattern("/^HTTP/", (string) $result);
    }

    public function testWriteWithArray() {
        $stream = new Push($this->_testConfig);
        $stream->open();
        $result = $stream->write(array(
            'path' => '/search',
            'query' => array('data' => 'foo')
        ));
        $this->assertTrue($result);
    }

    public function testSendWithObject() {
        $stream = new Push($this->_testConfig);
        $this->assertInternalType('resource', $stream->open());
        $result = $stream->send(
            new Request($this->_testConfig),
            array('response' => 'lithium\net\http\Response')
        );
        $this->assertInstanceOf('lithium\net\http\Response', $result);
        $this->assertPattern("/^HTTP/", (string) $result);
    }
}
