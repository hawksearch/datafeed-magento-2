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
namespace HawkSearch\Datafeed\Controller\Cron;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;



class Index extends  \Magento\Framework\App\Action\Action
{/**
         * @var \Magento\Framework\View\Result\PageFactory
         */
        protected $resultPageFactory;
	
        /**
         * @param \Magento\Framework\App\Action\Context $context
         * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
         */
        public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory
        )
        {
            $this->resultPageFactory = $resultPageFactory;			
            parent::__construct($context);
        }
  /**
     *  Index execute
     *
     * @return void
     */
    public function execute()
    {
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/crontest'.date('Y-m-d h:i:sa').'.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("HAWKSEARCH: checking Cron");
		
		}}
