define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/minicart',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
    'mage/storage',
], function ($, ko, Component, customerData, priceUtils, storage) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Xanka_SplitCart/minicart/item/default',
        },
        initialize: function () {
            this._super();
            let initialValue = typeof this.item !== 'undefined' && typeof this.item.selected_item !== 'undefined' ? this.item.selected_item === 1 : false;
            this.selected_item = ko.observable(initialValue);
            let subtotal = '0';
            this._updateTotals(subtotal);
            return this;
        },
        getProductNameUnsanitizedHtml: function (productName) {
            // product name has already escaped on backend
            return productName;
        },
        updateItemSelection: function (itemId, isSelected) {
            let self = this;
            this._recalculateSubtotal();
            this._updateItemSelection(itemId, isSelected);

        },
        _updateItemSelection: function (itemId, isSelected) {
            let url = window.checkout.baseUrl + 'splitcart/cart/updateItemSelection',
                self = this;

            let payload = {
                item_id: itemId,
                is_selected: isSelected ? 1 : 0,
            }
            storage.post(
                url,
                JSON.stringify(payload),
                false
            ).done(function (response) {
                if (response.success === true) {
                    customerData.invalidate(['cart']);
                } else {
                    console.error('Error updating item selection:', response.message);
                }
            }).fail(function (response) {
                console.error('Error updating item selection:', response);
                document.getElementById('split-cart-item-' + itemId).checked = !isSelected; // Revert checkbox state on error
                self._recalculateSubtotal(); // Recalculate subtotal on error
            });
        },
        _recalculateSubtotal: function () {
            let cart = customerData.get('cart'),
                items = cart().items || [],
                subtotal = 0;

            items.forEach((item, index) => {
                let itemCheckbox = document.getElementById('split-cart-item-' + item.item_id);
                if (itemCheckbox && itemCheckbox.checked) {
                    subtotal += parseFloat(item.product_price_value) * parseInt(item.qty);
                    item['selected_item'] = itemCheckbox.checked;

                    cart().items[index] = item; // Update the item in the observable array

                }
            });

            // Update the subtotal in the minicart
            this._updateTotals(subtotal);

        },
        _getPriceFormat: function () {
            return {
                "pattern": "$%s",
                "precision": 2,
                "requiredPrecision": 2,
                "decimalSymbol": ".",
                "groupSymbol": ",",
                "groupLength": 3,
                "integerRequired": false
            }
        },
        getFormattedPrice: function (price) {
            return priceUtils.formatPrice(price, this._getPriceFormat());
        },
        _updateTotals: function (subtotal) {
            let cart = customerData.get('cart');
            cart().subtotalAmount = this.getFormattedPrice(subtotal);
            cart().subtotal = '<span class="price">' + this.getFormattedPrice(subtotal) + '</span>';
            cart().subtotal_excl_tax = '<span class="price">' + this.getFormattedPrice(subtotal) + '</span>';
            customerData.set('cart', cart());
        }
    });
})
