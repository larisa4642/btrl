<?
namespace btrl\ipay\Plugin;
 

class PluginBefore
{
    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) 
	{

        $this->_request = $context->getRequest();
		
		 $orderId = $this->_request->getParam('order_id');
		 if(trim($orderId))
		 {
		 $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		 
         $uri_obj = $objectManager->get('\Magento\Framework\UrlInterface'); 
         $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($orderId); 
		 $bt_status = $order->getIpayStatus(); 
		  $tot = $order->getGrandTotal(); 
		  
		 $bt_id = $order->getIpayId(); 
		  
		
	 if($this->_request->getFullActionName() == 'sales_order_view'){ 
	 
	 
	 
		$che_url = "/ipay/index/index/che/$orderId" ;
	 
		 $buttonList->add('chebutton',['label' => __('Verifica plata'), 'onclick' => 'fin_order(\''.$che_url.'\', \'che\' );  ', 'class' => 'reset'],-1);
	  
	 
	      
	 if($bt_status == 'Preautorizata')  //preautorizata
	 {
	 		
		$fin_url = "/ipay/index/index/fin/$orderId" ;
		$rev_url = "/ipay/index/index/rev/$orderId" ;
		
		 $buttonList->add('finbutton',['label' => __('Finalizare plata'), 'onclick' => 'if(confirm(\'Se va finaliza plata? Suma va fi depozitata in contul dvs.\')) fin_order(\''.$fin_url.'\', \'fin\' );  ', 'class' => 'reset'],-1);
	  
		 $buttonList->add('revbutton',['label' => __('Reversal'), 'onclick' => 'if(confirm(\'Se va debloca suma platita si returna clientului? Aceasta operatiune este ireversibila\')) fin_order(\''.$rev_url.'\', \'rev\' );  ', 'class' => 'reset'],-1);
	   
        }
		
		
			      
	 if($bt_status == 'Finalizata')  //Finalizata
	 {
	 		 
		$ref_url = "/ipay/index/index/ref/$orderId" ;
		
		 $buttonList->add('refbutton',['label' => __('Rambursare'), 'onclick' => 'ref_order(\''.$ref_url.'\',  \''.$tot.'\' );  ', 'class' => 'reset'],-1);
	  
	    
        }
				
			      
	 if($bt_status == 'Rambursata')  //Rambursata
	 {
	 		  
		
		 $buttonList->add('refbutton',['label' => __('Rambursata'), 'onclick' => 'alert(\'Plata este rambursata\')', 'class' => 'reset'],-1);
	  
	    
        }
		
		
		
			
	 }   
		 
		 
		 
		 
		 
		 }   //sales_order_view
		  
	 
		
		 
    }
}
?>