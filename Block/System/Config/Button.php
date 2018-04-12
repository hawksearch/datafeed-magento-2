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

namespace HawkSearch\Datafeed\Block\System\Config;

use HawkSearch\Datafeed\Helper\Data as Helper;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Button extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $helper;

    /**
     * Button constructor.
     * @param Context $context
     * @param Helper    $helper
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Helper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout() {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('HawkSearch_Datafeed::system/config/button/feedgenerate.phtml');
        }
        return $this;
    }

    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(AbstractElement $element) {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element) {
        $config = $element->getFieldConfig();
        $this->addData(
            [
                'button_label' => $config['button_label'],
                'generate_url' => $this->getUrl($config['button_url']),
                'html_id' => $element->getHtmlId(),
            ]
        );
        return $this->_toHtml();
    }

    public function isFeedLocked() {
        return $this->helper->isFeedLocked();
    }
}
 