<?php

namespace Xanka\SplitCart\Plugin\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Controller\Onepage\Success as CheckoutSuccess;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;


class Success
{
    public function __construct(
        protected CheckoutSession $checkoutSession,
        protected \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        protected CartManagementInterface $cartManagementInterface
    ) {
    }

    /**
     * After plugin for CheckoutSuccess to handle unselected items.
     * @param CheckoutSuccess $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(CheckoutSuccess $subject, $result)
    {
        // Retrieve unselected items from the session
        $unselectedItems = $this->checkoutSession->getUnselectedItems();

        if (!empty($unselectedItems)) {
            try {
                // get customer ID to create a new cart
                $customerId = $this->checkoutSession->getCustomerId();
                // create new cart for the customer or guest
               if ($customerId) {
                    $cartId = $this->cartManagementInterface->createEmptyCartForCustomer($customerId);
                } else {
                   $cartId = $this->cartManagementInterface->createEmptyCart();
               }

               // get new quote
                $quote = $this->cartRepository->get($cartId);

               if (!$quote || !$quote->getId()) {
                   // log error or handle it as needed
                    return $result; // Return original result if quote is not available
                }
                // add unselected items to the new cart
                foreach ($unselectedItems as $itemData) {
                    try {
                        // Load product by SKU
                        $product = $this->productRepository->get($itemData['sku']);

                        // check if product is available for purchase
//                        if (!$product->isSaleable()) {
//                            continue; // Skip if product is not available for purchase
//                        }

                        $request = new \Magento\Framework\DataObject(
                            [
                                'qty' => $itemData['qty'] ?? 1, // Default to 1 if qty is not set
                                'product_id' => $itemData['product_id'],
                            ]
                        );
                        // Create a new quote item
                        $addedItem = $quote->addProduct($product, $request);

                        if (!$addedItem) {
                            continue; // Skip if item could not be added
                        }

                       // set as unselected (0) by default
                        $addedItem->setData('selected_item', 0);

                        // Set options data if needed
                        if (isset($itemData['options'])) {
                            $addedItem->setOptions($itemData['options']);
                        }

                        // Set custom price if available
                        if (isset($itemData['custom_price'])) {
                            $addedItem->setCustomPrice($itemData['custom_price']);
                            $addedItem->setOriginalCustomPrice($itemData['custom_price']);
                        }
                    } catch (\Magento\Framework\Exception\NoSuchEntityException|\Magento\Framework\Exception\LocalizedException $e) {
                        // Log the error or handle it as needed
                        continue;
                    }
                }
                // Save the quote after adding unselected items
                // @todo: have error with customer assignment, need to check
                if ($customerId) {
                    $this->cartRepository->save($quote);
                } else {
                    $quote->collectTotals();
                    $this->cartRepository->save($quote);

                }
                // clear the current quote in the session and set the new cart
//                $this->checkoutSession->replaceQuote($quote);
                $this->checkoutSession->clearQuote();
                $this->checkoutSession->setQuoteId($quote->getId());

                // Clear the unselected items from the session
                $this->checkoutSession->unsUnselectedItems();
            } catch (\Exception $e) {
                // Handle any exceptions that may occur
                // We can log the error or display a message to the user
            }
        }
        return $result;
    }
}
