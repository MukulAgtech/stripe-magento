/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, quote) {
        'use strict';

        return function (billingAddress) {
            var address = null;

            if (billingAddress.telephone && billingAddress.telephone == '0999999999') {
                address = billingAddress;
            } else {
                if (billingAddress.getCacheKey() && quote.shippingAddress() && billingAddress.getCacheKey() == quote.shippingAddress().getCacheKey()) {
                    address = $.extend({}, billingAddress);
                    address.saveInAddressBook = null;
                } else {
                    address = billingAddress;
                }
            }

            quote.billingAddress(address);
        };
    }
);