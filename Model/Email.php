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
declare(strict_types=1);

namespace HawkSearch\Datafeed\Model;

use HawkSearch\Datafeed\Logger\LoggerFactory;
use HawkSearch\Datafeed\Model\Config\Feed as FeedConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Email
{
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FeedConfig
     */
    private $feedConfigProvider;

    /**
     * Email constructor.
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param FeedConfig $feedConfigProvider
     * @param LoggerFactory $loggerFactory
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        FeedConfig $feedConfigProvider,
        LoggerFactory $loggerFactory
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->feedConfigProvider = $feedConfigProvider;
        $this->logger = $loggerFactory->create();
    }

    /**
     * @param array $templateParams
     * @return void
     */
    public function sendEmail(array $templateParams)
    {
        try {
            $this->inlineTranslation->suspend();
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('hawksearch_datafeed_cronemail')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars($templateParams)
                ->setFrom(
                    [
                        'name' => $this->scopeConfig->getValue(
                            'trans_email/ident_general/name',
                            ScopeInterface::SCOPE_STORE
                        ),
                        'email' => $this->scopeConfig->getValue(
                            'trans_email/ident_general/email',
                            ScopeInterface::SCOPE_STORE
                        )
                    ]
                )
                ->addTo($this->feedConfigProvider->getCronEmail())
                ->getTransport();

            $transport->sendMessage();
        } catch (LocalizedException $e) {
            $this->logger->debug($e->getMessage());
        }

        $this->inlineTranslation->resume();
    }
}
