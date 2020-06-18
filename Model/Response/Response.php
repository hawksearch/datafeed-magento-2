<?php
/**
 *  Copyright (c) 2020 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 *  FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 *  IN THE SOFTWARE.
 */

namespace HawkSearch\Datafeed\Model\Response;

use HawkSearch\Datafeed\Api\Data\ResponseInterface;
use Magento\Framework\DataObject;

class Response extends DataObject implements ResponseInterface
{
    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getData(static::STATUS);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setStatus(string $value)
    {
        return $this->setData(static::STATUS, $value);
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->getData(static::MESSAGE);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setMessage(string $value)
    {
        return $this->setData(static::MESSAGE, $value);
    }

    /**
     * @return mixed
     */
    public function getResponseData()
    {
        return $this->getData(static::RESPONSE_DATA);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setResponseData($value)
    {
        return $this->setData(static::RESPONSE_DATA, $value);
    }
}
