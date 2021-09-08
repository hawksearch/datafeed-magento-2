<?php
/**
 * Copyright (c) 2021 Hawksearch (www.hawksearch.com) - All Rights Reserved
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

namespace HawkSearch\Datafeed\Block\Adminhtml\Form\Field;

use HawkSearch\Datafeed\Model\Config\Source\HawksearchFields;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class HawksearchFieldColumn extends Select
{
    /**
     * @var HawksearchFields
     */
    private $hawksearchFields;

    /**
     * AttributeColumn constructor.
     * @param Context $context
     * @param HawksearchFields $hawksearchFields
     * @param array $data
     */
    public function __construct(
        Context $context,
        HawksearchFields $hawksearchFields,
        array $data = []
    ) {
        $this->hawksearchFields = $hawksearchFields;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * @return array
     */
    private function getSourceOptions(): array
    {
        return $this->hawksearchFields->toOptionArray();
    }
}
