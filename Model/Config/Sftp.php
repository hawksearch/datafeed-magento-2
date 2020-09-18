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

namespace HawkSearch\Datafeed\Model\Config;

use HawkSearch\Connector\Model\ConfigProvider;

class Sftp extends ConfigProvider
{
    /**#@+
     * Configuration paths
     */
    const CONFIG_ENABLED = 'enabled';
    const CONFIG_HOST = 'host';
    const CONFIG_USERNAME = 'username';
    const CONFIG_PASSWORD = 'password';
    const CONFIG_FOLDER = 'folder';
    /**#@-*/

    /**
     * @param null|int|string $store
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        return (bool)$this->getConfig(self::CONFIG_ENABLED, $store);
    }

    /**
     * @param null|int|string $store
     * @return string | null
     */
    public function getHost($store = null): ?string
    {
        return $this->getConfig(self::CONFIG_HOST, $store);
    }

    /**
     * @param null|int|string $store
     * @return string | null
     */
    public function getUsername($store = null): ?string
    {
        return $this->getConfig(self::CONFIG_USERNAME, $store);
    }

    /**
     * @param null|int|string $store
     * @return string | null
     */
    public function getPassword($store = null): ?string
    {
        return $this->getConfig(self::CONFIG_PASSWORD, $store);
    }

    /**
     * @param null|int|string $store
     * @return string | null
     */
    public function getFolder($store = null): ?string
    {
        return $this->getConfig(self::CONFIG_FOLDER, $store);
    }
}
