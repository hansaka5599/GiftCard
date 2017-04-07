<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Model\Giftcard;

use Magento\GiftCard\Api\Data\GiftCardOptionInterface;

/**
 * Class Option.
 */
class Option extends \Magento\GiftCard\Model\Giftcard\Option implements GiftCardOptionInterface
{
    /**#@+
     * Constants
     */
    const KEY_DATE_OF_DELIVERY = 'giftcard_date_of_delivery';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function getGiftcardDateOfDelivery()
    {
        return $this->getData(self::KEY_DATE_OF_DELIVERY);
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftcardDateOfDelivery($value)
    {
        return $this->setData(self::KEY_DATE_OF_DELIVERY, $value);
    }
}
