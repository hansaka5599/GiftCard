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

/**
 * Class GenerateGiftCardAccounts.
 */
class GenerateGiftCardAccounts extends \Magento\GiftCard\Observer\GenerateGiftCardAccounts
{
    /**
     * xml path for barcode status
     */
    const XML_PATH_BARCODE_STATUS = 'barcode/settings/status';

    /**
     * xml path for barcode url
     */
    const XML_PATH_BARCODE_URL = 'barcode/settings/url';

    /**
     * xml path for barcode pwd
     */
    const XML_PATH_BARCODE_PWD = 'barcode/settings/pwd';

    /**
     * xml path for barcode TEXT
     */
    const XML_PATH_BARCODE_TEXT = 'barcode/settings/text';

    /**
     * Gift card data.
     *
     * @var \Magento\GiftCard\Helper\Data
     */
    protected $giftCardData = null;

    /**
     * Scope config.
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Message manager
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Url model.
     *
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $urlModel;

    /**
     * Invoice Repository.
     *
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * Transport builder
     *
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * Invoice items collection factory.
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Invoice\Item\CollectionFactory
     */
    protected $itemsFactory;

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Currency
     *
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $localeCurrency;

    /**
     * Event manager
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * Rag gift card
     *
     * @var null|\Rag\GiftCard\Model\GiftCard
     */
    protected $ragGiftCard = null;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var Resource
     */
    protected $resource;

