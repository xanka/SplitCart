<?php

namespace Xanka\SplitCart\Plugin\Checkout;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\quote\Api\CartRepositoryInterface;
class QuoteManagement
{
    protected $checkoutSession;
    protected $cartRepository;
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Before plugin for QuoteManagement to handle unselected items.
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param CartInterface $quote
     * @param $orderData
     * @return array
     */
    public function beforeSubmit(\Magento\Quote\Model\QuoteManagement $subject, CartInterface $quote, $orderData = [])
    {
        $unselectedItems = [];
        $itemToRemove = [];
        foreach ($quote->getItems() as $item) {
            if ($item->getData('selected_item') == 0) {
                $unselectedItems[] = [
                    'item_id' => (int)$item->getItemId(),
                    'product_id' => (int)$item->getProductId(),
                    'name' => (string)$item->getName(),
                    'sku' => (string)$item->getSku(),
                    'product_type' => (string)$item->getProductType(),
                    'price' => (float)$item->getPrice(),
                    'qty' => (int)$item->getQty(),
                    'selected_item' =>(int)$item->getData('selected_item')
                ];
            }
            $itemToRemove[] = $item;
        }

        // remove unselected items from the quote
        foreach ($itemToRemove as $item) {
            if ($item->getData('selected_item') == 0) {
                $quote->removeItem($item->getItemId());
            }
        }
        // store unselected items in the session
        $this->checkoutSession->setUnselectedItems($unselectedItems);

        return [$quote, $orderData];
    }

}
