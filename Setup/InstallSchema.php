<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema.
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Install custom table
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        // Get rag_giftcard table
        $tableName = $installer->getTable('rag_giftcard');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            // Create rag_giftcard table
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                    ],
                    'Entity Id'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'default' => '0',
                    ],
                    'Order ID'
                )
                ->addColumn(
                    'order_item_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'default' => '0',
                    ],
                    'Order Item Id'
                )
                ->addColumn(
                    'date_of_delivery',
                    Table::TYPE_DATE,
                    null,
                    [
                        'nullable' => false,
                        'default' => '0000:00:00',
                    ],
                    'Date of Delivery'
                )
                ->addColumn(
                    'email_sent_status',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'default' => '1',
                    ],
                    'Email sent status'
                )
                ->setComment('RAG Gift Card Date Of Delivery Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
