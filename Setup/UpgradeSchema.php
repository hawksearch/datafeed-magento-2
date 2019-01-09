<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 1/9/19
 * Time: 2:33 PM
 */

namespace HawkSearch\Datafeed\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.2.8', '<')) {
            $this->upgrade_228($setup);
        }
    }

    private function upgrade_228($setup)
    {
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
        return $this;
    }
}