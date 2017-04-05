<?php
/**
 * Copyright (c) 2013 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace HawkSearch\Datafeed\Controller\Adminhtml\Hawkdatagenerate;

use HawkSearch\Datafeed\Model\Datafeed;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class RunFeedGeneration
    extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    private $helper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \HawkSearch\Datafeed\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
    }

    /**
     * RunFeed execute
     *
     * @return void
     */
    public function execute() {
        $response = array('error' => false);
        try {
            $disabledFuncs = explode(',', ini_get('disable_functions'));
            $isShellDisabled = is_array($disabledFuncs) ? in_array('shell_exec', $disabledFuncs) : true;

            if ($isShellDisabled) {
                $response['error'] = 'This installation cannot run one off feed generation because the PHP function "shell_exec" has been disabled. Please use cron.';
            } else {

                if (strtolower($this->getRequest()->getParam('force')) == 'true') {
                    $this->helper->removeFeedLocks(true);
                }
                $this->helper->runDatafeed();
            }
        } catch (\Exception $e) {
            $response['error'] = $e;
        }
        echo json_encode($response);

        exit;
    }
}