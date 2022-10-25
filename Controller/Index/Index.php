<?php
namespace btrl\ipay\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
  public function __construct(\Magento\Framework\App\Action\Context $context)
  {
    return parent::__construct($context);
  }

  public function execute()
  {
    
		 
$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
$scopeConfig = $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface'); 
$orderConfig = $objectManager->get('\Magento\Checkout\Model\Session');
$cart = $objectManager->get('\Magento\Checkout\Model\Cart');  
$orderObject = $objectManager->get('\Magento\Sales\Model\Order'); 
$uri_obj = $objectManager->get('\Magento\Framework\UrlInterface'); 
 
	$repo = $objectManager->get('\Magento\Sales\Api\OrderRepositoryInterface');
	$orderFactory = $objectManager->get('\Magento\Sales\Api\Data\OrderInterfaceFactory');
	
	
	$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
	$moneda = $storeManager->getStore()->getCurrentCurrency()->getCode();
	
	
	$user = $scopeConfig->getValue('payment/btrl_ipay/merchant_username', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
	$pass = $scopeConfig->getValue('payment/btrl_ipay/merchant_pass', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
	$phase = $scopeConfig->getValue('payment/btrl_ipay/phase', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if(!$phase) $phase = 1;
		  
	$modultest = $scopeConfig->getValue('payment/btrl_ipay/modultest', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
	$status_comanda = $scopeConfig->getValue('payment/btrl_ipay/status_comanda', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
	$status_comanda_noua = $scopeConfig->getValue('payment/btrl_ipay/status_comanda_noua', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	
	
	$bt_port = "";
	 if($modultest) 
	$bt_port = ":5443";		
	
$request = $objectManager->get('Magento\Framework\App\Request\Http');   
  
//verificare plata			
if($request->getParam('che'))
{ 
	$che = $request->getParam('che');
	
	
		 $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
         $uri_obj = $objectManager->get('\Magento\Framework\UrlInterface'); 
         $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($che);  
		 $bt_id = $order->getIpayId(); 
	  
	
		 $order_data_a = array(
	"userName=".$user,
	"password=".$pass,
	"orderId=".$bt_id);
	
    $order_data = implode("&", $order_data_a); 
	 
	$ch = curl_init(); 
curl_setopt($ch,CURLOPT_URL,'https://ecclients.btrl.ro'.$bt_port.'/payment/rest/getOrderStatusExtended.do'); 
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $order_data); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
$order_result = curl_exec($ch);  
curl_close($ch); 

$result_ibtpay = json_decode($order_result);

  $order_status = $result_ibtpay -> orderStatus;
  $error_code = $result_ibtpay -> errorCode;
  $mesaj = $result_ibtpay -> actionCodeDescription;


 if($error_code == '0')
 {
	 if($order_status == '0')
	{ 
	   die("Neplatita\n".$mesaj); 
		
	}
	
	elseif($order_status == '1')
	{
		
		
	$order->setIpayStatus('Preautorizata');	 
	$order->addStatusToHistory($order->getStatus(), 'Plata este preautorizata'); 
	 $order->setState($status_comanda, true);
    $order->setStatus($status_comanda);
    $order->save(); 
	 die("Plata este preautorizata\n\n".$mesaj); 
		
	}
	
	elseif($order_status == '2')
	{
	 	$order->setIpayStatus('Finalizata');	 
	$order->addStatusToHistory($order->getStatus(), 'Plata este depozitata'); 
	 $order->setState($status_comanda, true);
    $order->setStatus($status_comanda);
    $order->save(); 
	 die("Plata este depozitata\n\n".$mesaj); 
	}
	
	elseif($order_status == '3' || $order_status == '4')
	{
     $order->setIpayStatus('Rambursata');	 
    $order->addStatusToHistory($order->getStatus(), 'Plata este rambursata'); 
    $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
    $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED); 
    $order->save();
	   die("Plata este rambursata\n\n".$mesaj); 
		
	}  
	else 
	   die($mesaj);  
	 
}
else {   
 	
	die("Eroare de sistem\n".$mesaj); 
}
  
}
 
 
//finalizare plata			
if($request->getParam('fin'))
{ 
	$fin = $request->getParam('fin');
	
	
		 $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
         $uri_obj = $objectManager->get('\Magento\Framework\UrlInterface'); 
         $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($fin);  
		 $bt_id = $order->getIpayId(); 
	 
	$suma = $order->getGrandTotal() * 100; 
	
		 $order_data_a = array(
	"userName=".$user,
	"password=".$pass,
	"orderId=".$bt_id , 
	"amount=$suma"  	);
	
    $order_data = implode("&", $order_data_a);  
	 

	$ch = curl_init(); 
curl_setopt($ch,CURLOPT_URL,'https://ecclients.btrl.ro'.$bt_port.'/payment/rest/deposit.do'); 
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $order_data); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
$order_result = curl_exec($ch); 
curl_close($ch);

$result_ibtpay = json_decode($order_result);

if($result_ibtpay -> errorCode == 0)
{ 

	$order->setIpayStatus('Finalizata');	 
	$order->addStatusToHistory($order->getStatus(), 'Plata a fost finalizata');
	
	
    $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE, true);
    $order->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
	
    $order->save();
	die("Finalizata"); 
	 
}
else {
 $order->addStatusToHistory($order->getStatus(), 'Eroare Finalizata '.print_r($result_ibtpay, true));
    $order->save();
 	
	die("Eroare finalizare"); 
}
 
	 
}

//finalizare plata 
			
if($request->getParam('rev'))
{ 
	$rev = $request->getParam('rev');
			 $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
         $uri_obj = $objectManager->get('\Magento\Framework\UrlInterface'); 
         $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($rev);  
		 $bt_id = $order->getIpayId(); 
	
		 $order_data_a = array(
	"userName=".$user,
	"password=".$pass,
	"orderId=".$bt_id 	);
	
    $order_data = implode("&", $order_data_a);  
	  
	$ch = curl_init();//open connection 
curl_setopt($ch,CURLOPT_URL,'https://ecclients.btrl.ro'.$bt_port.'/payment/rest/reverse.do'); 
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $order_data); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
$order_result = curl_exec($ch); 
curl_close($ch);

$result_ibtpay = json_decode($order_result);

if($result_ibtpay -> errorCode == 0)
{ 

	$order->setIpayStatus('Rambursata');	 
	$order->addStatusToHistory($order->getStatus(), 'Plata a fost rambursata');
	 
	
    $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
    $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
	
    $order->save();
	die("Rambursata"); 
	 
}
else {
 $order->addStatusToHistory($order->getStatus(), 'Eroare rambursare '.print_r($result_ibtpay, true));
    $order->save();
 	
	die("Eroare rambursare"); 
}
	 
	
}   //rev


			
if($request->getParam('ref'))
{ 
	$ref = $request->getParam('ref');
	$ref = explode('--', $ref);
	$orderid = $ref[0];
	$suma = $ref[1] * 100;
	     
		 $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
         $uri_obj = $objectManager->get('\Magento\Framework\UrlInterface'); 
         $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($orderid);  
		 $bt_id = $order->getIpayId(); 
	
	$rev = $request->getParam('rev');
	
	
		 $order_data_a = array(
	"userName=".$user,
	"password=".$pass,
	"orderId=".$bt_id, 
	"amount=$suma" 	);
	
    $order_data = implode("&", $order_data_a);  
	 

	$ch = curl_init(); 
curl_setopt($ch,CURLOPT_URL,'https://ecclients.btrl.ro'.$bt_port.'/payment/rest/refund.do'); 
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $order_data); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
$order_result = curl_exec($ch); 
curl_close($ch);

$result_ibtpay = json_decode($order_result);

if($result_ibtpay -> errorCode == 0)
{ 

	$order->setIpayStatus('Rambursata');	 
	$order->addStatusToHistory($order->getStatus(), "Rambursat ".$ref[1]." RON");
	
	
    $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
    $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
	
    $order->save();
	die("Rambursata");  
	 
}
else {
 $order->addStatusToHistory($order->getStatus(), 'Eroare rambursare '.print_r($result_ibtpay, true));
    $order->save();
 	
	die("Eroare rambursare");
}
	 
} 
	
	 
if($request->getParam('orderId'))
{
	$orderId = $request->getParam('orderId');
	
	 $order_data_a = array(
	"userName=".$user,
	"password=".$pass,
	"orderId=$orderId" 	);
	 
	
	$order_data = implode("&", $order_data_a);
	
	
 
	$ch = curl_init(); 
curl_setopt($ch,CURLOPT_URL,"https://ecclients.btrl.ro$bt_port/payment/rest/getOrderStatusExtended.do"); 
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $order_data); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
$order_result = curl_exec($ch);  
curl_close($ch); 
 
 $json_data = json_decode($order_result, true); 
   
 $error_code =  $json_data['errorCode'];
 $mesaj = $json_data['actionCodeDescription'] ;
 $OrderNumber = $json_data['orderNumber'] ;
 $order_status = $json_data['orderStatus'] ; 
   
 $order_id = explode('-', $OrderNumber);
 $order_id = $order_id[0];
	
	
	
	$order = $orderFactory->create()->loadByIncrementId($order_id);
	 
 if($error_code == 0 && ( $order_status == '1' || $order_status == '2')) { 
	 
						 
		 if($order_status)
			 {   
	 
    $order->addStatusToHistory($order->getStatus(), 'Plata efectuata prin iPay');
	
	$order->setIpayStatus('Creata');	
						
	if($order_status == 1) { 	
	$order->setIpayStatus('Preautorizata');	 
	$order->addStatusToHistory($order->getStatus(), 'Plata a fost preautorizata'); 
	 $order->setState($status_comanda, true);
    $order->setStatus($status_comanda);
 
	
	
	}
	if($order_status == 2)  { 	
	$order->setIpayStatus('Finalizata');	
	$order->addStatusToHistory($order->getStatus(), 'Plata a fost depozitata');	 
	$order->setState($status_comanda, true);
    $order->setStatus($status_comanda);
	}
	
	$order->setIpayId($orderId);
    $order->save();
	 	 
			 }
					
          $finish_url = $uri_obj->getBaseUrl()."checkout/onepage/success";
		   $resultRedirect = $this->resultRedirectFactory->create();
           return $resultRedirect->setPath('checkout/onepage/success');
		    
    echo "<br><br><div align='center' style='font-size: 16px;'>Plata a fost efectuata cu succes!<br><br>
	<a href='$finish_url'><strong><- Inapoi la magazin</strong></a></div>
	";
		   
		   echo"<script>window.location.replace('$finish_url')</script>";	 
					 exit;
					} else {
						
						
		  
						echo"<div align='center'><br><br>Plata a esuat!<br><br>" . $mesaj; 
						
						echo"<br><br><a href='".$uri_obj->getUrl('ipay/index/index', array('payOrder'=>$OrderNumber))."'>Reincercati plata</a></div>";
		 
						exit; 
					}
 
  
 
 exit; 
	}
		 
		
 if($request->getParam('payOrder'))
 {  $order_id = $request->getParam('payOrder');
    $order_id = explode('-', $order_id);
	$order_id = $order_id[0];
	$order = $orderFactory->create()->loadByIncrementId($order_id); 
	 
 }
 else		
 {
 $order = $orderConfig->getLastRealOrder();
  $order_id=$order->getIncrementId();
 } 

 $amount = $order->getGrandTotal() * 100;
	
 
 
	  $order_number = "$order_id-".time();  
	  $return_url = $uri_obj->getUrl('ipay/index/index', array('orderNumber'=>$order_number));
 
	
