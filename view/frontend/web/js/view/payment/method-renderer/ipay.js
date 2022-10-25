define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'btrl_ipay/payment/ipay-form' 
            },

            context: function() {
                return this;
            },
 


            isActive: function() {
                return true;
            },
      
            getCode: function() {
                return 'btrl_ipay';
            },  
            
               afterPlaceOrder: function () { 
               window.location.replace("/ipay/index/index");
		       return false;
            } 
 
        });
    }
);



 



 