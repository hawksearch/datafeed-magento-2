<?php


namespace HawkSearch\Datafeed\Model\Task\GenerateDatafeed;


use Exception;
use HawkSearch\Datafeed\Model\Task\TaskLockException;
use Magento\Framework\Lock\Backend\Database;

class TaskLock
{
    /** @var string Lock key used for connection database lock. */
    public const LOCK_KEY = 'hawksearch_datafeed_generate';

    /** @var int Max time (seconds) to wait to acquire database lock. */
    public const LOCK_TIMEOUT_SECONDS = 300;

    /** @var Database */
    private $databaseLock;

    /**
     * @param Database $databaseLock
     */
    public function __construct( Database $databaseLock )
    {
        $this->databaseLock = $databaseLock;
    }

    /**
     * Acquires task database lock.
     * @throws TaskLockException
     */
    public function lock() : void
    {
        try {
            $isLocked = $this->databaseLock->lock( self::LOCK_KEY, self::LOCK_TIMEOUT_SECONDS );
        }
        catch ( Exception $exception ) {
            throw new TaskLockException( $exception->getMessage() );
        }

        if ( ! $isLocked ) {
            throw new TaskLockException( 'failed to acquire task lock: ' . self::LOCK_KEY );
        }
    }

    /**
     * Releases task database lock.
     * @throws TaskLockException
     */
    public function unlock() : void
    {
        try {
            $isUnlocked = $this->databaseLock->unlock( self::LOCK_KEY );
        }
        catch ( Exception $exception ) {
            throw new TaskLockException( 'failed to release task lock: ' . $exception->getMessage() );
        }

        if ( ! $isUnlocked ) {
            throw new TaskLockException( 'failed to release task lock: ' . self::LOCK_KEY );
        }
    }
}