$monede = array( 'AFN'=>971, 'ALL'=>8, 'DZD'=>12, 'USD'=>840, 'EUR'=>978, 'AOA'=>973, 'XCD'=>951, 'XCD'=>951, 'ARS'=>32, 'AMD'=>51, 'AWG'=>533, 'AUD'=>36, 'EUR'=>978, 'AZN'=>944, 'BSD'=>44, 'BHD'=>48, 'BDT'=>50, 'BBD'=>52, 'BYN'=>933, 'EUR'=>978, 'BZD'=>84, 'XOF'=>952, 'BMD'=>60, 'BTN'=>64, 'INR'=>356, 'BOB'=>68, 'BOV'=>984, 'USD'=>840, 'BAM'=>977, 'BWP'=>72, 'NOK'=>578, 'BRL'=>986, 'USD'=>840, 'BND'=>96, 'BGN'=>975, 'XOF'=>952, 'BIF'=>108, 'CVE'=>132, 'KHR'=>116, 'XAF'=>950, 'CAD'=>124, 'KYD'=>136, 'XAF'=>950, 'XAF'=>950, 'CLF'=>990, 'CLP'=>152, 'CNY'=>156, 'AUD'=>36, 'AUD'=>36, 'COP'=>170, 'COU'=>970, 'KMF'=>174, 'CDF'=>976, 'XAF'=>950, 'NZD'=>554, 'CRC'=>188, 'HRK'=>191, 'CUC'=>931, 'CUP'=>192, 'ANG'=>532, 'EUR'=>978, 'CZK'=>203, 'XOF'=>952, 'DKK'=>208, 'DJF'=>262, 'XCD'=>951, 'DOP'=>214, 'USD'=>840, 'EGP'=>818, 'SVC'=>222, 'USD'=>840, 'XAF'=>950, 'ERN'=>232, 'EUR'=>978, 'ETB'=>230, 'EUR'=>978, 'FKP'=>238, 'DKK'=>208, 'FJD'=>242, 'EUR'=>978, 'EUR'=>978, 'EUR'=>978, 'XPF'=>953, 'EUR'=>978, 'XAF'=>950, 'GMD'=>270, 'GEL'=>981, 'EUR'=>978, 'GHS'=>936, 'GIP'=>292, 'EUR'=>978, 'DKK'=>208, 'XCD'=>951, 'EUR'=>978, 'USD'=>840, 'GTQ'=>320, 'GBP'=>826, 'GNF'=>324, 'XOF'=>952, 'GYD'=>328, 'HTG'=>332, 'USD'=>840, 'AUD'=>36, 'EUR'=>978, 'HNL'=>340, 'HKD'=>344, 'HUF'=>348, 'ISK'=>352, 'INR'=>356, 'IDR'=>360, 'XDR'=>960, 'IRR'=>364, 'IQD'=>368, 'EUR'=>978, 'GBP'=>826, 'ILS'=>376, 'EUR'=>978, 'JMD'=>388, 'JPY'=>392, 'GBP'=>826, 'JOD'=>400, 'KZT'=>398, 'KES'=>404, 'AUD'=>36, 'KPW'=>408, 'KRW'=>410, 'KWD'=>414, 'KGS'=>417, 'LAK'=>418, 'EUR'=>978, 'LBP'=>422, 'LSL'=>426, 'ZAR'=>710, 'LRD'=>430, 'LYD'=>434, 'CHF'=>756, 'EUR'=>978, 'EUR'=>978, 'MOP'=>446, 'MGA'=>969, 'MWK'=>454, 'MYR'=>458, 'MVR'=>462, 'XOF'=>952, 'EUR'=>978, 'USD'=>840, 'EUR'=>978, 'MRU'=>929, 'MUR'=>480, 'EUR'=>978, 'XUA'=>965, 'MXN'=>484, 'MXV'=>979, 'USD'=>840, 'MDL'=>498, 'EUR'=>978, 'MNT'=>496, 'EUR'=>978, 'XCD'=>951, 'MAD'=>504, 'MZN'=>943, 'MMK'=>104, 'NAD'=>516, 'ZAR'=>710, 'AUD'=>36, 'NPR'=>524, 'EUR'=>978, 'XPF'=>953, 'NZD'=>554, 'NIO'=>558, 'XOF'=>952, 'NGN'=>566, 'NZD'=>554, 'AUD'=>36, 'USD'=>840, 'NOK'=>578, 'OMR'=>512, 'PKR'=>586, 'USD'=>840, 'PAB'=>590, 'USD'=>840, 'PGK'=>598, 'PYG'=>600, 'PEN'=>604, 'PHP'=>608, 'NZD'=>554, 'PLN'=>985, 'EUR'=>978, 'USD'=>840, 'QAR'=>634, 'MKD'=>807, 'RON'=>946, 'RUB'=>643, 'RWF'=>646, 'EUR'=>978, 'EUR'=>978, 'SHP'=>654, 'XCD'=>951, 'XCD'=>951, 'EUR'=>978, 'EUR'=>978, 'XCD'=>951, 'WST'=>882, 'EUR'=>978, 'STN'=>930, 'SAR'=>682, 'XOF'=>952, 'RSD'=>941, 'SCR'=>690, 'SLL'=>694, 'SGD'=>702, 'ANG'=>532, 'XSU'=>994, 'EUR'=>978, 'EUR'=>978, 'SBD'=>90, 'SOS'=>706, 'ZAR'=>710, 'SSP'=>728, 'EUR'=>978, 'LKR'=>144, 'SDG'=>938, 'SRD'=>968, 'NOK'=>578, 'SZL'=>748, 'SEK'=>752, 'CHE'=>947, 'CHF'=>756, 'CHW'=>948, 'SYP'=>760, 'TWD'=>901, 'TJS'=>972, 'TZS'=>834, 'THB'=>764, 'USD'=>840, 'XOF'=>952, 'NZD'=>554, 'TOP'=>776, 'TTD'=>780, 'TND'=>788, 'TRY'=>949, 'TMT'=>934, 'USD'=>840, 'AUD'=>36, 'UGX'=>800, 'UAH'=>980, 'AED'=>784, 'GBP'=>826, 'USD'=>840, 'USD'=>840, 'USN'=>997, 'UYI'=>940, 'UYU'=>858, 'UZS'=>860, 'VUV'=>548, 'VEF'=>937, 'VND'=>704, 'USD'=>840, 'USD'=>840, 'XPF'=>953, 'MAD'=>504, 'YER'=>886, 'ZMW'=>967, 'ZWL'=>932, 'EUR'=>978); 
	


  $country_numbers = array(  'AF'=>'4','AX'=>'248','AL'=>'8','DZ'=>'12','AS'=>'16','AD'=>'20','AO'=>'24','AI'=>'660','AQ'=>'10','AG'=>'28','AR'=>'32','AM'=>'51','AW'=>'533','AU'=>'36','AT'=>'40','AZ'=>'31','BS'=>'44','BH'=>'48','BD'=>'50','BB'=>'52','BY'=>'112','BE'=>'56','BZ'=>'84','BJ'=>'204','BM'=>'60','BT'=>'64','BO'=>'68','BQ'=>'535','BA'=>'70','BW'=>'72','BV'=>'74','BR'=>'76','IO'=>'86','BN'=>'96','BG'=>'100','BF'=>'854','BI'=>'108','CV'=>'132','KH'=>'116','CM'=>'120','CA'=>'124','KY'=>'136','CF'=>'140','TD'=>'148','CL'=>'152','CN'=>'156','CX'=>'162','CC'=>'166','CO'=>'170','KM'=>'174','CD'=>'180','CG'=>'178','CK'=>'184','CR'=>'188','CI'=>'384','HR'=>'191','CU'=>'192','CW'=>'531','CY'=>'196','CZ'=>'203','DK'=>'208','DJ'=>'262','DM'=>'212','DO'=>'214','EC'=>'218','EG'=>'818','SV'=>'222','GQ'=>'226','ER'=>'232','EE'=>'233','SZ'=>'748','ET'=>'231','FK'=>'238','FO'=>'234','FJ'=>'242','FI'=>'246','FR'=>'250','GF'=>'254','PF'=>'258','TF'=>'260','GA'=>'266','GM'=>'270','GE'=>'268','DE'=>'276','GH'=>'288','GI'=>'292','GR'=>'300','GL'=>'304','GD'=>'308','GP'=>'312','GU'=>'316','GT'=>'320','GG'=>'831','GN'=>'324','GW'=>'624','GY'=>'328','HT'=>'332','HM'=>'334','VA'=>'336','HN'=>'340','HK'=>'344','HU'=>'348','IS'=>'352','IN'=>'356','ID'=>'360','IR'=>'364','IQ'=>'368','IE'=>'372','IM'=>'833','IL'=>'376','IT'=>'380','JM'=>'388','JP'=>'392','JE'=>'832','JO'=>'400','KZ'=>'398','KE'=>'404','KI'=>'296','KP'=>'408','KR'=>'410','KW'=>'414','KG'=>'417','LA'=>'418','LV'=>'428','LB'=>'422','LS'=>'426','LR'=>'430','LY'=>'434','LI'=>'438','LT'=>'440','LU'=>'442','MO'=>'446','MK'=>'807','MG'=>'450','MW'=>'454','MY'=>'458','MV'=>'462','ML'=>'466','MT'=>'470','MH'=>'584','MQ'=>'474','MR'=>'478','MU'=>'480','YT'=>'175','MX'=>'484','FM'=>'583','MD'=>'498','MC'=>'492','MN'=>'496','ME'=>'499','MS'=>'500','MA'=>'504','MZ'=>'508','MM'=>'104','NA'=>'516','NR'=>'520','NP'=>'524','NL'=>'528','NC'=>'540','NZ'=>'554','NI'=>'558','NE'=>'562','NG'=>'566','NU'=>'570','NF'=>'574','MP'=>'580','NO'=>'578','OM'=>'512','PK'=>'586','PW'=>'585','PS'=>'275','PA'=>'591','PG'=>'598','PY'=>'600','PE'=>'604','PH'=>'608','PN'=>'612','PL'=>'616','PT'=>'620','PR'=>'630','QA'=>'634','RE'=>'638','RO'=>'642','RU'=>'643','RW'=>'646','BL'=>'652','SH'=>'654','KN'=>'659','LC'=>'662','MF'=>'663','PM'=>'666','VC'=>'670','WS'=>'882','SM'=>'674','ST'=>'678','SA'=>'682','SN'=>'686','RS'=>'688','SC'=>'690','SL'=>'694','SG'=>'702','SX'=>'534','SK'=>'703','SI'=>'705','SB'=>'90','SO'=>'706','ZA'=>'710','GS'=>'239','SS'=>'728','ES'=>'724','LK'=>'144','SD'=>'729','SR'=>'740','SJ'=>'744','SE'=>'752','CH'=>'756','SY'=>'760','TW'=>'158','TJ'=>'762','TZ'=>'834','TH'=>'764','TL'=>'626','TG'=>'768','TK'=>'772','TO'=>'776','TT'=>'780','TN'=>'788','TR'=>'792','TM'=>'795','TC'=>'796','TV'=>'798','UG'=>'800','UA'=>'804','AE'=>'784','GB'=>'826','UM'=>'581','US'=>'840','UY'=>'858','UZ'=>'860','VU'=>'548','VE'=>'862','VN'=>'704','VG'=>'92','VI'=>'850','WF'=>'876','EH'=>'732','YE'=>'887','ZM'=>'894','ZW'=>'716');

