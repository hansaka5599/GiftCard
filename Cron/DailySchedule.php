<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Cron;

/**
 * Class DailySchedule.
 */
class DailySchedule
{
    /**
     * Gift card model
     *
     * @var null|\Rag\GiftCard\Model\GiftCard
     */
    protected $ragGiftCardModel = null;

    /**
     * Sales order
     *
     * @var \Magento\Sales\Model\Order|null
     */
    protected $salesOrder = null;

    /**
     * State
     *
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Scope config.
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Gift card data.
     *
     * @var \Magento\GiftCard\Helper\Data
     */
    protected $giftCardData = null;

    /**
     * Currency
     *
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $localeCurrency;

    /**
     * Transport builder
     *
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * DailySchedule constructor.
     * @param \Rag\GiftCard\Model\GiftCard $ragGiftCardModel
     * @param \Magento\Sales\Model\Order $salesOrder
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Rag\GiftCard\Model\GiftCard $ragGiftCardModel,
        \Magento\Sales\Model\Order $salesOrder,
        \Magento\Framework\App\State $state,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\GiftCard\Helper\Data $giftCardData,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->ragGiftCardModel = $ragGiftCardModel;
        $this->salesOrder = $salesOrder;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->giftCardData = $giftCardData;
        $this->localeCurrency = $localeCurrency;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Execute method
     */
    public function execute()
    {
        $giftcards = $this->ragGiftCardModel->getCollection()
            ->addFieldToFilter('email_sent_status', '0')
            ->addFieldToFilter('date_of_delivery', ['eq' => date('Y-m-d')]);

        foreach ($giftcards as $giftcard) {
            $orderId = $giftcard->getOrderId();

            $order = $this->salesOrder->load($orderId);
            foreach ($order->getAllItems() as $item) {
                /**
                 * if the order has 2 or more gift cards with delivery date (current date) it will be handled in the
                 * next iteration of the loop.
                 * With this logic order will be loaded and looped twice, but kept as it is as this is a rare case.
                 */
                if ($item->getQuoteItemId() != $giftcard->getOrderItemId()) {
                    continue;
                }
                if ($item->getProductType() == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD) {
                    $item->setOrder($order); // MAGENTO BUG: has to set Order to order item manually here to fix
                    $options = $item->getProductOptions();

                    $isRedeemable = 0;
                    $option = $item->getProductOptionByCode('giftcard_is_redeemable');
                    if ($option) {
                        $isRedeemable = $option;
                    }

                    $amount = $item->getBasePrice();
                    $codes = isset($options['giftcard_created_codes']) ? $options['giftcard_created_codes'] : [];
                    $goodCodes = count($codes);

                    if ($goodCodes && $item->getProductOptionByCode('giftcard_recipient_email')) {
                        $sender = $item->getProductOptionByCode('giftcard_sender_name');
                        $senderName = $item->getProductOptionByCode('giftcard_sender_name');
                        $senderEmail = $item->getProductOptionByCode('giftcard_sender_email');
                        if ($senderEmail) {
                            $sender = "{$sender} <{$senderEmail}>";
                        }

                        // barcode related data
                        $barcodeStatus = $this->scopeConfig->getValue(
                            \Rag\GiftCard\Observer\GenerateGiftCardAccounts::XML_PATH_BARCODE_STATUS,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );

                        $barcodeUrl = $this->scopeConfig->getValue(
                            \Rag\GiftCard\Observer\GenerateGiftCardAccounts::XML_PATH_BARCODE_URL,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );

                        $barcodePwd = $this->scopeConfig->getValue(
                            \Rag\GiftCard\Observer\GenerateGiftCardAccounts::XML_PATH_BARCODE_PWD,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );

                        $barcodeText = $this->scopeConfig->getValue(
                            \Rag\GiftCard\Observer\GenerateGiftCardAccounts::XML_PATH_BARCODE_TEXT,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );

                        $codeList = $this->giftCardData->getEmailGeneratedItemsBlock()->setCodes(
                            $codes
                        )->setArea(
                            \Magento\Framework\App\Area::AREA_FRONTEND
                        )->setIsRedeemable(
                            $isRedeemable
                        )->setStore(
                            $this->storeManager->getStore($order->getStoreId())
                        )->setBarcodeData(
                            [
                                'status' => $barcodeStatus,
                                'url' => $barcodeUrl . '?pwd=' . $barcodePwd . '&txt=' . $barcodeText
                            ]
                        );
                        $balance = $this->localeCurrency->getCurrency(
                            $this->storeManager->getStore($order->getStoreId())->getBaseCurrencyCode()
                        )->toCurrency(
                            $amount
                        );

                        $templateData = [
                            'name' => $item->getProductOptionByCode('giftcard_recipient_name'),
                            'email' => $item->getProductOptionByCode('giftcard_recipient_email'),
                            'sender_name_with_email' => $sender,
                            'sender_name' => $senderName,
                            'gift_message' => $item->getProductOptionByCode('giftcard_message'),
                            'giftcards' => $codeList->toHtml(),
                            'balance' => $balance,
                            'is_multiple_codes' => 1 < $goodCodes,
                            'store' => $order->getStore(),
                            'store_name' => $order->getStore()->getName(),
                            'is_redeemable' => $isRedeemable,
                        ];

                        $transport = $this->transportBuilder->setTemplateIdentifier(
                            $item->getProductOptionByCode('giftcard_email_template')
                        )->setTemplateOptions(
                            [
                                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                                'store' => $item->getOrder()->getStoreId(),
                            ]
                        )->setTemplateVars(
                            $templateData
                        )->setFrom(
                            $this->scopeConfig->getValue(
                                \Magento\GiftCard\Model\Giftcard::XML_PATH_EMAIL_IDENTITY,
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $item->getOrder()->getStoreId()
                            )
                        )->addTo(
                            $item->getProductOptionByCode('giftcard_recipient_email'),
                            $item->getProductOptionByCode('giftcard_recipient_name')
                        )->getTransport();

                        $transport->sendMessage();
                        $options['email_sent'] = 1;

                        /*
                         * Giftcard email send table is updated
                         * if the giftcard email has been sent by the scheduled cron
                         */
                        $giftcard->setEmailSentStatus(1);
                        $giftcard->save();
                    }
                    $item->setProductOptions($options);
                    $item->save();
                }
            }
        }
    }
}
