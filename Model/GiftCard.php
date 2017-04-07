<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class GiftCard.
 */
class GiftCard extends AbstractModel
{
    /**
     * Define resource model.
     */
    protected function _construct()
    {
        $this->_init('Rag\GiftCard\Model\ResourceModel\GiftCard');
    }
}
