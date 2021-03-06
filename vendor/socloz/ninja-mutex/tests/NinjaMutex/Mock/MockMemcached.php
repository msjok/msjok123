<?php
/**
 * This file is part of ninja-mutex.
 *
 * (C) Kamil Dziedzic <arvenil@klecza.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NinjaMutex\Mock;

use Memcached;

/**
 * Mock memcached to mimic mutex functionality
 *
 * @author Kamil Dziedzic <arvenil@klecza.pl>
 */
class MockMemcached extends Memcached
{
    /**
     * @var string[]
     */
    protected static $data = array();

    public function __construct()
    {
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null $expiration
     * @return bool
     */
    public function add($key, $value, $expiration = null)
    {
        if (false === $this->get($key)) {
            self::$data[$key] = (string)$value;
            return true;
        }

        return false;
    }

    /**
     * @param string $key
     * @param null $cache_cb
     * @param null $cas_token
     * @return bool|mixed|string
     */
    public function get($key, $cache_cb = null, &$cas_token = null)
    {
        if (!isset(self::$data[$key])) {
            return false;
        }

        return (string)self::$data[$key];
    }

    /**
     * @param string $key
     * @param null $time
     * @return bool
     */
    public function delete($key, $time = null)
    {
        unset(self::$data[$key]);
        return true;
    }
}
