<?php
/**
 * This file is part of ninja-mutex.
 *
 * (C) Kamil Dziedzic <arvenil@klecza.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Business\Lock;
/**
 * Lock implementor
 *
 * @author Kamil Dziedzic <arvenil@klecza.pl>
 */
interface BizLockInterface
{

    /**
     * @param string $name
     * @param null|int $timeout
     * @return bool
     */
    public function acquireLock($name, $timeout = null);

    /**
     * @param $name
     * @return bool
     */
    public function releaseLock($name);

    /**
     * @param $name
     * @return bool
     */
    public function isLocked($name);
}
