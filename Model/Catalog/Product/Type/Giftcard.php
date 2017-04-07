<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Model\Catalog\Product\Type;

/**
 * Class Giftcard.
 */
class Giftcard extends \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard
{
    /**
     * Prepare product and its configuration to be added to some products list.
     * Use standard preparation process and also add specific giftcard options.
     *
     * @param \Magento\Framework\DataObject  $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param string                         $processMode
     *
     * @return \Magento\Framework\Phrase|array|string
     */
    protected function _prepareProduct(\Magento\Framework\DataObject $buyRequest, $product, $processMode)
    {
        $result = parent::_prepareProduct($buyRequest, $product, $processMode);

        if (is_string($result)) {
            return $result;
        }

        try {
            $amount = $this->_validate($buyRequest, $product, $processMode);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
            $this->_logger->critical($e);

            return __('Something went wrong while preparing the gift card.');
        }

        $product->addCustomOption('giftcard_amount', $amount, $product);
        $product->addCustomOption('giftcard_sender_name', $buyRequest->getGiftcardSenderName(), $product);
        $product->addCustomOption('giftcard_recipient_name', $buyRequest->getGiftcardRecipientName(), $product);
        if (!$this->isTypePhysical($product)) {
            $product->addCustomOption('giftcard_sender_email', $buyRequest->getGiftcardSenderEmail(), $product);
            $product->addCustomOption('giftcard_recipient_email', $buyRequest->getGiftcardRecipientEmail(), $product);
            $product->addCustomOption('giftcard_date_of_delivery', $buyRequest->getGiftcardDateOfDelivery(), $product);
        }

        $messageAllowed = false;
        if ($product->getUseConfigAllowMessage()) {
            $messageAllowed = $this->_scopeConfig->isSetFlag(
                \Magento\GiftCard\Model\Giftcard::XML_PATH_ALLOW_MESSAGE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            $messageAllowed = (int) $product->getGiftMessageAvailable();
        }

        if ($messageAllowed) {
            $product->addCustomOption('giftcard_message', $buyRequest->getGiftcardMessage(), $product);
        }

        return $result;
    }

    /**
     * Validate Gift Card product, determine and return its amount.
     *
     * @param \Magento\Framework\DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $processMode
     * @return int|mixed|null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _validate(\Magento\Framework\DataObject $buyRequest, $product, $processMode)
    {
        $isStrictProcessMode = $this->_isStrictProcessMode($processMode);
        $allowedAmounts = $this->_getAllowedAmounts($product);
        $allowOpen = $product->getAllowOpenAmount();
        $selectedAmount = $buyRequest->getGiftcardAmount();
        $customAmount = $this->_getCustomGiftcardAmount($buyRequest);
        $this->_checkFields($buyRequest, $product, $isStrictProcessMode);

        $amount = null;
        if (($selectedAmount == 'custom' || !$selectedAmount) && $allowOpen) {
            if ($customAmount <= 0 && $isStrictProcessMode) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please specify a gift card amount.'));
            }
            $amount = $this->_getAmountWithinConstraints($product, $customAmount, $isStrictProcessMode);
        } elseif (is_numeric($selectedAmount)) {
            if (in_array($selectedAmount, $allowedAmounts)) {
                $amount = $selectedAmount;
            }
        }

        $amount = $this->_getAmountFromAllowed($amount, $allowedAmounts);

        if ($isStrictProcessMode) {
            $this->_checkGiftcardFields($buyRequest, $this->isTypePhysical($product), $amount);
        }

        if ($this->isTypeVirtual($product) && $buyRequest->getGiftcardDateOfDelivery()) {
            $deliveryDate = $buyRequest->getGiftcardDateOfDelivery();

            $validator = new \Zend_Validate_Date('DD/MM/YYYY');
            if($validator->isValid($deliveryDate)) {
                if (strtotime(str_replace('/', '-', $deliveryDate)) <= strtotime(str_replace('/', '-', date('d/m/Y')))) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Date of delivery should be a future date.'));
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid date format in date of delivery.'));
            }
        }

        return $amount;
    }

    /**
     * Prepare selected options for giftcard.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\DataObject  $buyRequest
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function processBuyRequest($product, $buyRequest)
    {
        $options = [
            'giftcard_amount' => $buyRequest->getGiftcardAmount(),
            'custom_giftcard_amount' => $buyRequest->getCustomGiftcardAmount(),
            'giftcard_sender_name' => $buyRequest->getGiftcardSenderName(),
            'giftcard_sender_email' => $buyRequest->getGiftcardSenderEmail(),
            'giftcard_recipient_name' => $buyRequest->getGiftcardRecipientName(),
            'giftcard_recipient_email' => $buyRequest->getGiftcardRecipientEmail(),
            'giftcard_date_of_delivery' => $buyRequest->getGiftcardDateOfDelivery(),
            'giftcard_message' => $buyRequest->getGiftcardMessage(),
        ];

        return $options;
    }
}
