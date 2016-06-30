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
namespace HawkSearch\Datafeed\Model;
class Email
{

/**
* @var \Magento\Framework\Mail\Template\TransportBuilder
*/
protected $_transportBuilder;
 
/**
* @var \Magento\Framework\Translate\Inline\StateInterface
*/
protected $inlineTranslation;
 
/**
* @var \Magento\Framework\App\Config\ScopeConfigInterface
*/
protected $scopeConfig;
 
/**
* @var \Magento\Store\Model\StoreManagerInterface
*/
protected $storeManager; 
/**
* @var \Magento\Framework\Escaper
*/
protected $_escaper;
/**
* @param \Magento\Framework\App\Action\Context $context
* @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
* @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
* @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
* @param \Magento\Store\Model\StoreManagerInterface $storeManager
*/
public function __construct(
\Magento\Framework\App\Action\Context $context,
\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
\Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
\Magento\Store\Model\StoreManagerInterface $storeManager,
\Magento\Framework\Escaper $escaper
) {

$this->_transportBuilder = $transportBuilder;
$this->inlineTranslation = $inlineTranslation;
$this->scopeConfig = $scopeConfig;
$this->storeManager = $storeManager;
$this->_escaper = $escaper;
}
 
  
  public function sendEmail($templateParams)
  {
	  

	  	$objectManagerr = \Magento\Framework\App\ObjectManager::getInstance();	
		$helper = $objectManagerr->create('HawkSearch\Datafeed\Helper\Data');
		  
		$receiver=$helper->getCronEmail();
	    $this->inlineTranslation->suspend();
	    $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE; 
	 // $sender = ['name' => $helper->getGenName(),'email' =>$helper->getGenEmail()];
		$sender = ['name' => $helper->getGenName(),'email' =>$helper->getGenEmail()];
		$transport = $this->_transportBuilder
			->setTemplateIdentifier('hawksearch_datafeed_cronemail') 
			->setTemplateOptions(
				[
				'area' => \Magento\Framework\App\Area::AREA_FRONTEND, 
				'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
			]
			)
			->setTemplateVars($templateParams)
			->setFrom($sender)
			->addTo($receiver)
			->getTransport();
		$transport->sendMessage();  

		$this->inlineTranslation->resume();
  }  
  
}
 
?>