<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Helper;

/**
 * Giftcard module helper
 */
class Data extends \Magento\GiftCard\Helper\Data
{
    /**
     * Instantiate giftardaccounts block when a gift card email should be sent
     *
     * @return \Magento\GiftCard\Block\Generated
     */
    public function getEmailGeneratedItemsBlock()
    {
        /** @var $block \Magento\GiftCard\Block\Generated */
        $block = $this->_layout->createBlock('Magento\GiftCard\Block\Generated');
        $block->setTemplate('Rag_GiftCard::email/generated.phtml');
        return $block;
    }
}
