<?php
/**
 * Created by PhpStorm.
 * User: flyer
 * Date: 16/12/25
 * Time: 上午10:33
 */

namespace App\Business\Lock;
use Memcached;
use App\Business\Lock\BizLockInterface;

class MemcachedLock implements BizLockInterface
{
    protected $memcache;
    protected $keys = array();
    protected $lockInformation;

    /**
     * @param \Memcached|\Memcache $memcache
     */
    public function __construct(Memcached $memcache)
    {
        $this->lockInformation = $this->generateLockInformation();
        $this->memcache = $memcache;

    }

    public function __destruct()
    {
        foreach($this->keys as $name => $v) {
            $this->releaseLock($name);
        }
    }

    public function __clone() {
        $this->keys = array();
    }

    /**
     * Acquire lock
     *
     * @param string $name name of lock
     * @param null|int $timeout 1. null if you want blocking lock
     *                          2. 0 if you want just lock and go
     *                          3. $timeout > 0 if you want to wait for lock some time (in milliseconds)
     * @return bool
     */
    public function acquireLock($name,$timeout = null)
    {
        $locked = false;
        if($this->memcache->get($name)){
            return $locked;
        }
        $start = microtime(true);
        $end = $start + $timeout / 1000;
        while (!($locked = $this->getLock($name)) && $timeout > 0 && microtime(true) < $end) {
            usleep(static::USLEEP_TIME);
        }
        return $locked;

    }

    /**
     * @param string $name
     * @return bool
     */
    protected function getLock($name)
    {
        return  ($this->memcache->add($name, serialize($this->getLockInformation()))) && ($this->keys[$name] = true);
    }

    /**
     * Release lock
     *
     * @param string $name name of lock
     * @return bool
     */
    public function releaseLock($name)
    {
        if (isset($this->keys[$name]) && $this->memcache->delete($name)) {
            unset($this->keys[$name]);
            return true;
        }

        return false;
    }

    /**
     * Check if lock is locked
     *
     * @param string $name name of lock
     * @return bool
     */
    public function isLocked($name)
    {
        return  $this->memcache->get($name)?true:false;
    }

    protected function getLockInformation()
    {

        return $this->lockInformation;
    }

    protected function generateLockInformation()
    {
        $pid = getmypid();
        $hostname = gethostname();
        $host = gethostbyname($hostname);

        // Compose data to one string
        $params = array();
        $params[] = $pid;
        $params[] = $host;
        $params[] = $hostname;
        $params[] = time()*1000;

        return $params;
    }

}