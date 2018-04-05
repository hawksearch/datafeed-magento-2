<?php
/**
 * Copyright (c) 2017 Hawksearch (www.hawksearch.com) - All Rights Reserved
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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use HawkSearch\Datafeed\Helper\Data as Helper;

class RunFeedGeneration extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    protected $helper;

    /**
     * @param Context     $context
     * @param JsonFactory $jsonResultFactory
     * @param Helper      $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->helper = $helper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
        $result = $this->jsonResultFactory->create();
        $data = ['error' => false];
        try {
            $disabledFuncs = explode(',', ini_get('disable_functions'));
            $isShellDisabled = is_array($disabledFuncs) ? in_array('shell_exec', $disabledFuncs) : true;

            if ($isShellDisabled) {
                $data['error'] = 'This installation cannot run one off feed generation because the PHP function "shell_exec" has been disabled. Please use cron.';
            } else {
                if (strtolower($this->getRequest()->getParam('force')) == 'true') {
                    $this->helper->removeFeedLocks(true);
                }
                $this->helper->runDatafeed();
            }
        } catch (\Exception $e) {
            $data['error'] = $e;
        }
        $result->setData($data);
        return $result;
    }
}