define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'btrl_ipay',
                component: 'btrl_ipay/js/view/payment/method-renderer/ipay'
            }
        );
       
        return Component.extend({});
    });
