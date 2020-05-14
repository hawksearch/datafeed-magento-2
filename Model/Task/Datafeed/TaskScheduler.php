<?php


namespace HawkSearch\Datafeed\Model\Task\Datafeed;


use HawkSearch\Datafeed\Cron\DataFeed;
use HawkSearch\Datafeed\Model\Task\AbstractTaskScheduler;

class TaskScheduler extends AbstractTaskScheduler
{
    protected $jobCode = DataFeed::JOB_CODE;
}
