<?php

namespace Xanka\SplitCart\Plugin\Quote\Api;

use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface as Subject;
use Magento\Quote\Api\CartRepositoryInterface;
class CartTotalRepository
{
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
    )
    {}

    /**
     * After plugin for CartTotalRepository to calculate totals based on selected items.
     * @param Subject $subject
     * @param TotalsInterface $result
     * @param $cartId
     * @return TotalsInterface
     */
    public function afterGet(Subject $subject, TotalsInterface $result, $cartId): TotalsInterface
    {
        try {
            $quote = $this->cartRepository->get($cartId);
            if (!$quote || !$quote->getId()) {
                return $result; // Return original result if quote is not available
            }

            // calculate totals based on selected items
            $selectedSubtotal = 0;
            $selectedItemsQty = 0;
            $selectedItemIds = [];

            foreach ($quote->getItems() as $item) {
                $isSelected = $item->getData('selected_item') == 1 || $item->getData('selected_item') === null; // default to selected if attribute doesn't exist
                if ($isSelected) {
                    $selectedSubtotal += $item->getQty() * $item->getPrice(); // calculate subtotal for selected items
                    $selectedItemsQty += $item->getQty();
                    $selectedItemIds[] = $item->getItemId();
                }
            }

            // origin grand total
            $originalGrandTotal = $result->getGrandTotal();
            $originalSubtotal = $result->getSubtotal();

            // calculate the difference (shipping, tax, fee, etc.)
            $additionalCharges = $originalGrandTotal - $originalSubtotal;

            // new grand total based on selected items
            $newGrandTotal = $selectedSubtotal + $additionalCharges;
            // Update the totals object with comprehensive calculations
            $result->setSubtotal($selectedSubtotal);
            $result->setBaseSubtotalInclTax($selectedSubtotal); // set base subtotal including tax if applicable
            $result->setBaseSubtotal($selectedSubtotal); // set base subtotal
            $result->setItemsQty($selectedItemsQty);
            $result->setGrandTotal($newGrandTotal); // set grand total including additional charges
            $result->setBaseGrandTotal($newGrandTotal); // set base grand total if applicable
            $result->setSubtotalWithDiscount($selectedSubtotal); // set subtotal with discount if applicable
            $result->setBaseSubtotalWithDiscount($selectedSubtotal); // set base subtotal with discount if applicable
            // Update total segments
            foreach ($result->getTotalSegments() as $segment) {
                // Update each segment if necessary, e.g., shipping, tax, etc.
                if ($segment->getCode() === 'subtotal') {
                    $segment->setValue($selectedSubtotal);
                }
                if ($segment->getCode() === 'grand_total') {
                    $segment->setValue($newGrandTotal);
                }
            }
            $result->setTotalSegments($result->getTotalSegments());

            // filter item in the totals to only include selected items
            $filteredItems = [];
            foreach ($result->getItems() as $item) {
                // Check if the item is selected based on the selected_item attribute
                if (in_array($item->getItemId(), $selectedItemIds)) {
                    $filteredItems[] = $item;
                }
            }
            // Set the filtered items back to the totals object
            $result->setItems($filteredItems);

        } catch (\Exception $e) {
            // Handle exception as needed, possibly log it
        }

        return $result;
    }
}