function validphone($s)
{
$phonec = array('93','355','213','1-684','376','244','1-264','672','1-268','54','374','297','61','43','994','1-242','973','880','1-246','375','32','501','229','1-441','975','591','387','267','55','673','359','226','257','855','237','1','238','1-345','236','235','56','86','53','61','57','269','243','242','682','506','225','385','53','357','420','45','253','1-767','1-809','1-829  ','670','593 ','20','503','240','291','372','251','500','298','679','358','33','594','689','241','220','995','49','233','350','30','299','1-473','590','1-671','502','224','245','592','509','504','852','36','354','91','62','98','964','353','972','39','1-876','81','962','7','254','686','850','82','965','996','856','371','961','266','231','218','423','370','352','853','389','261','265','60','960','223','356','692','596','222','230','269','52','691','373','377','976','1-664','212','258','95','264','674','977','31','599','687','64','505','227','234','683','672','1-670','47','968','92','680','970','507','675','595','51','63','48','351','1-787','1-939','974 ','262','40','7','250','290','1-869','1-758','508','1-784','685','378','239','966','221','248','232','65','421','386','677','252','27','34','94','249','597','268','46','41','963','886','992','255','66','690','676','1-868','216','90','993','1-649','688','256','380','971','44','1','598','998','678','418','58','84','1-284','1-340','681','967','260','263');	

$valid = false;

$s = trim($s);

if(!trim($s)) return false;

foreach($phonec as $item) if(substr($s, 0, strlen($item)) === $item) $valid = true;
if(strlen($s) > 12) $valid =  false;
return $valid ;
	
}

