<?php

use Tygh\Registry;
require_once dirname(__FILE__) . '/platron/PG_Signature.php';

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {
	$arrStatuses = array(
		'N' => 'Created',
		'P'	=> 'Pending',
		'C'	=> 'Completed',
		'O'	=> 'Opened',
		'F'	=> 'Failed',
		'D'	=> 'Declined',
		'B'	=> 'Postponed',
		'I' => 'Cancel',
	);
	
	$arrPendingStatuses = array('N','P','O');
	$arrOkStatuses = array('C');
	$arrFailedStatuses = array('F');
	
	$order_id = (int) $_REQUEST['pg_order_id'];
	$order_info = fn_get_order_info($order_id);
    $processor_data = $order_info['payment_method'];
	$arrRequest = array();
	if(!empty($_POST)) 
		$arrRequest = $_POST;
	else
		$arrRequest = $_GET;

	$thisScriptName = PG_Signature::getOurScriptName();
	if (empty($arrRequest['pg_sig']) || !PG_Signature::check($arrRequest['pg_sig'], $thisScriptName, $arrRequest, $processor_data['processor_params']['secret_key']))
		die("Wrong signature");
	
    if ($mode == 'check') {
		$bCheckResult = 0;

		if(empty($order_info) || !in_array($order_info['status'], $arrPendingStatuses))
			$error_desc = "Товар не доступен. Либо заказа нет, либо его статус " . !empty($arrStatuses[$order_info['status']]) ? $arrStatuses[$order_info['status']] : $order_info['status'];	
		elseif($arrRequest['pg_amount'] != $order_info['total'])
			$error_desc = "Неверная сумма";
		else
			$bCheckResult = 1;
		
		$arrResponse['pg_salt']              = $arrRequest['pg_salt']; // в ответе необходимо указывать тот же pg_salt, что и в запросе
		$arrResponse['pg_status']            = $bCheckResult ? 'ok' : 'error';
		$arrResponse['pg_error_description'] = $bCheckResult ?  ""  : $error_desc;
		$arrResponse['pg_sig']				 = PG_Signature::make($thisScriptName, $arrResponse, $processor_data['processor_params']['secret_key']);

		$objResponse = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response/>');
		$objResponse->addChild('pg_salt', $arrResponse['pg_salt']);
		$objResponse->addChild('pg_status', $arrResponse['pg_status']);
		$objResponse->addChild('pg_error_description', $arrResponse['pg_error_description']);
		$objResponse->addChild('pg_sig', $arrResponse['pg_sig']);
		
		header("Content-type: text/xml");
		echo $objResponse->asXML();
		die();
		
    } 
	elseif ($mode == 'result') {
        $bResult = 0;
		if(empty($order_info) || !in_array($order_info['status'], array_merge($arrPendingStatuses, $arrOkStatuses, $arrFailedStatuses)))
			$strResponseDescription = "Товар не доступен. Либо заказа нет, либо его статус " . !empty($arrStatuses[$order_info['status']]) ? $arrStatuses[$order_info['status']] : $order_info['status'];	
		elseif($arrRequest['pg_amount'] != $order_info['total'])
			$strResponseDescription = "Неверная сумма";
		else {
			$bResult = 1;
			$strResponseStatus = 'ok';
			$strResponseDescription = "Оплата принята";
			if ($arrRequest['pg_result'] == 1){
				// Установим статус оплачен
				$pp_response = array();
				$pp_response['order_status'] = 'C';
				$pp_response['reason_text'] = __('Success payment');
				$pp_response['transaction_id'] = $_REQUEST['pg_payment_id'];
				fn_finish_payment($order_id, $pp_response);
			}
			else{
				// Установим отказ
				$pp_response = array();
				$pp_response['order_status'] = 'F';
				$pp_response['reason_text'] = __('Failed status. See in platron admin');
				$pp_response['transaction_id'] = $_REQUEST['pg_payment_id'];
				fn_finish_payment($order_id, $pp_response);
			}
		}
		if(!$bResult)
			$strResponseStatus = 'error';

		$objResponse = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response/>');
		$objResponse->addChild('pg_salt', $arrRequest['pg_salt']); // в ответе необходимо указывать тот же pg_salt, что и в запросе
		$objResponse->addChild('pg_status', $strResponseStatus);
		$objResponse->addChild('pg_description', $strResponseDescription);
		$objResponse->addChild('pg_sig', PG_Signature::makeXML($thisScriptName, $objResponse, $processor_data['processor_params']['secret_key']));
		
		header("Content-type: text/xml");
		echo $objResponse->asXML();
		die();
		
    } 
	elseif ($mode == 'success' || $mode == 'fail') {
		fn_order_placement_routines('route', $order_id, false);
    }

} else {
	// Валюта по старому ISO
	$order_info = fn_get_order_info($order_id);

	$arrOrderItems = $order_info['products'];
	foreach($arrOrderItems as $arrItem){
		$strDescription .= $arrItem['product'];
		if($arrItem['amount'] > 1)
			$strDescription .= "*".$arrItem['amount'];
		$strDescription .= "; ";
	}

	$form_fields = array(
		'pg_merchant_id'	=> $processor_data['processor_params']['merchant_id'],
		'pg_order_id'		=> $order_info['order_id'],
		'pg_currency'		=> $order_info['secondary_currency'],
		'pg_language'		=> CART_LANGUAGE,
		'pg_amount'			=> number_format($order_info['total'], 2, '.', ''),
		'pg_lifetime'		=> $processor_data['processor_params']['lifetime'] * 60, // в секундах
		'pg_testing_mode'	=> ($processor_data['processor_params']['test_mode'] == 'test') ? 1 : 0,
		'pg_description'	=> mb_substr($strDescription, 0, 255, "UTF-8"),
		'pg_check_url'		=> fn_url('payment_notification.check&payment=platron'),
		'pg_result_url'		=> fn_url('payment_notification.result&payment=platron'),
		'pg_request_method'	=> 'GET',
		'pg_success_url'	=> fn_url('payment_notification.success&payment=platron'),
		'pg_failure_url'	=> fn_url('payment_notification.fail&payment=platron'),
		'pg_salt'			=> rand(21,43433),
		'cms_payment_module'=> 'CS_CART',		// Параметры безопасности сообщения. Необходима генерация pg_salt и подписи сообщения.
	);

	$strMaybePhone = (!empty($order_info['b_phone'])) ? $order_info['b_phone'] : @$order_info['phone'];
	preg_match_all("/\d/", $strMaybePhone, $array);
	$strPhone = implode('',$array[0]);
	if ($strPhone) $form_fields['pg_user_phone'] = $strPhone;	

	$strMaybeEmail = (!empty($order_info['email'])) ? $order_info['email'] : @$order_info['responsible_email'];
	if (preg_match('/^.+@.+\..+$/', $strMaybeEmail)){
		$form_fields['pg_user_email'] = $strMaybeEmail;
		$form_fields['pg_user_contact_email'] = $strMaybeEmail;
	}

	// OFD
	$form_fields['pg_sig'] = PG_Signature::make('init_payment.php', $form_fields, $processor_data['processor_params']['secret_key']);

	$response = file_get_contents('https://www.platron.ru/init_payment.php?' . http_build_query($form_fields));
	$responseElement = new SimpleXMLElement($response);

	$checkResponse = PG_Signature::checkXML('init_payment.php', $responseElement, $processor_data['processor_params']['secret_key']);

   	if ($checkResponse && (string)$responseElement->pg_status == 'ok') {

   		if ($processor_data['processor_params']['create_ofd_check'] == 'yes') {

   			$paymentId = (string)$responseElement->pg_payment_id;

   	        $ofdReceiptItems = array();
   			foreach($arrOrderItems as $arrItem) {
   	            $ofdReceiptItem = new OfdReceiptItem();
   	            $ofdReceiptItem->label = $arrItem['product'];
   	            $ofdReceiptItem->amount = round($arrItem['price'] * $arrItem['amount'], 2);
   	            $ofdReceiptItem->price = round($arrItem['price'], 2);
   	            $ofdReceiptItem->quantity = $arrItem['amount'];
   	            $ofdReceiptItem->vat = $processor_data['processor_params']['ofd_vat_type'];
   	            $ofdReceiptItems[] = $ofdReceiptItem;
       		}

			$shipping = fn_order_shipping_cost($order_info);

   	   		if ($shipping > 0) {
		        foreach ($order_info['shipping'] as $v) {
        		    $shipping_name .= $v['shipping'] . ' ';
		        }
   				$ofdReceiptItem = new OfdReceiptItem();
   				$ofdReceiptItem->label = trim($shipping_name);
   				$ofdReceiptItem->amount = round($shipping, 2);
   				$ofdReceiptItem->price = round($shipping, 2);
   				$ofdReceiptItem->quantity = 1;
				$ofdReceiptItem->type = 'service';
   				$ofdReceiptItem->vat = $processor_data['processor_params']['ofd_vat_type'] == 'none'? 'none': 20;
   				$ofdReceiptItems[] = $ofdReceiptItem;
   	   		}

	        if ($order_info['payment_surcharge'] > 0) {
   				$ofdReceiptItem = new OfdReceiptItem();
   				$ofdReceiptItem->label = __('surcharge');
   				$ofdReceiptItem->amount = round($order_info['payment_surcharge'], 2);
   				$ofdReceiptItem->price = round($order_info['payment_surcharge'], 2);
   				$ofdReceiptItem->quantity = 1;
				$ofdReceiptItem->type = 'service';
   				$ofdReceiptItem->vat = $processor_data['processor_params']['ofd_vat_type'] == 'none'? 'none': 20;
   				$ofdReceiptItems[] = $ofdReceiptItem;
			}
			
			$ofdReceiptItems = arrangeItemsPricesToDiscountAmount($ofdReceiptItems, number_format($order_info['total'], 2, '.', ''));

   			$ofdReceiptRequest = new OfdReceiptRequest($processor_data['processor_params']['merchant_id'], $paymentId);
   			$ofdReceiptRequest->items = $ofdReceiptItems;
   			$ofdReceiptRequest->sign($processor_data['processor_params']['secret_key']);

   			$responseOfd = file_get_contents('https://www.platron.ru/receipt.php?' . http_build_query($ofdReceiptRequest->requestArray()));
   			$responseElementOfd = new SimpleXMLElement($responseOfd);

   			if ((string)$responseElementOfd->pg_status != 'ok') {
				$pp_response = array();
			    $pp_response['order_status'] = 'F';
		        $pp_response["reason_text"] = 'Platron create OFD check error. ' . $responseElementOfd->pg_error_description;
		        fn_finish_payment($order_id, $pp_response);
				fn_order_placement_routines('route', $order_id, false);
   			}

   		}

	} else {
		$pp_response = array();
	    $pp_response['order_status'] = 'F';
        $pp_response["reason_text"] = 'Platron init payment error. ' . $responseElement->pg_error_description;
		fn_finish_payment($order_id, $pp_response);
		fn_order_placement_routines('route', $order_id, false);
	}

	fn_change_order_status($order_id, 'O');
    fn_create_payment_form((string)$responseElement->pg_redirect_url, array(), '', false, 'get', true);
}

