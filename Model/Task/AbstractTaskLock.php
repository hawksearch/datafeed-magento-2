<?php
/**
 * Copyright (c) 2023 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
namespace HawkSearch\Datafeed\Model\Task;

use HawkSearch\Datafeed\Model\Config\Feed as ConfigFeed;
use HawkSearch\Datafeed\Model\Task\Exception\TaskLockException;
use HawkSearch\Datafeed\Model\Task\Exception\TaskUnlockException;
use InvalidArgumentException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Lock\Backend\Database as DatabaseLockManager;
use Magento\Framework\Lock\Backend\FileLock;
use Magento\Framework\Lock\LockBackendFactory;
use Magento\Framework\Lock\LockManagerInterface;
use Zend_Db_Statement_Exception;

abstract class AbstractTaskLock
{
    const FILE_LOCK_PATH = "locks";

    /**
     * @var LockManagerInterface
     */
    private $databaseLockManager;

    /**
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @var string
     */
    protected $lockName = '';

    /**
     * @var int
     */
    protected $lockTimeout = 300;

    /**
     * @var DriverPool
     */
    private $driverPool;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var ConfigFeed
     */
    private $configFeed;

    /**
     * @param DatabaseLockManager $databaseLockManager
     * @param DriverPool $driverPool
     * @param DirectoryList $directoryList
     * @param ConfigFeed $configFeed
     */
    public function __construct(
        DatabaseLockManager $databaseLockManager,
        DriverPool $driverPool,
        DirectoryList $directoryList,
        ConfigFeed $configFeed
    ) {
        $this->databaseLockManager = $databaseLockManager;
        $this->driverPool = $driverPool;
        $this->directoryList = $directoryList;
        $this->configFeed = $configFeed;
    }

    /**
     * Attempts to lock the lock.
     * @throws TaskLockException
     * @throws RuntimeException
     * @throws FileSystemException
     */
    public function lock() : void
    {
        $this->requireLockName();

        try {
            if (! $this->getLocker()->lock($this->lockName, $this->lockTimeout)) {
                throw new TaskLockException('failed to lock');
            }
        } catch (AlreadyExistsException | InputException | Zend_Db_Statement_Exception $exception) {
            throw new TaskLockException('failed to lock');
        }
    }

    /**
     * Attempts to unlock the lock.
     * @throws TaskUnlockException
     * @throws RuntimeException
     * @throws FileSystemException
     */
    public function unlock() : void
    {
        $this->requireLockName();

        try {
            // no return check, if 'falsy' then nothing was locked
            $this->getLocker()->unlock($this->lockName);
        } catch (InputException | Zend_Db_Statement_Exception $exception) {
            throw new TaskUnlockException('failed to unlock');
        }
    }

    /**
     * Checks the lock status.
     * @return bool true if locked, else returns false
     * @throws TaskLockException
     * @throws RuntimeException
     * @throws FileSystemException
     */
    public function isLocked() : bool
    {
        $this->requireLockName();

        try {
            return $this->getLocker()->isLocked($this->lockName);
        } catch (InputException | Zend_Db_Statement_Exception $exception) {
            throw new TaskLockException('failed to verify lock status');
        }
    }

    /**
     * Throws an exception if the lock name is not overridden by subclass.
     */
    private function requireLockName() : void
    {
        if ($this->lockName === '') {
            throw new InvalidArgumentException('no lock name provided');
        }
    }

    /**
     * Gets LockManagerInterface implementation using Factory
     *
     * @return LockManagerInterface
     * @throws RuntimeException
     * @throws FileSystemException
     */
    private function getLocker(): LockManagerInterface
    {
        if (!$this->lockManager) {
            $lockProvider = $this->configFeed->getFeedLocker();

            switch ($lockProvider) {
                case LockBackendFactory::LOCK_FILE:
                    /** @var File $fileDriver */
                    $fileDriver = $this->driverPool->getDriver(DriverPool::FILE);
                    $this->lockManager = new FileLock(
                        $fileDriver,
                        $this->directoryList->getPath('var'). '/' . self::FILE_LOCK_PATH
                    );
                    break;
                default:
                    $this->lockManager = $this->databaseLockManager;
            }
        }

        return $this->lockManager;
    }
}
