<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class GiftCard.
 */
class GiftCard extends AbstractDb
{
    /**
     * Define main table.
     */
    protected function _construct()
    {
        $this->_init('rag_giftcard', 'entity_id');
    }
}
