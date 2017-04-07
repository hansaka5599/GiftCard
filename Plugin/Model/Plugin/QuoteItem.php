<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Plugin\Model\Plugin;

/**
 * Class QuoteItem.
 */
class QuoteItem
{
    /**
     * Around function to Convert
     *
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem  $subject
     * @param callable                                     $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param array                                        $additional
     *
     * @return \Magento\Sales\Model\Order\Item
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional
    ) {
        /** @var $orderItem \Magento\Sales\Model\Order\Item */
        $orderItem = $proceed($item, $additional);
        /** @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $item;

        $keys = [
            'giftcard_sender_name',
            'giftcard_sender_email',
            'giftcard_recipient_name',
            'giftcard_recipient_email',
            'giftcard_date_of_delivery',
            'giftcard_message',
        ];
        $productOptions = $orderItem->getProductOptions();
        foreach ($keys as $key) {
            $option = $quoteItem->getProduct()->getCustomOption($key);
            if ($option) {
                $productOptions[$key] = $option->getValue();
            }
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $quoteItem->getProduct();
        // set lifetime
        if ($product->getUseConfigLifetime()) {
            $lifetime = $this->_scopeConfig->getValue(
                \Magento\GiftCard\Model\Giftcard::XML_PATH_LIFETIME,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $orderItem->getStore()
            );
        } else {
            $lifetime = $product->getLifetime();
        }
        $productOptions['giftcard_lifetime'] = $lifetime;

        // set is_redeemable
        if ($product->getUseConfigIsRedeemable()) {
            $isRedeemable = $this->_scopeConfig->isSetFlag(
                \Magento\GiftCard\Model\Giftcard::XML_PATH_IS_REDEEMABLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $orderItem->getStore()
            );
        } else {
            $isRedeemable = (int) $product->getIsRedeemable();
        }
        $productOptions['giftcard_is_redeemable'] = $isRedeemable;

        // set email_template
        if ($product->getUseConfigEmailTemplate()) {
            $emailTemplate = $this->_scopeConfig->getValue(
                \Magento\GiftCard\Model\Giftcard::XML_PATH_EMAIL_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $orderItem->getStore()
            );
        } else {
            $emailTemplate = $product->getEmailTemplate();
        }
        $productOptions['giftcard_email_template'] = $emailTemplate;
        $productOptions['giftcard_type'] = $product->getGiftcardType();

        $orderItem->setProductOptions($productOptions);

        return $orderItem;
    }
}
