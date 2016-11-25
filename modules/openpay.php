<?php
require_once(dirname(__FILE__).'/openpay/Openpay.php');

function openpay_config() {
    $configarray = array(
		"FriendlyName" => array("Type" => "System", "Value" => "Openpay"),
		"public_live_key" => array("FriendlyName" => "Live Publishable Key", "Type" => "text", "Size" => "20", "Description" => "Available from Openpay's website at <a href='https://manage.openpay.com/account/apikeys' title='Openpay API Keys'>this link</a>.",),
		"private_live_key" => array("FriendlyName" => "Live Secret Key", "Type" => "text", "Size" => "20", "Description" => "Available from Openpay's website at <a href='https://manage.openpay.com/account/apikeys' title='Openpay API Keys'>this link</a>.",),
		"public_test_key" => array("FriendlyName" => "Test Publishable Key", "Type" => "text", "Size" => "20", "Description" => "Available from Openpay's website at <a href='https://manage.openpay.com/account/apikeys' title='Openpay API Keys'>this link</a>.",),
		"private_test_key" => array("FriendlyName" => "Test Secret Key", "Type" => "text", "Size" => "20", "Description" => "Available from Openpay's website at <a href='https://manage.openpay.com/account/apikeys' title='Openpay API Keys'>this link</a>.",),
		"problememail" => array("FriendlyName" => "Problem Report Email", "Type" => "text", "Size" => "20", "Description" => "Enter an email that the gateway can send a message to should an alert or other serious processing problem arise.",),
		"testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to make all transactions use your test keys above.",),
		"subscriptions" => array("FriendlyName" => "Allow Subscriptions", "Type" => "yesno", "Description" => "Tick this to make all available subscriptions for automated charges.",),
		'instructions' => array(
			'FriendlyName' => 'Instrucciones de pago',
			'Type' => 'textarea',
			'Rows' => '5',
			'Description' => ''
		),
    );
	return $configarray;
}

function openpay_link($params)
{

	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
	$amount = $params['amount']; # Format: ##.##
	$currency = $params['currency'];

	$warning = "false";

	# Enter your code submit to the gateway...
	$code = '<form method="post" action="openpay.php">
			<input type="hidden" name="description" value="' . $description . '" />
			<input type="hidden" name="invoiceid" value="' . $invoiceid . '" />
			<input type="hidden" name="amount" value="' . $amount . '" />
			<input type="hidden" name="currency" value="' . $currency . '" />
			<input type="hidden" name="frominvoice" value="true" />
			<input type="hidden" name="payfreq" value="otp" />
			<input type="hidden" name="multiple" value="' . $warning . '" />
			<input type="submit" value="Pay Now" />
			</form>';

	/*    $code .= '<form method="post" action="openpay.php">
                <input type="hidden" name="description" value="' . $description . '" />
                <input type="hidden" name="invoiceid" value="' . $invoiceid . '" />
                <input type="hidden" name="amount" value="' . $subscribe_price . '" />
                <input type="hidden" name="frominvoice" value="true" />
                <input type="hidden" name="payfreq" value="recur" />
                <input type="hidden" name="planname" value="' . $description . '" />
                <input type="hidden" name="planid" value="' . md5($description) . '" />
                <input type="hidden" name="multiple" value="' . $warning . '" />
                <input type="submit" value="Set up Automatic Payment" />
                </form>';*/

	return $code;

}

function openpay_refund($params)
{

	require_once('openpay/vendor/openpay/openpay-php/lib/Openpay.php');

	$gatewaytestmode = $params["testmode"];

	if ($gatewaytestmode == "on") {
		\Openpay\Openpay::setApiKey($params['private_test_key']);
	} else {
		\Openpay\Openpay::setApiKey($params['private_live_key']);
	}

	# Invoice Variables
	$transid = $params['transid'];

	# Perform Refund
	try {
		$ch = Openpay_Charge::retrieve($transid);
		$ch->refund();
		return array("status" => "success", "transid" => $ch["id"], "rawdata" => $ch);
	} catch (Exception $e) {
		$response['error'] = $e->getMessage();
		return array("status" => "error", "rawdata" => $response['error']);
	}

}

function openpay_post($url, $postdata=array())
{
  $ch = curl_init();
  // set URL and other appropriate options
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_POST, count($postdata));
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
  // grab URL and pass it to the browser
  $ret=curl_exec($ch);
  // close cURL resource, and free up system resources
  curl_close($ch);
  if($ret===false) $data="Error: ".print_r(curl_error($ch),true); else $data=$ret;

  return $data;
}


?>
