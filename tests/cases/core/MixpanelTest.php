<?php

namespace li3_mixpanel\tests\cases\core;

use li3_mixpanel\core\Mixpanel;

class MixpanelTest extends \lithium\test\unit {

    protected $mixpanelConfiguration = array();

    public function setUp() {
        $this->mixpanelConfiguration = array(
            'token' =>  getenv('MIXPANEL_TOKEN'),
            'env' => '*'
        );
    }

    public function testConfigure() {
        $mixpanel = new Mixpanel;
        $configuration = $mixpanel->configure(array('token' => 123) + $this->mixpanelConfiguration);

        $this->assertEqual('*', $configuration['env']);
        $this->assertEqual(123, $configuration['token']);
        $this->assertEqual(80, $configuration['port']);
        $this->assertEqual('api.mixpanel.com', $configuration['host']);
    }

    public function testRealSyncToMixpanel() {
        $message = "Set env variable MIXPANEL_TOKEN to test with real transactions";
        $this->skipIf(!isset($this->mixpanelConfiguration['token']), $message);
        $mixpanel = new Mixpanel;
        $configuration = $mixpanel->configure($this->mixpanelConfiguration);

        $options = array('ip'=>1,'test'=>1);
        $result = $mixpanel->track('test', $options);
        $this->assertTrue($result);

        $result = $mixpanel->transaction(1, 1.6);
        $this->assertTrue($result);
    }
}
