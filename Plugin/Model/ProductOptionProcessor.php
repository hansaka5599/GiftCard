<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Plugin\Model;

use Magento\Framework\DataObject;
/**
 * Class ProductOptionProcessor.
 */
class ProductOptionProcessor extends \Magento\GiftCard\Model\ProductOptionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function aroundConvertToProductOption(
        \Magento\GiftCard\Model\ProductOptionProcessor $subject,
        \Closure $proceed,
        DataObject $request
    ) {
        $allowedOptions = [
            'giftcard_amount',
            'giftcard_sender_name',
            'giftcard_recipient_name',
            'giftcard_sender_email',
            'giftcard_recipient_email',
            'giftcard_date_of_delivery',
            'giftcard_message',
        ];

        $options = [];
        foreach ($allowedOptions as $optionKey) {
            $optionValue = $request->getData($optionKey);
            if ($optionValue) {
                $options[$optionKey] = $optionValue;
            }
        }

        if (!empty($options) && is_array($options)) {
            /** @var GiftcardOption $giftOption */
            $giftOption = $this->giftCardOptionFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $giftOption,
                $options,
                'Magento\GiftCard\Api\Data\GiftCardOptionInterface'
            );

            return ['giftcard_item_option' => $giftOption];
        };

        return [];
    }
}
