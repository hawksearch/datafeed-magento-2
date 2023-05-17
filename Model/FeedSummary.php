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

use HawkSearch\Datafeed\Api\Data\FeedSummaryInterface;
use Magento\Framework\DataObject;

class FeedSummary extends DataObject implements FeedSummaryInterface
{
    /**
     * @return array
     */
    public function getStores(): array
    {
        return $this->getData(self::STORES);
    }

    /**
     * @param array $value
     * @return $this
     */
    public function setStores(array $value)
    {
        return $this->setData(self::STORES, $value);
    }

    /**
     * @return string
     */
    public function getComplete(): string
    {
        return $this->getData(self::COMPLETE);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setComplete(string $value)
    {
        return $this->setData(self::COMPLETE, $value);
    }
}
