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

    public function __construct(Task $task, Helper $helper, DirectoryList $dir)
    {
        $this->task = $task;
        $this->helper = $helper;
        $this->dir = $dir;
    }

    public function execute() {
        chdir($this->dir->getRoot());
        if($this->helper->getCronEnabled()) {
            $this->task->generateFeed();
        }
    }
}