<?php


namespace HawkSearch\Datafeed\Model\Task\ScheduleGenerateDatafeed;


use Magento\Cron\Model\ResourceModel\Schedule;
use Magento\Cron\Model\ResourceModel\ScheduleFactory;

class Task
{
    /** @var ScheduleFactory */
    private $scheduleFactory;

    /**
     * @param ScheduleFactory $scheduleFactory
     */
    public function __construct( ScheduleFactory $scheduleFactory )
    {
        $this->scheduleFactory = $scheduleFactory;
    }

    public function execute() : void
    {

    }
}
