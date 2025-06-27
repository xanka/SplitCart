<?php
namespace Xanka\SplitCart\Plugin\Checkout;
class DefaultConfigProvider
{
    public function __construct(
        protected \Magento\Checkout\Model\Session $checkoutSession
    ){}

    /**
     * After plugin for DefaultConfigProvider to filter quote items based on selected_item attribute.
     * @param \Magento\Checkout\Model\DefaultConfigProvider $subject
     * @param array $result
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, array $result): array
    {
        // filter quote items to only include those with selected_item = 1
        if (isset($result['quoteItemData']) && is_array($result['quoteItemData'])) {
            $filteredItems = [];

            // get the actual quote to check selected_item attribute
            $quote = $this->checkoutSession->getQuote();
            foreach ($quote->getAllVisibleItems() as $item) {
                // map item id to selected_item value
                $itemId = $item->getItemId();
                $selectedItem = $item->getData('selected_item');
            }
            // filter items based on selected_item attribute
            foreach ($result['quoteItemData'] as $itemData) {
                // check if item is selected
                $isSelected = isset($itemData['selected_item']) && $itemData['selected_item'] == 1;
                if ($isSelected) {
                    $filteredItems[] = $itemData;
                }
            }
            $result['quoteItemData'] = $filteredItems;

        }
        // also update totals to reflect only selected items
        if (isset($result['totalsData'])) {
            $result['totalsData'] = $this->updateTotalsForSelectedItems($result['totalsData']);
        }

        return $result;
    }

    /**
     * Update totals based on selected items.
     *
     * @param array $totalsData
     * @return array
     */
    protected function updateTotalsForSelectedItems(array $totalsData)
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            if (!$quote || !$quote->getId()) {
                return $totalsData; // Return original data if quote is not available
            }
            // calculate totals based on selected items
            $selectedSubtotal = 0;
            $selectedTax = 0;
            $selectedItemsCount = 0;
            $selectedItemsQty = 0;
            foreach ($quote->getAllVisibleItems() as $item) {
                $isSelected = $item->getSelectedItem() == 1;
                // default to selected of attribute doesn't exist
                if ($isSelected == 1|| $item->getSelectedItem() === null) {
                    $selectedSubtotal += $item->getRowTotal();
                    $selectedTax += $item->getTaxAmount();
                    $selectedItemsCount++;
                    $selectedItemsQty += $item->getQty();
                }
            }

            // Update totals data
            if (isset($totalsData['subtotal'])) {
                $totalsData['subtotal'] = $selectedSubtotal;
            }
            if (isset($totalsData['subtotal_incl_tax'])) {
                $totalsData['subtotal_incl_tax'] = $selectedTax + $selectedSubtotal;
            }

            if (isset($totalsData['grand_total'])) {
                // grand total is subtotal + tax + shipping - discount
                $grandTotal = $selectedSubtotal + $selectedTax;

                // add shipping if available
                if (isset($totalsData['shipping_amount'])) {
                    $grandTotal += $totalsData['shipping_amount'];
                }

                // subtract discount if available
                if (isset($totalsData['discount_amount'])) {
                    $grandTotal -= $totalsData['discount_amount'];
                }
                $totalsData['grand_total'] = $grandTotal;
            }

            // update items count and quantity
            if (isset($totalsData['items_count'])) {
                $totalsData['items_count'] = $selectedItemsCount;
            }
            // update items quantity
            if (isset($totalsData['items_qty'])) {
                $totalsData['items_qty'] = $selectedItemsQty;
            }

        } catch (\Exception $e) {
            // Handle exception if needed
        }

        return $totalsData;
    }
}
