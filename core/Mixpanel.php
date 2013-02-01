<?php

namespace li3_mixpanel\core;

use lithium\core\Environment;

/**
 * Client for sending data to Mixpanel
 *
 */
class Mixpanel extends \lithium\core\StaticObject {

    /**
     * Configuration
     * 
     * @var array
     */
    protected static $_config = array(
        'host' => 'api.mixpanel.com',
        'port' => 80,
        'env' => 'production',
        'timeout' => 1,
        'token' => ''
    );

    /**
     * Current running environment cache
     *
     * @var string
     */
    protected static $env;

    /**
     * Track an increment to a people property
     *
     * @param string|int $id Distinct id for your user
     * @param array $properties Key/value of property names and increment values
     * @return boolean true on succeess, false otherwise
     */
    public static function increment($id, array $properties = array()) {
        $payload = array(
            '$add' => $properties,
            '$distinct_id' => $id,
        ) + static::defaults('$');
        return static::async_call('/engage/', $payload);
    }

    /**
     * Track a transaction for Mixpanels specialized revenue tracking
     *
     * @param string|int $id Distinct id for your user
     * @param float $sum Total transaction sum
     * @param string|date $time Override the time the charge happened
     * @return boolean true on succeess, false otherwise
     */
    public static function transaction($id, $sum, $time = null) {
        $data = array(
            '$append' => array(
                '$transactions' => array(
                    '$time' => $time ?: date('c'),
                    '$amount' => $sum
                )
            ),
            '$distinct_id' => $id
        ) + static::defaults('$');
        return static::async_call('/engage/', $data);
    }

    /**
     * Configure a user tied to a distinct_id and/or IP
     *
     * @param string|int $id Distinct id for your user
     * @param array $data Values to set for the user
     *        Certain special values are allowed, read about them at
     *        https://mixpanel.com/docs/people-analytics/special-properties
     * @return boolean true on succeess, false otherwise
     */
    public static function set($id, array $data = array()) {
        $payload = array(
            '$set' => $data,
            '$distinct_id' => $id
        ) + static::defaults('$');
        return static::async_call('/engage/', $payload);
    }

    /**
     * Tracks event in Mixpanel
     *
     * You can give more properties to analyze and drill down more information
     * later on. You would be surprised what is possible on the awesome Mixpanel
     * Webfrontend. Make sure to provide a single field wich enables Mixpanel
     * to drill down various different events to one user, i.e. user_id
     *
     * @param string $event name of event to trigger
     * @param string $properties an array with data that will be sent along with
     *        this event in order to analyze these fields and additional data
     * @return boolean true on succeess, false otherwise
     */
    public static function track($event, array $properties = array()) {
        $properties += array(
            'ip' => $_SERVER['REMOTE_ADDR'],
            'token' => static::$_config['token']
        );
        return static::async_call('/track', compact('event', 'properties'));
    }

    /**
     * Configure li3_mixpanel properties
     *
     * This method is mostly used when config is passed during `Libraries::add`
     * and is triggered from `config/bootstrap.php`
     *
     * @param array $config
     * @return array Configuration
     */
    public static function configure(array $config = array()) {
        $whitelistKeys = array('host', 'timeout', 'token', 'env', 'port');
        $config = array_intersect_key($config, array_flip($whitelistKeys));
        return self::$_config = $config + self::$_config;
    }

    /**
     * This method handles the submission to the remote endpoint
     *
     * It does that in an asynchronous fashion to prevent time-consuming
     * interaction. It does that with a fire-and-forget approach: It simply
     * opens a socket connection to the remote-point and as soon as that is
     * open it pushes through all data to be transmitted and returns right
     * after that. It may happen, that this leads to unexpected behavior or
     * failure of data submission. double-check your token and everything else
     * that can fail to make sure, everything works as expected.
     *
     * @param string $path Absolute path to mixpanel api endpoint.
     *        Can be one of the values `/track` or `/engange`
     * @param array $data all data to be submitted, must be in the form
     *        of an array, containing exactly two keys: `event` and `properties`
     *        which are of type string (event) and array (properties). You can
     *        submit whatever properties you like. If no token is given, it will
     *        be automatically appended from `static::$host` which can be set in
     *        advance like this: `Mixpanel::$token = 'foo';`
     * @return boolean true on succeess, false otherwise
     *         actually, it just checks, if bytes sent is greater than zero. It
     *         does _NOT_ check in any way if data is recieved sucessfully in
     *         the endpoint and/or if given data is accepted by remote.
     */
    public static function async_call($path, array $data = array()) {
        if (!static::isTracking()) {
            return false;
        }

        $url = $path . '?data=' . base64_encode(json_encode($data));
        $fp = fsockopen(static::$_config['host'], static::$_config['port'], $errno, $errstr, static::$_config['timeout']);
        if ($errno != 0) {
            // TODO: make something useful with error
            return false;
        }
        $out = array();
        $out[] = sprintf('GET %s HTTP/1.1', $url);
        $out[] = sprintf('Host: %s', static::$_config['host']);
        $out[] = 'Accept: */*';
        $out[] = 'Connection: close';
        $out[] = '';
        $bytes = fwrite($fp, implode("\r\n", $out));
        fclose($fp);
        return ($bytes > 0);
    }

    /**
     * Helper method to determine if track calls should be respected
     *
     * It is possible to filter tracking calls based on environment
     * and this method will make sure that is respected
     * @return bool
     **/
    protected static function isTracking(){
        if (($env = static::$_config['env']) && $env === '*') {
            return true;
        }
        if (!static::$env) {
            static::$env = Environment::get();
        }
        return is_array($env) ? in_array(static::$env, $env) : static::$env === $env;
    }

    protected static function defaults($prefix = '') {
        $defaults = array(
            'token' => static::$_config['token']
        );
        if ($_SERVER && isset($_SERVER['REMOTE_ADDR']))
            $defaults['ip'] = $_SERVER['REMOTE_ADDR'];

        if ($prefix) {
            $values = array();
            foreach ($defaults as $key => $val) {
                $values[$prefix . $key] = $val;
            }
            return $values;
        }

        return $defaults;
    }

}
