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
use HawkSearch\Datafeed\Model\EmailFactory;
use Magento\Framework\Filesystem\DirectoryList;

class ImageCache
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
     * @var EmailFactory
     */
    private $emailFactory;

    public function __construct(Task $task, Helper $helper, DirectoryList $dir, EmailFactory $emailFactory)
    {
        $this->task = $task;
        $this->helper = $helper;
        $this->dir = $dir;
        $this->emailFactory = $emailFactory;
    }

    public function execute() {
        chdir($this->dir->getRoot());
        if($this->helper->getCronImagecacheEnable()) {
            $vars = [];
            $vars['jobTitle'] = 'ImageCache';
            if ($this->helper->isFeedLocked()) {
                $vars['message'] = "HawkSearch is currently locked, not generating image cache at this time.";
            } else {
                try {
                    if($this->helper->createFeedLocks()) {
                        $this->task->refreshImageCache();
                        $this->helper->removeFeedLocks();
                        $vars['message'] = "HawkSearch Image Cache Generated!";
                    } else {
                        $vars['message'] = 'Unable to create the lock file. Image Cache not generated';
                    }
                } catch (\Exception $e) {
                    $vars['message'] = sprintf('There has been an error: %s', $e->getMessage());
                }
            }
            $this->emailFactory->create()->sendEmail($vars);
        }
    }
}