function utf3a($toClean) {
  
    $normalizeChars = array(
    '&icirc;'=>'i','&Icirc;'=>'I','&acirc;'=>'a','&Acirc;'=>'A', 
	'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 
    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 
    'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 
    'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 
    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 
    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 
    'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f', 'ü'=>'u', 
	'ţ'=>'t', 'Ţ'=>'T', 'ă'=>'a', 'Ă'=>'A', 'ş'=>'s', 'Ş'=>'S', 'î'=>'i', 'Î'=>'I', 'Â'=>'A', 'â'=>'a',
	'ț'=>'t', 'ș'=>'s'
);
  foreach($normalizeChars as $ch1 => $ch2)
  $toClean = str_replace($ch1, $ch2, $toClean);
  
    foreach($normalizeChars as $ch1 => $ch2) 
  $toClean = preg_replace('/'.$ch1.'/i', $ch2, $toClean);
  
  return $toClean; 
}

function form_text($s)
{
$s = utf3a($s);
$vc = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9","-", " ", ",", ".", ":", ";", "(", ")"); 
$s2 = '';
for($i = 0; $i < strlen($s); $i++)
if(in_array(strtolower($s[$i]), $vc)) 
$s2.=$s[$i];
return $s2;	
}	    
 
	$ds2_email = $scopeConfig->getValue('payment/btrl_ipay/ds2_email', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	$ds2_phone = $scopeConfig->getValue('payment/btrl_ipay/ds2_phone', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	$ds2_contact = $scopeConfig->getValue('payment/btrl_ipay/ds2_contact', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	$ds2_deliveryType = $scopeConfig->getValue('payment/btrl_ipay/ds2_deliveryType', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	$ds2_country = $scopeConfig->getValue('payment/btrl_ipay/ds2_country', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	$ds2_city = $scopeConfig->getValue('payment/btrl_ipay/ds2_city', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	$ds2_postAddress = $scopeConfig->getValue('payment/btrl_ipay/ds2_postAddress', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
   
   
   
 
        $shippingAddress = $order->getBillingAddress();
		   
        $street = $shippingAddress->getData('street');
        $city = $shippingAddress->getData('city');
        $countryCode = $shippingAddress->getData('country_id'); 
        $telephone = $shippingAddress->getData('telephone');  

   
	 $s2['ds2_email'] = $order->getCustomerEmail();
	 $s2['ds2_phone'] = $telephone; 
	 $s2['ds2_contact'] = $order->getCustomerName();
	 $s2['ds2_deliveryType'] = "courier";
	 $s2['ds2_country'] = $country_numbers[$countryCode];
	 $s2['ds2_city'] = $city;
	 $s2['ds2_postAddress'] = $street; 
	  
	 if(strlen($s2['ds2_country']) != 3 && intval($s2['ds2_country']) != $s2['ds2_country'])  $s2['ds2_country'] = $ds2_country;
	 
	 if(!filter_var($s2['ds2_email'], FILTER_VALIDATE_EMAIL)) $s2['ds2_email'] = $ds2_email;
     $s2['ds2_phone'] = preg_replace("/[^0-9]/", '', $s2['ds2_phone']); 
	 if(!validphone($s2['ds2_phone'])) $s2['ds2_phone'] = $ds2_phone;
	   
	 $s2['ds2_contact'] = form_text($s2['ds2_contact']); 
	 $s2['ds2_contact'] = substr($s2['ds2_contact'], 0, 39);
	 if(!trim($s2['ds2_contact'])) $s2['ds2_contact'] =  $ds2_contact;
	 
	 $s2['ds2_city'] = form_text($s2['ds2_city']); 
	 $s2['ds2_city'] = substr($s2['ds2_city'], 0, 39);
	 if(!trim($s2['ds2_city'])) $s2['ds2_city'] = $ds2_city;
	   
	 $s2['ds2_postAddress'] = form_text($s2['ds2_postAddress']); 
	 $s2['ds2_postAddress'] = substr($s2['ds2_postAddress'], 0, 49);
	 if(!trim($s2['ds2_postAddress'])) $s2['ds2_postAddress'] = $ds2_postAddress; 
	 
 	$orderBundle='{"orderCreationDate":"'.date("Y-m-d").'","customerDetails":{"email":"'.$s2['ds2_email'].'","phone":"'.$s2['ds2_phone'].'","contact":"'.$s2['ds2_contact'].'","deliveryInfo":{"deliveryType":"'.$s2['ds2_deliveryType'].'","country":"'.$s2['ds2_country'].'","city":"'.$s2['ds2_city'].'","postAddress":"'.$s2['ds2_postAddress'].'"},"billingInfo":{"country":"'.$s2['ds2_country'].'","city":"'.$s2['ds2_city'].'","postAddress":"'.$s2['ds2_postAddress'].'"}}}';
 
    $jsp1 = '{"FORCE_3DS2":"true"}';	 
 
 
	 $order_data_a = array(
	"userName=".$user,
	"password=".$pass,
	"orderNumber=".$order_number,
	"amount=$amount",
	"currency=".$monede[$moneda],
	"returnUrl=".$return_url, 
	"failUrl=".$return_url, 
	"description=Pay+online+by+credit+card", 
	"email=".$s2['ds2_email'], 
	'jsonParams='.$jsp1,
	"orderBundle=".$orderBundle); 
	
	$userAgent = $request->getHeader('useragent');
    $server = $request->getServer();
	$isMobileDevice = \Zend_Http_UserAgent_Mobile::match($userAgent, $server);
    if($isMobileDevice) 
	$order_data_a['pageView'] = 'MOBILE';
      
    $order_data = implode("&", $order_data_a); 
	 
	
 if($phase == 1)
 { 
 $ch = curl_init(); 
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_URL,"https://ecclients.btrl.ro$bt_port/payment/rest/register.do"); 
curl_setopt($ch,CURLOPT_POSTFIELDS, $order_data); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
$order_result = curl_exec($ch);   
curl_close($ch);			
					
 }
 else
 {
	$ch = curl_init(); 
curl_setopt($ch,CURLOPT_URL,"https://ecclients.btrl.ro$bt_port/payment/rest/registerPreAuth.do"); 
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $order_data); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
$order_result = curl_exec($ch);   
curl_close($ch); 	
 }  		 
	  
    if($status_comanda_noua)
	{
	 $order->setState($status_comanda_noua, true);
     $order->setStatus($status_comanda_noua);
     $order->save(); 
	}	  
 	
$result_ibtpay = json_decode($order_result, true);   
if(isset($result_ibtpay['formUrl'])) 
{     
 $ibtpay_url = $result_ibtpay['formUrl']; 
echo"<script>window.location.replace('$ibtpay_url');</script>";

    exit;
} 
else echo"A intervenit o eroare!";
	  
    exit;
  }
} 