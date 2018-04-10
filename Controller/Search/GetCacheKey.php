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

namespace HawkSearch\Datafeed\Controller\Search;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class GetCacheKey extends Action
{
    protected $jsonResultFactory;
    protected $productCollection;
    protected $imageHelper;

    /**
     * GetCacheKey constructor.
     * @param Context     $context
     * @param JsonFactory $jsonResultFactory
     * @param Collection  $productCollection
     * @param Image       $imageHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        Collection $productCollection,
        Image $imageHelper
    ) {
        // TODO: find a better way to determine the image cache checksum.
        // TODO: remove direct use of the collection
        $this->jsonResultFactory = $jsonResultFactory;
        $this->productCollection = $productCollection;
        $this->imageHelper = $imageHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
        $result = $this->jsonResultFactory->create();
        $data = ['error' => false];

        try {
            $this->productCollection->addAttributeToSelect('small_image');
            $this->productCollection->addAttributeToFilter('small_image', array('notnull' => true));
            $this->productCollection->getSelect()->limit(100);
            $path = '';
            $found = false;
            foreach ($this->productCollection as $product) {
                $path = $this->imageHelper->init($product, 'hawksearch_autosuggest_image')->getUrl();
                if (strpos($path, '/small_image/') !== false) {
                    $found = true;
                    break;
                }
            }

            if($found) {
                $imageArray = explode("/", $path);
                $cache_key = "";
                foreach ($imageArray as $part) {
                    if (preg_match('/[0-9a-fA-F]{32}/', $part)) {
                        $cache_key = $part;
                    }
                }

                $data['cache_key'] = $cache_key;
                $data['date_time'] = date('Y-m-d H:i:s');
            } else {
                $data['error'] = true;
                $data['message'] = 'CacheKey not found';
            }
        } catch (\Exception $e) {
            $data['error'] = true;
            $data['message'] = $e->getMessage();
        }
        $result->setData($data);
        return $result;
    }
}
