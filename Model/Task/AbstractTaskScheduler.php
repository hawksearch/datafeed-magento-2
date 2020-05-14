<?php


namespace HawkSearch\Datafeed\Model\Task;


use Exception;
use HawkSearch\Datafeed\Cron\DataFeed;
use HawkSearch\Datafeed\Model\Task\Exception\AlreadyScheduledException;
use HawkSearch\Datafeed\Model\Task\Exception\SchedulerException;
use InvalidArgumentException;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResourceModel;
use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleCollection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

abstract class AbstractTaskScheduler
{
    /** @var DateTime */
    private $dateTime;

    /** @var ScheduleCollectionFactory */
    private $scheduleCollectionFactory;

    /** @var ScheduleFactory */
    private $scheduleFactory;

    /** @var ScheduleResourceModel */
    private $scheduleResourceModel;

    /** @var string job_code field in cron_schedules table */
    protected $jobCode = '';

    /**
     * @param DateTime $dateTime
     * @param ScheduleCollectionFactory $scheduleCollectionFactory
     * @param ScheduleFactory $scheduleFactory
     * @param ScheduleResourceModel $scheduleResourceModel
     */
    public function __construct(
        DateTime $dateTime,
        ScheduleCollectionFactory $scheduleCollectionFactory,
        ScheduleFactory $scheduleFactory,
        ScheduleResourceModel $scheduleResourceModel
    )
    {
        $this->dateTime                  = $dateTime;
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->scheduleFactory           = $scheduleFactory;
        $this->scheduleResourceModel     = $scheduleResourceModel;
    }

    /**
     * Returns true if there is a pending schedule entry in the cron_schedule table, false otherwise.
     * @return bool
     */
    public function isScheduled() : bool
    {
        $this->requireJobCode();

        /** @var ScheduleCollection $collection */
        $collection = $this->scheduleCollectionFactory->create();
        $collection->addFieldToFilter( 'job_code', [ 'eq' => $this->jobCode ] );
        $collection->addFieldToFilter( 'status', [ 'eq' => Schedule::STATUS_PENDING ] );

        return boolval( $collection->getSize() );
    }

    /**
     * @throws AlreadyScheduledException
     * @throws SchedulerException
     */
    public function schedule() : Schedule
    {
        $this->requireJobCode();

        if ( $this->isScheduled() ) {
            throw new AlreadyScheduledException( sprintf( 'job_code %s is already scheduled', $this->jobCode ) );
        }

        $schedule = $this->createScheduleEntry();

        try {
            $this->scheduleResourceModel->save( $schedule );
            return $schedule;
        }
        catch ( Exception $exception ) {
            throw new SchedulerException( sprintf( 'failed to save schedule for job_code %s', $this->jobCode ) );
        }
    }

    /**
     * @return Schedule
     */
    private function createScheduleEntry() : Schedule
    {
        $createdAt   = $this->dateTime->gmtTimestamp();
        $scheduledAt = $createdAt + 60; // add 1 minute

        /** @var Schedule $schedule */
        $schedule = $this->scheduleFactory->create()
            ->setJobCode( DataFeed::JOB_CODE )
            ->setStatus( Schedule::STATUS_PENDING )
            ->setCreatedAt( strftime( '%Y-%m-%d %H:%M:%S', $createdAt ) )
            ->setScheduledAt( strftime( '%Y-%m-%d %H:%M', $scheduledAt ) );

        return $schedule;
    }

    /**
     * Verifies that a jobCode has been specified.
     */
    private function requireJobCode() : void
    {
        if ( $this->jobCode === '' ) {
            throw new InvalidArgumentException( 'jobCode is a required field' );
        }
    }
}
