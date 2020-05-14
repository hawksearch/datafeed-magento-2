<?php


namespace HawkSearch\Datafeed\Model\Task\ScheduleDatafeed;


use Exception;
use HawkSearch\Datafeed\Cron\DataFeed;
use HawkSearch\Datafeed\Model\Task\Exception\AlreadyScheduledException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskException;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResourceModel;
use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleCollection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Task
{
    /** @var DateTime */
    private $dateTime;

    /** @var ScheduleCollectionFactory */
    private $scheduleCollectionFactory;

    /** @var ScheduleFactory */
    private $scheduleFactory;

    /** @var ScheduleResourceModel */
    private $scheduleResourceModel;

    /** @var TaskResultsFactory */
    private $taskResultsFactory;

    /**
     * @param DateTime $dateTime
     * @param ScheduleCollectionFactory $scheduleCollectionFactory
     * @param ScheduleFactory $scheduleFactory
     * @param ScheduleResourceModel $scheduleResourceModel
     * @param TaskResultsFactory $taskResultsFactory
     */
    public function __construct(
        DateTime $dateTime,
        ScheduleCollectionFactory $scheduleCollectionFactory,
        ScheduleFactory $scheduleFactory,
        ScheduleResourceModel $scheduleResourceModel,
        TaskResultsFactory $taskResultsFactory
    )
    {
        $this->dateTime                  = $dateTime;
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->scheduleFactory           = $scheduleFactory;
        $this->scheduleResourceModel     = $scheduleResourceModel;
        $this->taskResultsFactory        = $taskResultsFactory;
    }

    /**
     * Task entry point.
     * Attempts to schedule datafeed generation.
     * @throws TaskException
     * @throws AlreadyScheduledException
     */
    public function execute() : TaskResults
    {
        if ( $this->isAlreadyScheduled() ) {
            throw new AlreadyScheduledException();
        }

        $schedule = $this->createScheduleEntry();
        return $this->createResults( $schedule );
    }

    /**
     * Checks whether a cron_schedule entity already exists in the pending state for this job.
     * @return bool
     */
    private function isAlreadyScheduled() : bool
    {
        /** @var ScheduleCollection $collection */
        $collection = $this->scheduleCollectionFactory->create();
        $collection->addFieldToFilter( 'job_code', [ 'eq' => Datafeed::JOB_CODE ] );
        $collection->addFieldToFilter( 'status', [ 'eq' => 'pending' ] );

        return boolval( $collection->getSize() );
    }

    /**
     * @return Schedule
     * @throws TaskException
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

        try {
            $this->scheduleResourceModel->save( $schedule );
            return $schedule;
        }
        catch ( AlreadyExistsException | Exception $e ) {
            throw new TaskException( 'failed to create schedule entry' );
        }
    }

    /**
     * Extracts results data from the schedule entity created during execution.
     * @param Schedule $schedule
     * @return TaskResults
     */
    private function createResults( Schedule $schedule ) : TaskResults
    {
        /** @var TaskResults $results */
        $results = $this->taskResultsFactory->create();

        $results->setJobEntityId( intval( $schedule->getId() ) );
        $results->setCreatedAt( $schedule->getCreatedAt() );
        $results->setScheduledAt( $schedule->getScheduledAt() );

        return $results;
    }
}
