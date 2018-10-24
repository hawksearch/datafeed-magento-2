<?php
/**
 * Created by PhpStorm.
 * User: astayart
 * Date: 10/16/18
 * Time: 10:54 AM
 */

namespace HawkSearch\Datafeed\Cron;

use HawkSearch\Datafeed\Helper\Data as Helper;
use HawkSearch\Datafeed\Model\Datafeed as Task;
use HawkSearch\Datafeed\Model\Email;
use Magento\Framework\Filesystem\DirectoryList;

class DataFeed
{
    /**
     * @var Task
     */
    private $task;
    /**
     * @var Helper
     */
    private $helper;
    /**
     * @var DirectoryList
     */
    private $dir;
    /**
     * @var Email
     */
    private $email;

    /**
     * DataFeed constructor.
     * @param Task $task
     * @param Helper $helper
     * @param DirectoryList $dir
     * @param Email $email
     */
    public function __construct(Task $task, Helper $helper, DirectoryList $dir, Email $email)
    {
        $this->task = $task;
        $this->helper = $helper;
        $this->dir = $dir;
        $this->email = $email;
    }

    public function execute() {
        chdir($this->dir->getRoot());
        if($this->helper->getCronEnabled()) {
            $vars = [];
            $vars['jobTitle'] = Task::SCRIPT_NAME;
            if ($this->helper->isFeedLocked()) {
                $vars['message'] = "Hawksearch is currently locked, not generating feed at this time.";
            } else {
                try {
                    $this->helper->createFeedLocks(Task::SCRIPT_NAME);
                    $this->task->generateFeed();

                    $vars['message'] = "HawkSeach Datafeed Generated!";
                } catch (\Exception $e) {
                    $vars['message'] = sprintf('There has been an error: %s', $e->getMessage());
                } finally {
                    $this->helper->removeFeedLocks();
                }
            }
            $this->email->sendEmail($vars);
        }
    }
}
