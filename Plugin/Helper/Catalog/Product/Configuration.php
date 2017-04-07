<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Plugin\Helper\Catalog\Product;

/**
 * Class Configuration.
 */
class Configuration
{
    /**
     * Around function to GetGiftcardOptions
     *
     * @param \Magento\GiftCard\Helper\Catalog\Product\Configuration          $subject
     * @param \Closure                                                        $proceed
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
     *
     * @return array
     */
    public function aroundGetGiftcardOptions(
        \Magento\GiftCard\Helper\Catalog\Product\Configuration $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
    ) {
        $result = [];
        $value = $subject->prepareCustomOption($item, 'giftcard_sender_name');
        if ($value) {
            $email = $subject->prepareCustomOption($item, 'giftcard_sender_email');
            if ($email) {
                $value = "{$value} &lt;{$email}&gt;";
            }
            $result[] = ['label' => __('Gift Card Sender'), 'value' => $value];
        }

        $value = $subject->prepareCustomOption($item, 'giftcard_recipient_name');
        if ($value) {
            $email = $subject->prepareCustomOption($item, 'giftcard_recipient_email');
            if ($email) {
                $value = "{$value} &lt;{$email}&gt;";
            }
            $result[] = ['label' => __('Gift Card Recipient'), 'value' => $value];
        }

        $value = $subject->prepareCustomOption($item, 'giftcard_date_of_delivery');
        if ($value) {
            $result[] = ['label' => __('Date of Delivery'), 'value' => $value];
        }

        $value = $subject->prepareCustomOption($item, 'giftcard_message');
        if ($value) {
            $result[] = ['label' => __('Gift Card Message'), 'value' => $value];
        }

        return $result;
    }
}
