# Xanka_SplitCart Module

**Xanka_SplitCart** is a custom Magento 2 module that enhances the shopping cart experience by allowing customers to split their cart items into separate groups or orders. This is useful for scenarios such as multi-vendor checkouts, partial checkouts, or organizing items by shipping method or supplier.

## Key Features
- Split cart items into multiple groups/orders
- Flexible integration with Magento 2 checkout
- Customizable logic for splitting items (e.g., by vendor, shipping method, or product type)
- Seamless user experience for managing split carts

## Installation
1. Copy the `Xanka/SplitCart` module to `app/code/Xanka/SplitCart`.
2. Run `php bin/magento setup:upgrade`.
3. Run `php bin/magento setup:di:compile` (if in production mode).
4. Run `php bin/magento cache:flush`.

## Usage
Once enabled, the module provides options to split cart items during the checkout process. Configuration and customization can be managed via the Magento admin panel or by extending the module's logic.

## Support
For issues or feature requests, please contact the Xanka development team or open an issue in your project repository.

