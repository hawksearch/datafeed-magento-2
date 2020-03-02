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

use HawkSearch\Datafeed\Model\ImageCache as Task;
use HawkSearch\Datafeed\Helper\Data as Helper;
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
            $vars['jobTitle'] = Task::SCRIPT_NAME;
            if ($this->helper->isImageCacheLocked()) {
                $vars['message'] = "Hawksearch is currently locked, not generating the Imagecache at this time.";
            } else {
                try {
                    $this->helper->createImageCacheLocks(Task::SCRIPT_NAME);
                        $this->task->refreshImageCache();
                    $vars['message'] = "HawkSeach Image Cache Generated!";
                } catch (\Exception $e) {
                    $vars['message'] = sprintf('There has been an error: %s', $e->getMessage());
                    $this->helper->removeImageCacheLocks(Task::SCRIPT_NAME);
                }
            }
            $this->emailFactory->create()->sendEmail($vars);
        }
    }
}