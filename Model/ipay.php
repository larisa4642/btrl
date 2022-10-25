<?php

namespace btrl\ipay\Model;

class ipay extends \Magento\Payment\Model\Method\AbstractMethod
{
 
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'btrl_ipay';
 
    /**
     * Availability option
     *
     * @var bool
     */ 

    protected $_canAuthorize = true;
    protected $_canCapture = true;
 
}



