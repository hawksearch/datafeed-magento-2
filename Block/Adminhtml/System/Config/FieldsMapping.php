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

namespace HawkSearch\Datafeed\Block\Adminhtml\System\Config;

use HawkSearch\Datafeed\Block\Adminhtml\Form\Field\AttributeColumn;
use HawkSearch\Datafeed\Block\Adminhtml\Form\Field\HawksearchFieldColumn;
use HawkSearch\Datafeed\Exception\DataFeedException;
use HawkSearch\Datafeed\Model\Config\Attributes as AttributesConfig;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class FieldsMapping extends AbstractFieldArray
{
    /**
     * @var string
     */
    protected $_template = 'HawkSearch_Datafeed::system/config/form/field/array.phtml';

    /**
     * @var AttributeColumn
     */
    private $attributeRenderer;

    /**
     * @var HawksearchFieldColumn
     */
    private $hawksearchFieldRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            AttributesConfig::HAWK_ATTRIBUTE_CODE,
            [
                'label' => __('Hawksearch Field Name'),
                'class' => 'required-entry',
                'renderer' => $this->getHawksearchFieldRenderer()
            ]
        );
        $this->addColumn(
            AttributesConfig::HAWK_ATTRIBUTE_CODE . '_new',
            [
                'label' => __('Hawksearch Field Name'),
                'class' => 'required-entry validate-code'
            ]
        );
        $this->addColumn(
            AttributesConfig::MAGENTO_ATTRIBUTE,
            [
                'label' => __('Magento Attribute'),
                'renderer' => $this->getAttributeRenderer()
            ]
        );

        $this->_addAfter = false;
    }

    /**
     * @inheritdoc
     */
    public function addColumn($name, $params)
    {
        parent::addColumn($name, $params);
        if (!isset($this->_columns[$name])) {
            return;
        }

        $this->_columns[$name]['readonly'] = $this->_getParam($params, 'readonly', false);
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
     * @return HawksearchFieldColumn
     * @throws LocalizedException
     */
    private function getHawksearchFieldRenderer()
    {
        if (!$this->hawksearchFieldRenderer) {
            $this->hawksearchFieldRenderer = $this->getLayout()->createBlock(
                HawksearchFieldColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->hawksearchFieldRenderer;
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @param bool $newRow
     * @return string
     * @throws DataFeedException
     */
    public function renderCellTemplate($columnName, $newRow = false)
    {
        if (empty($this->_columns[$columnName])) {
            throw new DataFeedException('Wrong column name specified.');
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

    /**
     * @inheritDoc
     */
    public function getColumns()
    {
        $columns = parent::getColumns();
        $resultColumns = [];
        foreach ($columns as $columnName => $column) {
            if (strpos((string) $columnName, '_new', 0 - strlen('_new')) !== false) {
                continue;
            }
            $resultColumns[$columnName] = $column;
        }
        return $resultColumns;
    }

    /**
     * Get column names which have duplicated element for newly inserted data
     * @return array
     */
    public function getNewColumnNames()
    {
        return [AttributesConfig::HAWK_ATTRIBUTE_CODE];
    }
}
