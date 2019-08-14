<?php

$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('delivrawebhook/queue'))
    ->addColumn('webhookqueue_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true
    ), 'WebHook Queue Id')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'default'   => '0',
    ), 'Store Id')
    ->addColumn('serialized_data', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
    ), 'Serialized Data')
    ->addColumn('queue_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'default'   => '0',
    ), 'Queue Status')
    ->addColumn('added_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Added At')
    ->addColumn('last_attempt_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Last Attempt At')
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false
    ), 'Entity Id')
    ->addColumn('entity_type', Varien_Db_Ddl_Table::TYPE_TEXT, '16', array(
    ), 'Entity Type')
    ->addColumn('next_attempt_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Next Attempt At')
    ->addColumn('retry_count', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false
    ), 'Retry Count');
$installer->getConnection()->createTable($table);

$installer->endSetup();
