<?php


namespace HawkSearch\Datafeed\Model\Task\ScheduleGenerateDatafeed;


use Exception;
use HawkSearch\Datafeed\Model\Task\ScheduleGenerateDatafeed\Exception\AlreadyScheduledException;
use HawkSearch\Datafeed\Model\Task\TaskException;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResourceModel;
use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleCollection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Task
{
    private const JOB_CODE = 'justin_test'; // TODO replace with actual value

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

        try {
            $createdAt   = $this->dateTime->gmtTimestamp();
            $scheduledAt = $createdAt + 60; // add 1 minute

            /** @var Schedule $schedule */
            $schedule = $this->scheduleFactory->create()
                ->setJobCode( self::JOB_CODE )
                ->setStatus( Schedule::STATUS_PENDING )
                ->setCreatedAt( strftime( '%Y-%m-%d %H:%M:%S', $createdAt ) )
                ->setScheduledAt( strftime( '%Y-%m-%d %H:%M', $scheduledAt ) );

            $this->scheduleResourceModel->save( $schedule );

            return $this->getResults( $schedule );
        }
        catch ( Exception $exception ) {
            throw new TaskException( 'Failed to create schedule entry', 0, $exception );
        }
    }

    /**
     * Checks whether a cron_schedule entity already exists in the pending state for this job.
     * @return bool
     */
    private function isAlreadyScheduled() : bool
    {
        /** @var ScheduleCollection $collection */
        $collection = $this->scheduleCollectionFactory->create();
        $collection->addFieldToFilter( 'job_code', [ 'eq' => self::JOB_CODE ] );
        $collection->addFieldToFilter( 'status', [ 'eq' => 'pending' ] );

        return boolval( $collection->getSize() );
    }

    /**
     * Extracts results data from the schedule entity created during execution.
     * @param Schedule $schedule
     * @return TaskResults
     */
    private function getResults( Schedule $schedule ) : TaskResults
    {
        /** @var TaskResults $results */
        $results = $this->taskResultsFactory->create();

        $results->setJobEntityId( intval( $schedule->getEntityId() ) );
        $results->setCreatedAt( $schedule->getCreatedAt() );
        $results->setScheduledAt( $schedule->getScheduledAt() );

        return $results;
    }
}
