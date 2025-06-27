<?php

namespace Xanka\SplitCart\CustomerData;
use Magento\Checkout\CustomerData\DefaultItem as DefaultItemCustomerData;
class DefaultItem extends DefaultItemCustomerData
{
    protected function doGetItemData()
    {
        $itemData = parent::doGetItemData();
        // Add selected_item attribute to item data
        $quoteItem = $this->item->getQuote()->getItemById($this->item->getItemId());
        $itemData['selected_item'] = $quoteItem->getData('selected_item') ? 1 : 0;
        return $itemData;
    }
}
