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
declare(strict_types=1);

namespace HawkSearch\Datafeed\Logger;

use Magento\Framework\Logger\Monolog;
use HawkSearch\Datafeed\Model\ConfigProvider;

class DataFeedLogger extends Monolog
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * DataFeedLogger constructor.
     * @param ConfigProvider $configProvider
     * @param $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        ConfigProvider $configProvider,
        $name,
        array $handlers = [],
        array $processors = []
    ) {
        $this->configProvider = $configProvider;
        parent::__construct(
            $name,
            $handlers,
            $processors
        );
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function debug($message, array $context = []) : bool
    {
        if ($this->configProvider->isLoggingEnabled()) {
            return parent::debug($message, $context);
        } else {
            return false;
        }
    }
}
