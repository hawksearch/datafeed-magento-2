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

namespace HawkSearch\Datafeed\Model\Config\Admin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class StoreViewConfigResolver
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StoreInterface
     */
    private $currentStoreIdBackup;

    /**
     * ConfigurationStoreViewResolver constructor.
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    /**
     * Resolve store by URL parameters in system configuration  and switch current store
     * @param bool $switch
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function resolve($switch = false)
    {
        $storeId = $this->request->getParam('store', Store::DEFAULT_STORE_ID);
        if ($switch) {
            $this->currentStoreIdBackup = $this->storeManager->getStore();
            $this->storeManager->setCurrentStore($storeId);
        }
        return $this->storeManager->getStore($storeId);
    }

    /**
     * Reset current store form backup
     */
    public function unresolve()
    {
        if ($this->currentStoreIdBackup instanceof StoreInterface) {
            $this->storeManager->setCurrentStore($this->currentStoreIdBackup);
            $this->currentStoreIdBackup = null;
        }
    }
}
