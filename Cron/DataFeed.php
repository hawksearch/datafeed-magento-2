<?php
/**
 * Copyright (c) 2020 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace HawkSearch\Datafeed\Cron;

use HawkSearch\Datafeed\Helper\Data as Helper;
use HawkSearch\Datafeed\Model\Datafeed as Task;
use HawkSearch\Datafeed\Model\EmailFactory;
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
     * @var EmailFactory
     */
    private $emailFactory;

    /**
     * DataFeed constructor.
     * @param Task $task
     * @param Helper $helper
     * @param DirectoryList $dir
     * @param EmailFactory $emailFactory
     */
    public function __construct(Task $task, Helper $helper, DirectoryList $dir, EmailFactory $emailFactory)
    {
        $this->task = $task;
        $this->helper = $helper;
        $this->dir = $dir;
        $this->emailFactory = $emailFactory;
    }

    public function execute() {
        chdir($this->dir->getRoot());
        if($this->helper->getCronEnabled()) {
            $vars = [];
            $vars['jobTitle'] = Task::SCRIPT_NAME;
            if ($this->helper->isFeedLocked()) {
                $vars['message'] = "HawkSearch feed is currently locked, not generating feed at this time.";
            } else {
                try {
                    if($this->helper->createFeedLocks(Task::SCRIPT_NAME)) {
                        $this->task->generateFeed();
                        $this->helper->removeFeedLocks(Task::SCRIPT_NAME);
                        $vars['message'] = "HawkSeach Datafeed Generated!";
                    } else {
                        $vars['message'] = 'Unable to create the lock file. feed not generated';
                    }
                } catch (\Exception $e) {
                    $vars['message'] = sprintf('There has been an error: %s', $e->getMessage());
                }
            }
            $this->emailFactory->create()->sendEmail($vars);
        }
    }
}