    /**
     * Constructor
     *
     * GenerateGiftCardAccounts constructor.
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice\Item\CollectionFactory $itemsFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Backend\Model\UrlInterface $urlModel
     * @param \Magento\GiftCard\Helper\Data $giftCardData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Rag\GiftCard\Model\GiftCard $ragGiftCard
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\Item\CollectionFactory $itemsFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\UrlInterface $urlModel,
        \Magento\GiftCard\Helper\Data $giftCardData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Rag\GiftCard\Model\GiftCard $ragGiftCard,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->storeManager = $storeManager;
        $this->localeCurrency = $localeCurrency;
        $this->itemsFactory = $itemsFactory;
        $this->transportBuilder = $transportBuilder;
        $this->invoiceRepository = $invoiceRepository;
        $this->messageManager = $messageManager;
        $this->urlModel = $urlModel;
        $this->giftCardData = $giftCardData;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->ragGiftCard = $ragGiftCard;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Zend_Currency_Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // sales_order_save_after

        $order = $observer->getEvent()->getOrder();
        $requiredStatus = $this->scopeConfig->getValue(
            \Magento\GiftCard\Model\Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );
        $loadedInvoices = [];

        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD) {

                $item->setOrder($order); // MAGENTO BUG: has to set Order to order item manually here to fix
                $qty = 0;
                $options = $item->getProductOptions();

                switch ($requiredStatus) {
                    case \Magento\Sales\Model\Order\Item::STATUS_INVOICED:
                        $paidInvoiceItems = isset(
                            $options['giftcard_paid_invoice_items']
                        ) ? $options['giftcard_paid_invoice_items'] : [];
                        // find invoice for this order item
                        $invoiceItemCollection = $this->itemsFactory->create()->addFieldToFilter(
                            'order_item_id',
                            $item->getId()
                        );

                        foreach ($invoiceItemCollection as $invoiceItem) {
                            $invoiceId = $invoiceItem->getParentId();
                            if (isset($loadedInvoices[$invoiceId])) {
                                $invoice = $loadedInvoices[$invoiceId];
                            } else {
                                $invoice = $this->invoiceRepository->get($invoiceId);
                                $loadedInvoices[$invoiceId] = $invoice;
                            }
                            // check, if this order item has been paid
                            if ($invoice->getState() == \Magento\Sales\Model\Order\Invoice::STATE_PAID && !in_array(
                                    $invoiceItem->getId(),
                                    $paidInvoiceItems
                                )
                            ) {
                                $qty += $invoiceItem->getQty();
                                $paidInvoiceItems[] = $invoiceItem->getId();
                            }
                        }
                        $options['giftcard_paid_invoice_items'] = $paidInvoiceItems;
                        break;
                    default:
                        $qty = $item->getQtyOrdered();
                        if (isset($options['giftcard_created_codes'])) {
                            $qty -= count($options['giftcard_created_codes']);
                        }
                        break;
                }

                $hasFailedCodes = false;
                if ($qty > 0) {
                    $isRedeemable = 0;
                    $option = $item->getProductOptionByCode('giftcard_is_redeemable');
                    if ($option) {
                        $isRedeemable = $option;
                    }

                    $lifetime = 0;
                    $option = $item->getProductOptionByCode('giftcard_lifetime');
                    if ($option) {
                        $lifetime = $option;
                    }

                    $amount = $item->getBasePrice();
                    $websiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();

                    $data = new \Magento\Framework\DataObject();
                    $data->setWebsiteId(
                        $websiteId
                    )->setAmount(
                        $amount
                    )->setLifetime(
                        $lifetime
                    )->setIsRedeemable(
                        $isRedeemable
                    )->setOrderItem(
                        $item
                    );

                    $codes = isset($options['giftcard_created_codes']) ? $options['giftcard_created_codes'] : [];
                    $goodCodes = 0;
                    for ($i = 0; $i < $qty; ++$i) {
                        try {
                            $code = new \Magento\Framework\DataObject();
                            $this->eventManager->dispatch(
                                'magento_giftcardaccount_create',
                                ['request' => $data, 'code' => $code]
                            );
                            $codes[] = $code->getCode();
                            ++$goodCodes;
                        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                            $hasFailedCodes = true;
                            $codes[] = null;
                        }
                    }

                    /**
                     * Add options to rag_giftcards table if the deliver date is greater than invoicing date
                     */
                    $deliveryDate = isset($options['info_buyRequest']['giftcard_date_of_delivery'])
                        ? $options['info_buyRequest']['giftcard_date_of_delivery'] : '';
                    if (!empty($deliveryDate)) {
                        $deliveryDateToDb = date('Y-m-d', strtotime(str_replace('/', '-', $deliveryDate)));
                        // if delivery date is greater than today, then it will be saved to send later
                        if (strtotime($deliveryDateToDb) >= strtotime(date('Y-m-d'))) {
                            // giftcard is saved (to send later) only when the order is invoiced,
                            // it should either be in complete state or processing state.
                            // States are described at the constant declaration above
                            if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_COMPLETE ||
                                $order->getStatus() == \Magento\Sales\Model\Order::STATE_PROCESSING
                            ) {
                                $scheduleTableUpdated = isset($options['schedule_table_updated'])
                                    ? $options['schedule_table_updated'] : 0;
                                $giftcardId = $observer->getEvent()->getGiftcardId();
                                // schedule table is updated only if it has not done before or when it is NOT called
                                // from the scheduled email send cron
                                if (!$scheduleTableUpdated && $giftcardId == null) {
                                    $scheduleError = false;
                                    try {
                                        $this->connection->insert(
                                            $this->resource->getTableName('rag_giftcard'),
                                            [
                                                'order_id' => $item->getOrderId(),
                                                'order_item_id' => $item->getQuoteItemId(),
                                                'date_of_delivery' => $deliveryDateToDb,
                                                'email_sent_status' => 0
                                            ]
                                        );
                                    } catch (Exception $e) {
                                        $scheduleError = true;
                                    }

                                    if ($scheduleError == true) {
                                        // flag is set to identify the items marked in schedule table
                                        $options['schedule_table_updated'] = 1;
                                        $options['giftcard_date_of_delivery'] = $deliveryDate;
                                        $item->setProductOptions($options);
                                        $item->save();
                                    }
                                }
                            }
                            $options['giftcard_created_codes'] = $codes;
                            $item->setProductOptions($options);
                            $item->save();
                            //Bypass email sending for the above criteria
                            continue;
                        }
                    }

                    if ($goodCodes && $item->getProductOptionByCode('giftcard_recipient_email')) {
                        $sender = $item->getProductOptionByCode('giftcard_sender_name');
                        $senderName = $item->getProductOptionByCode('giftcard_sender_name');
                        $senderEmail = $item->getProductOptionByCode('giftcard_sender_email');
                        if ($senderEmail) {
                            $sender = "{$sender} <{$senderEmail}>";
                        }

                        // barcode related data
                        $barcodeStatus = $this->scopeConfig->getValue(
                            self::XML_PATH_BARCODE_STATUS,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );

                        $barcodeUrl = $this->scopeConfig->getValue(
                            self::XML_PATH_BARCODE_URL,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );

                        $barcodePwd = $this->scopeConfig->getValue(
                            self::XML_PATH_BARCODE_PWD,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );

                        $barcodeText = $this->scopeConfig->getValue(
                            self::XML_PATH_BARCODE_TEXT,
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
                    }
                    $options['giftcard_created_codes'] = $codes;
                    $item->setProductOptions($options);
                    $item->save();
                }
                if ($hasFailedCodes) {
                    $url = $this->urlModel->getUrl('adminhtml/giftcardaccount');
                    $message = __(
                        'Some gift card accounts were not created properly. You can create gift card accounts manually <a href="%1">here</a>.',
                        $url
                    );

                    $this->messageManager->addError($message);
                }
            }
        }

        return $this;
    }
}
