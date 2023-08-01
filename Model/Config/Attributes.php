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

namespace HawkSearch\Datafeed\Model\Config;

use HawkSearch\Connector\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Store;

class Attributes extends ConfigProvider
{
    /**#@+
     * Configuration paths
     */
    const CONFIG_GROUP_PRICING_ENABLED = 'group_pricing_enabled';
    const CONFIG_MAPPING = 'mapping';
    const HAWK_ATTRIBUTE_LABEL = 'hawk_attribute_label';
    const HAWK_ATTRIBUTE_CODE = 'hawk_attribute_code';
    const MAGENTO_ATTRIBUTE = 'magento_attribute';
    /**#@-*/

    /**
     * @var Json
     */
    private $jsonSerializer;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $jsonSerializer,
        $configRootPath = null,
        $configGroup = null
    ) {
        parent::__construct(
            $scopeConfig,
            $configRootPath,
            $configGroup
        );
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param null|int|string $store
     * @return bool
     */
    public function isGroupPricingEnabled($store = null): bool
    {
        return (bool)$this->getConfig(self::CONFIG_GROUP_PRICING_ENABLED, $store);
    }

    /**
     * @param null|int|string $store
     * @param array $filteredFields
     * @param array $excludedFields
     * @return array
     */
    public function getMapping($store = null, array $filteredFields = [], array $excludedFields = []): array
    {
        $attributeFieldMap = [];
        foreach ($filteredFields as $field) {
            $attributeFieldMap[$field] = '';
        }

        /** @var Store $store */
        $configurationMap = $this->jsonSerializer->unserialize(
            $this->getConfig(self::CONFIG_MAPPING, $store)
        );
        foreach ($configurationMap as $map) {
            if ($filteredFields && !in_array($map[self::HAWK_ATTRIBUTE_CODE], $filteredFields)) {
                continue;
            }
            if (!empty($map[self::HAWK_ATTRIBUTE_CODE])
                && !in_array($map[self::HAWK_ATTRIBUTE_CODE], $excludedFields)
                && !empty($map[self::MAGENTO_ATTRIBUTE])) {
                $attributeFieldMap[$map[self::HAWK_ATTRIBUTE_CODE]] = $map[self::MAGENTO_ATTRIBUTE];
            }
        }

        return $attributeFieldMap;
    }

}
