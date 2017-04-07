<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Block\Adminhtml\Sales\Items\Column\Name;

class Giftcard extends \Magento\GiftCard\Block\Adminhtml\Sales\Items\Column\Name\Giftcard
{
    /**
     * Get gift card option list.
     *
     * @return array
     */
    protected function _getGiftcardOptions()
    {
        $result = [];
        if ($type = $this->getItem()->getProductOptionByCode('giftcard_type')) {
            switch ($type) {
                case \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL:
                    $type = __('Virtual');
                    break;
                case \Magento\GiftCard\Model\Giftcard::TYPE_PHYSICAL:
                    $type = __('Physical');
                    break;
                case \Magento\GiftCard\Model\Giftcard::TYPE_COMBINED:
                    $type = __('Combined');
                    break;
            }

            $result[] = ['label' => __('Gift Card Type'), 'value' => $type];
        }

        if ($value = $this->_prepareCustomOption('giftcard_sender_name')) {
            if ($email = $this->_prepareCustomOption('giftcard_sender_email')) {
                $value = "{$value} &lt;{$email}&gt;";
            }
            $result[] = ['label' => __('Gift Card Sender'), 'value' => $value, 'custom_view' => true];
        }
        if ($value = $this->_prepareCustomOption('giftcard_recipient_name')) {
            if ($email = $this->_prepareCustomOption('giftcard_recipient_email')) {
                $value = "{$value} &lt;{$email}&gt;";
            }
            $result[] = ['label' => __('Gift Card Recipient'), 'value' => $value, 'custom_view' => true];
        }
        if ($value = $this->_prepareCustomOption('giftcard_message')) {
            $result[] = ['label' => __('Gift Card Message'), 'value' => $value];
        }
        if ($value = $this->getItem()->getProductOptions()['info_buyRequest']['giftcard_date_of_delivery']) {
            $result[] = ['label' => __('Gift Card Date of Delivery'), 'value' => $value];
        }

        if ($value = $this->_prepareCustomOption('giftcard_lifetime')) {
            $result[] = ['label' => __('Gift Card Lifetime'), 'value' => sprintf('%s days', $value)];
        }

        $yes = __('Yes');
        $no = __('No');
        if ($value = $this->_prepareCustomOption('giftcard_is_redeemable')) {
            $result[] = ['label' => __('Gift Card Is Redeemable'), 'value' => $value ? $yes : $no];
        }

        $createdCodes = 0;
        $totalCodes = $this->getItem()->getQtyOrdered();
        if ($codes = $this->getItem()->getProductOptionByCode('giftcard_created_codes')) {
            $createdCodes = count($codes);
        }

        if (is_array($codes)) {
            foreach ($codes as &$code) {
                if ($code === null) {
                    $code = __('We cannot create this gift card.');
                }
            }
        } else {
            $codes = [];
        }

        for ($i = $createdCodes; $i < $totalCodes; ++$i) {
            $codes[] = __('N/A');
        }

        $result[] = [
            'label' => __('Gift Card Accounts'),
            'value' => implode('<br />', $codes),
            'custom_view' => true,
        ];

        return $result;
    }
}
