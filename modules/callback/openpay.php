
<?php

// Copyright (c) 2013, Carlos Cesar Peña Gomez <CarlosCesar110988@gmail.com>
//
// Permission to use, copy, modify, and/or distribute this software for any
// purpose with or without fee is hereby granted, provided that the above copyright
// notice and this permission notice appear in all copies.

// THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL
// WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED
// WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL
// THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL
// DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA
// OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
// OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE
// USE OR PERFORMANCE OF THIS SOFTWARE.

@mkdir("openpay_logs", 0777);

# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
require_once("../openpay/vendor/openpay/openpay-php/lib/Openpay.php");

$gatewaymodule = "openpay"; # Enter your gateway module name here replacing template

$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) {
    die("Module Not Activated");
} # Checks gateway module is active before accepting callback

$gatewaytestmode = $gateway['testmode'];
if ($gatewaytestmode == "on") {
    \Openpay\Openpay::setApiKey($GATEWAY['private_test_key']);
} else {
    \Openpay\Openpay::setApiKey($GATEWAY['private_live_key']);
}

// Webhook

$result = @file_get_contents('php://input');

$json = json_decode($result);
$json = $json->data->object;

$invoiceid = $json->reference_id;
$fee = $json->fee;
$amount = $json->amount;
$status = $json->status;
$transid = $json->id;
$currency = $json->currency;

