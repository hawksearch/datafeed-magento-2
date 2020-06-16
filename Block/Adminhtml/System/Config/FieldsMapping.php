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
declare(strict_types=1);

namespace HawkSearch\Datafeed\Block\Adminhtml\System\Config;

use HawkSearch\Datafeed\Block\Adminhtml\Form\Field\AttributeColumn;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class FieldsMapping extends AbstractFieldArray
{
    /**#@+
     * Constants
     */
    const HAWK_ATTRIBUTE_LABEL = 'hawk_attribute_label';
    const HAWK_ATTRIBUTE_CODE = 'hawk_attribute_code';
    const MAGENTO_ATTRIBUTE = 'magento_attribute';
    /**#@-*/

    /**
     * @var string
     */
    protected $_template = 'HawkSearch_Datafeed::system/config/form/field/array.phtml';

    /**
     * @var AttributeColumn
     */
    private $attributeRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            self::HAWK_ATTRIBUTE_LABEL,
            [
                'label' => __('HawkSearch Label'),
                'class' => 'required-entry',
                'readonly' => true
            ]
        );
        $this->addColumn(
            self::HAWK_ATTRIBUTE_CODE,
            [
                'label' => __('HawkSearch Code'),
                'class' => 'required-entry validate-code',
                'readonly' => true
            ]
        );
        $this->addColumn(
            self::MAGENTO_ATTRIBUTE,
            [
                'label' => __('Magento Attribute'),
                'renderer' => $this->getAttributeRenderer()
            ]
        );

        $this->_addAfter = false;
    }

    /**
     * Add a column to array-grid
     *
     * @param string $name
     * @param array $params
     * @return void
     */
    public function addColumn($name, $params)
    {
        $this->_columns[$name] = [
            'label' => $this->_getParam($params, 'label', 'Column'),
            'size' => $this->_getParam($params, 'size', false),
            'style' => $this->_getParam($params, 'style'),
            'class' => $this->_getParam($params, 'class'),
            'readonly' => $this->_getParam($params, 'readonly', false),
            'renderer' => false,
        ];

        if (!empty($params['renderer'])
            && $params['renderer'] instanceof \Magento\Framework\View\Element\AbstractBlock
        ) {
            $this->_columns[$name]['renderer'] = $params['renderer'];
        }
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $attribute = $row->getMagentoAttribute();
        if ($attribute !== null) {
            $options['option_' . $this->getAttributeRenderer()->calcOptionHash($attribute)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return AttributeColumn
     * @throws LocalizedException
     */
    private function getAttributeRenderer()
    {
        if (!$this->attributeRenderer) {
            $this->attributeRenderer = $this->getLayout()->createBlock(
                AttributeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->attributeRenderer;
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @param bool $newRow
     * @return string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName, $newRow = false)
    {
        if (empty($this->_columns[$columnName])) {
            throw new \Exception('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];
        $inputName = $this->_getCellInputElementName($columnName);

        if ($column['renderer']) {
            return $column['renderer']->setInputName(
                $inputName
            )->setInputId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setColumnName(
                $columnName
            )->setColumn(
                $column
            )->toHtml();
        }

        return '<input type="text" id="' . $this->_getCellInputElementId(
            '<%- _id %>',
            $columnName
        ) .
            '"' .
            ' name="' .
            $inputName .
            '" value="<%- ' .
            $columnName .
            ' %>" ' .
            ($column['size'] ? 'size="' .
                $column['size'] .
                '"' : '') .
            ($column['readonly'] && !$newRow ? 'readonly="' .
                $column['readonly'] .
                '"' : '') .
            ' class="' .
            (isset($column['class'])
                ? $column['class']
                : 'input-text') . '"' . (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';
    }
}
