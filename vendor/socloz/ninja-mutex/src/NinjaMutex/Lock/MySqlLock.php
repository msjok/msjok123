<?php
/**
 * This file is part of ninja-mutex.
 *
 * (C) Kamil Dziedzic <arvenil@klecza.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NinjaMutex\Lock;

use PDO;
use NinjaMutex\Lock\LockAbstract;

/**
 * Lock implementor using MySql
 *
 * @author Kamil Dziedzic <arvenil@klecza.pl>
 */
class MySqlLock extends LockAbstract
{
    /**
     * MySql connections
     *
     * @var PDO[]
     */
    protected $pdo = array();

    protected $user;
    protected $password;
    protected $host;
    protected $classname;

    /**
     * Provide data for PDO connection
     *
     * @param string $user
     * @param string $password
     * @param string $host
     * @param string $classname class name to create as PDO connection
     */
    public function __construct($user, $password, $host, $classname = 'PDO')
    {
        parent::__construct();

        $this->user = $user;
        $this->password = $password;
        $this->host = $host;
        $this->classname = $classname;
    }

    public function __clone()
    {
        $this->pdo = array();
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
    public function acquireLock($name, $timeout = null)
    {
        if (!$this->setupPDO($name)) {
            return false;
        }

        $start = microtime(true);
        $end = $start + $timeout / 1000;
        $locked = false;
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
        return !$this->isLocked($name) && $this->pdo[$name]->query(
            sprintf(
                'SELECT GET_LOCK("%s", %d)',
                $name,
                0
            ),
            PDO::FETCH_COLUMN,
            0
        )->fetch();
    }

    /**
     * Release lock
     *
     * @param string $name name of lock
     * @return bool
     */
    public function releaseLock($name)
    {
        if (!$this->setupPDO($name)) {
            return false;
        }

        return (bool)$this->pdo[$name]->query(
            sprintf(
                'SELECT RELEASE_LOCK("%s")',
                $name
            ),
            PDO::FETCH_COLUMN,
            0
        )->fetch();
    }

    /**
     * Check if lock is locked
     *
     * @param string $name name of lock
     * @return bool
     */
    public function isLocked($name)
    {
        if (empty($this->pdo) && !$this->setupPDO($name)) {
            return false;
        }

        return !current($this->pdo)->query(
            sprintf(
                'SELECT IS_FREE_LOCK("%s")',
                $name
            ),
            PDO::FETCH_COLUMN,
            0
        )->fetch();
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function setupPDO($name)
    {
        if (isset($this->pdo[$name])) {
            return true;
        }

        $this->pdo[$name] = new $this->classname(sprintf('mysql:host=%s', $this->host), $this->user, $this->password);
        return true;
    }
}
