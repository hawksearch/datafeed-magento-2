<?php


namespace HawkSearch\Datafeed\Model\Task\Datafeed;


use HawkSearch\Datafeed\Model\Task\AbstractTaskLock;

class TaskLock extends AbstractTaskLock
{
    public const LOCK_NAME = 'hawksearch_datafeed';

    protected $lockName = self::LOCK_NAME;
}
