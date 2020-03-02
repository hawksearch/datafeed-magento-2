<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 10/25/18
 * Time: 11:14 AM
 */

namespace HawkSearch\Datafeed\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;

class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var Config
     */
    private $cache;

    /**
     * UpgradeData constructor.
     * @param ConfigInterface $config
     * @param Config $cache
     */
    public function __construct(ConfigInterface $config, Config $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    /**
     * Upgrades data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.1.0.3', '<')) {
            $this->upgradeTo_2103($setup);
        }
        $setup->endSetup();
        if (version_compare($context->getVersion(), '2.2.0.1', '<')) {
            $this->upgrade_2201($setup);
        }
    }

    private function upgradeTo_2103($setup)
    {
        /*
         * configuration changes:
         * hawksearch_datafeed/hawksearch_api/api_mode
         * hawksearch_datafeed/hawksearch_api/api_url_live changed to hawksearch_datafeed/hawksearch_api/api_url_production
         */
        /** @var \Magento\Config\Model\ResourceModel\Config $config */
        $config = $this->config;
        $select = $config->getConnection()
            ->select()
            ->from($setup->getTable('core_config_data'))
            ->where('path in (?)', [
                'hawksearch_datafeed/hawksearch_api/api_mode',
                'hawksearch_datafeed/hawksearch_api/api_url_live'
            ])->order('path');
        foreach ($config->getConnection()->fetchAll($select) as $item) {
            if($item['path'] == 'hawksearch_datafeed/hawksearch_api/api_mode')  {
                if($item['value'] == "0") {
                    $item['value'] = 'develop';
                } else {
                    $item['value'] = 'production';
                }
            } elseif($item['path'] == 'hawksearch_datafeed/hawksearch_api/api_url_live') {
                $item['path'] = 'hawksearch_datafeed/hawksearch_api/api_url_production';
            } else {
                continue;
            }
            $config->saveConfig($item['path'], $item['value'] , $item['scope'], $item['scope_id']);
        }
        $config->deleteConfig('hawksearch_datafeed/hawksearch_api/api_url_live', 'default', 0);
        $this->cache->clean();
    }

    private function upgrade_2201(ModuleDataSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('cms_page'),
            'hawk_exclude',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Exclude from HawkSearch'
            ]
        );
        $setup->endSetup();
    }
}