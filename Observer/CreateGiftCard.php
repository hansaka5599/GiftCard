<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Observer;

use Magento\Framework\Event\ObserverInterface;

class CreateGiftCard implements ObserverInterface
{
    /**
     * Gift card account giftcardaccount
     *
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory
     */
    protected $giftCAFactory = null;

    /**
     * @param \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
     */
    public function __construct(
        \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
    ) {
        $this->giftCAFactory = $giftCAFactory;
    }

    /**
     * Create gift card account on event
     * Set giftcard code to order item
     * used for event: magento_giftcardaccount_create
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $observer->getEvent()->getRequest();
        $code = $observer->getEvent()->getCode();
        $order = $data->getOrder() ?: ($data->getOrderItem()->getOrder() ?: null);

        $model = $this->giftCAFactory->create()->setStatus(
            \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_ENABLED
        )->setWebsiteId(
            $data->getWebsiteId()
        )->setBalance(
            $data->getAmount()
        )->setLifetime(
            $data->getLifetime()
        )->setIsRedeemable(
            $data->getIsRedeemable()
        )->setOrder(
            $order
        )->save();

        $giftCardCode = $data->getOrderItem()->getGiftcardCode();
        if (!empty($giftCardCode)) {
            $giftCardCode .= '|' . $model->getCode();
        } else {
            $giftCardCode = $model->getCode();
        }

        $data->getOrderItem()->setGiftcardCode($giftCardCode);

        $code->setCode($model->getCode());

        return $this;
    }
}
