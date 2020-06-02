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
declare(strict_types=1);

namespace HawkSearch\Datafeed\Controller\Search;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Image as ProductImage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\View\Config;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Class GetCacheKey
 * Get cache key for hawksearch_autosuggest_image images
 */
class GetCacheKey extends Action implements HttpGetActionInterface
{
    /**
     * Image theme view Id
     */
    const IMAGE_VIEW_ID = 'hawksearch_autosuggest_image';

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * GetCacheKey constructor.
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param Config $config
     * @param Encryptor $encryptor
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        Config $config,
        Encryptor $encryptor,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->config = $config;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $data = ['error' => false];

        $cacheKey = $this->encryptor->hash(
            implode('_', $this->getMiscParams()),
            Encryptor::HASH_VERSION_MD5
        );

        $data['cache_key'] = $cacheKey;
        $data['date_time'] = date('Y-m-d H:i:s');

        return $result->setData($data);
    }

    /**
     * Converting bool into a string representation
     *
     * @return array
     */
    private function getMiscParams()
    {
        $mediaAttributes = $this->config->getViewConfig()
            ->getMediaAttributes(
                'Magento_Catalog',
                Image::MEDIA_TYPE_CONFIG_NODE,
                self::IMAGE_VIEW_ID
            );

        $miscParams['image_height'] = 'h:' . ($mediaAttributes['height'] ?? 'empty');
        $miscParams['image_width'] = 'w:' . ($mediaAttributes['width'] ?? 'empty');
        $miscParams['background'] = !empty($mediaAttributes['background'])
            ? 'rgb' . implode(',', $mediaAttributes['background'])
            : 'rgb255,255,255';
        $miscParams['angle'] = 'r:' . 'empty';
        $miscParams['quality'] = 'q:' . $this->scopeConfig->getValue(ProductImage::XML_PATH_JPEG_QUALITY);
        $miscParams['keep_aspect_ratio'] = (empty($mediaAttributes['aspect_ratio']) ? '' : 'non') . 'proportional';
        $miscParams['keep_frame'] = (empty($mediaAttributes['frame']) ? '' : 'no') . 'frame';
        $miscParams['keep_transparency'] = (empty($mediaAttributes['transparency']) ? '' : 'no') . 'transparency';
        $miscParams['constrain_only'] = (empty($mediaAttributes['constrain']) ? 'do' : 'not') . 'constrainonly';

        return $miscParams;
    }
}
