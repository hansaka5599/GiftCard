<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Block\Catalog\Product\View\Type;

/**
 * Class Giftcard.
 */
class Giftcard extends \Magento\GiftCard\Block\Catalog\Product\View\Type\Giftcard
{
    /**
     * Check whether date of delivery is available
     *
     * @param $product
     * @return bool
     */
    public function isDateOfDeliveryAvailable($product)
    {
        if ($product->getTypeInstance()->isTypeVirtual($product)) {
            return true;
        }

        return false;
    }
}