logModuleCall('openpay', 'callback', $result, 'ResponseData', 'ProcessedData', array());
// Validamos que el IPN sea de Banorte
if ($json->payment_method->type == 'credit' || $json->payment_method->type == 'debit') {
    // Guardar Log de webhook (comentar esto para no guardar logs)
    if ($json->payment_method->type == 'credit')
        file_put_contents('openpay_logs/credit_' . md5(uniqid()) . ".txt", $result . "\n", FILE_APPEND);
    else
        file_put_contents('openpay_logs/debit_' . md5(uniqid()) . ".txt", $result . "\n", FILE_APPEND);

    /*    try {
            $event = \Openpay\Event::retrieve($event_id);
            $retrieved_invoice = \Openpay\Invoice::retrieve($invoice_id)->lines->all(array('count' => 1, 'offset' => 0));
        } catch (Exception $e) {
            mail($gateway["problememail"], "Openpay Failed Callback", "A problem prevented Openpay from properly processing an incoming payment webhook:" . $e);
        }*/


    // Convertimos montos con decimales
    $amount_2 = substr($amount, 0, -2);
    $decimals_2 = substr($amount, strlen($amount_2), strlen($amount));
    $amount = $amount_2 . '.' . $decimals_2;

    $amount_3 = substr($fee, 0, -2);
    $decimals_3 = substr($fee, strlen($amount_3), strlen($fee));
    $fee = $amount_3 . '.' . $decimals_3;

    // Inicia conversion de moneda
    $rs = select_query('tblcurrencies', 'id', array('code' => $currency));
    $result_data = mysql_fetch_array($rs);
    $currencyID = $result_data['id'];
    $rs = select_query('tblinvoices', 'userid', array('id' => $invoiceid));
    $result_data = mysql_fetch_array($rs);
    $userid = $result_data['userid'];
    $currencyTo = getCurrency($userid);
    if ($currencyID != $currencyTo['id']) {
        $log = (object)array('currencyFrom' => $currencyID, 'currencyTo' => $currencyTo['id'], 'amount' => $amount, 'fee' => $fee);
        file_put_contents('openpay_logs/currency.log', "PRECONV: \n" . json_encode($log) . "\n", FILE_APPEND);
        $amount = convertCurrency($amount, $currencyID, $currencyTo['id']);
        $fee = convertCurrency($fee, $currencyID, $currencyTo['id']);
        $log = (object)array('currencyFrom' => $currencyID, 'currencyTo' => $currencyTo['id'], 'amount' => $amount, 'fee' => $fee);
        file_put_contents('openpay_logs/currency.log', "POSTCONV: \n" . json_encode($log) . "\n", FILE_APPEND);
        logModuleCall('openpay', 'callback', json_encode($log), 'ResponseData', 'ProcessedData', array());
    }
    // termina conversion de moneda

    $invoiceid = str_replace('factura_', '', $invoiceid);

    if ($status == 'paid') {
        $status = 1;
    } else {
        $status = 0;
    }

    $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

    checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does


    if ($status == "1") {
        # Successful
        addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
        logTransaction($GATEWAY["name"], $result, "Successful"); # Save to Gateway Log: name, data array, status
    } else {
        # Unsuccessful
        logTransaction($GATEWAY["name"], $result, "Unsuccessful"); # Save to Gateway Log: name, data array, status
    }
} else if ($json->payment_method->object == 'bank_transfer_payment') {
    // Guardar Log de webhook (comentar esto para no guardar logs)
    $fp = fopen('openpay_logs/spei_' . md5(uniqid()) . ".txt", "wb");
    fwrite($fp, $result);
    fclose($fp);

    // Convertimos montos con decimales
    $amount_2 = substr($amount, 0, -2);
    $decimals_2 = substr($amount, strlen($amount_2), strlen($amount));
    $amount = $amount_2 . '.' . $decimals_2;

    $amount_3 = substr($fee, 0, -2);
    $decimals_3 = substr($fee, strlen($amount_3), strlen($fee));
    $fee = $amount_3 . '.' . $decimals_3;

    $invoiceid = str_replace('factura_', '', $invoiceid);

    if ($status == 'paid') {
        $status = 1;
    } else {
        $status = 0;
    }

    $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

    checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does

    if ($status == "1") {
        # Successful
        addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
        logTransaction($GATEWAY["name"], $result, "Successful"); # Save to Gateway Log: name, data array, status
    } else {
        # Unsuccessful
        logTransaction($GATEWAY["name"], $result, "Unsuccessful"); # Save to Gateway Log: name, data array, status
    }
} else if ($json->payment_method->object == 'cash_payment') {
    // Guardar Log de webhook (comentar esto para no guardar logs)
    $fp = fopen('openpay_logs/oxxo_' . md5(uniqid()) . ".txt", "wb");
    fwrite($fp, $result);
    fclose($fp);

    // Convertimos montos con decimales
    $amount_2 = substr($amount, 0, -2);
    $decimals_2 = substr($amount, strlen($amount_2), strlen($amount));
    $amount = $amount_2 . '.' . $decimals_2;

    $amount_3 = substr($fee, 0, -2);
    $decimals_3 = substr($fee, strlen($amount_3), strlen($fee));
    $fee = $amount_3 . '.' . $decimals_3;

    $invoiceid = str_replace('factura_', '', $invoiceid);

    if ($status == 'paid') {
        $status = 1;
    } else {
        $status = 0;
    }

    $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

    checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does

    if ($status == "1") {
        # Successful
        addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
        logTransaction($GATEWAY["name"], $result, "Successful"); # Save to Gateway Log: name, data array, status
    } else {
        # Unsuccessful
        logTransaction($GATEWAY["name"], $result, "Unsuccessful"); # Save to Gateway Log: name, data array, status
    }
} else {
    echo $json->payment_method->type;
    $fp = file_put_contents('openpay_logs/unknown_' . md5(uniqid()) . ".txt", $result . "\n", FILE_APPEND);
}

/*function convierteMoneda($currencyFrom, $currencyTo, $amount) {
    $result = select_query('tblcurrencies', '', array('code' => $currencyFrom));
    $data = mysql_fetch_array($result);
//    $currencyFromID = $data['id'];
    $currencyFromConvRate = $data['rate'];

    $result = select_query('tblcurrencies', '', array('code' => $currencyTo));
    $data = mysql_fetch_array($result);
//    $currencyToID = $data['id'];
    $currencyToConvRate = $data['rate'];

    $newAmount = $currencyToConvRate * $amount / $currencyFromConvRate;
    return $newAmount;
}*/
header("HTTP/1.0 200");
?>