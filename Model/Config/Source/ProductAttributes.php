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

namespace HawkSearch\Datafeed\Model\Config\Source;

use Magento\Eav\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

class ProductAttributes implements OptionSourceInterface
{
    /**#@+
     * Constants
     */
    const SEPARATE_METHOD = 'separate_method_hawk';
    /**#@-*/

    /**
     * @var Config
     */
    private $eavConfig;

    public function __construct(
        Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $allAttributes = $this->eavConfig->getEntityAttributes('catalog_product');
        $options = [
            [
                'value' => null,
                'label' => '--Please Select--'
            ],
            [
                'value' => self::SEPARATE_METHOD,
                'label' => 'Dedicated Method'
            ],
            [
                'value' => 'entity_id',
                'label' => 'entity_id'
            ],
            [
                'value' => 'type_id',
                'label' => 'type_id'
            ]

        ];

        foreach ($allAttributes as $attribute) {
            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getAttributeCode()
            ];
        }

        return $options;
    }
}
