<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Block\Sales\Order\Item;

/**
 * Class Renderer.
 */
class Renderer extends \Magento\GiftCard\Block\Sales\Order\Item\Renderer
{
    /**
     * Get gift card option list.
     *
     * @return array
     */
    protected function _getGiftcardOptions()
    {
        $result = [];
        if ($value = $this->_prepareCustomOption('giftcard_sender_name')) {
            if ($email = $this->_prepareCustomOption('giftcard_sender_email')) {
                $value = $this->_getNameEmailString($value, $email);
            }
            $result[] = ['label' => __('Gift Card Sender'), 'value' => $value];
        }
        if ($value = $this->_prepareCustomOption('giftcard_recipient_name')) {
            if ($email = $this->_prepareCustomOption('giftcard_recipient_email')) {
                $value = $this->_getNameEmailString($value, $email);
            }
            $result[] = ['label' => __('Gift Card Recipient'), 'value' => $value];
        }
        if ($value = $this->getItem()->getProductOptions()['info_buyRequest']['giftcard_date_of_delivery']) {
            $result[] = ['label' => __('Date of Delivery'), 'value' => $value];
        }
        if ($value = $this->_prepareCustomOption('giftcard_message')) {
            $result[] = ['label' => __('Gift Card Message'), 'value' => $value];
        }

        return $result;
    }
}