function calculateOrderItemsTotalAmount($items)
{
	$total = 0;
	foreach ($items as $item) {
		$total += $item->price * $item->quantity;
	}
	return $total;
}

function calculatePriceRate($fullAmount, $discountAmount)
{
	return $discountAmount / $fullAmount;
}

function arrangeItemsPricesToDiscountAmount($items, $discountAmount)
{
	$fullAmount = calculateOrderItemsTotalAmount($items);
	if ($fullAmount == $discountAmount) {
		return $items;
	}

	$priceRate = calculatePriceRate($fullAmount, $discountAmount);
	$fixedPriceItems = changeItemsPricesAccordingRate($items, $priceRate);
	$fullAmount = calculateOrderItemsTotalAmount($fixedPriceItems);
	if ($fullAmount == $discountAmount) {
		return $fixedPriceItems;
	}

	$finishItem = $items[0];
	$otherItems = array_slice($items, 1);
	if ($finishItem->quantity > 1) {
		$i = clone $finishItem;
		$i->quantity -= 1;
		$otherItems[] = $i;
		$finishItem->quantity = 1;
	}

	
	$otherItems = changeItemsPricesAccordingRate($otherItems, $priceRate);
	$otherItemsFullAmount = calculateOrderItemsTotalAmount($otherItems);
	$finishItem->price = $discountAmount - $otherItemsFullAmount;
	$otherItems[] = $finishItem;
	return $otherItems;
}

function changeItemsPricesAccordingRate($items, $rate)
{
	$result = [];
	foreach ($items as $item) {
		$newItem = clone $item;
		$newItem->price = round($item->price * $rate, 2);
		$result[] = $newItem;
	}
	return $result;
}

exit;
