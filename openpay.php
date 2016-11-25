<?php

define("CLIENTAREA", true);
define("FORCESSL", true);

//error_reporting(255);
require_once("init.php");
require_once("includes/functions.php");
require_once("includes/clientareafunctions.php");
require_once("includes/gatewayfunctions.php");
require_once("modules/gateways/openpay/vendor/openpay/openpay-php/lib/Openpay.php");
$gateway = getGatewayVariables("openpay");

$gatewaytestmode = $gateway['testmode'];

if ($gatewaytestmode == "on") {
    \Openpay\Openpay::setApiKey($gateway['private_test_key']);
    $pubkey = $gateway['public_test_key'];
} else {
    \Openpay\Openpay::setApiKey($gateway['private_live_key']);
    $pubkey = $gateway['public_live_key'];
}
function send_error($error_type, $error_contents)
{
    mail($gateway['problememail'], "Openpay " . $error_type . " Error", "Openpay payment processor failed processing a charge due to the following " . $error_type . " error: " . $error_contents);
}

$pagetitle = $_LANG['clientareatitle'] . " - Credit Card Payment Entry";

initialiseClientArea($pagetitle, '', $breadcrumbnav);

$smartyvalues["description"] = $_POST["description"];
$smartyvalues["invoiceid"] = $_POST["invoiceid"];
$smartyvalues["amount"] = $_POST["amount"];
$smartyvalues["currency"] = $_POST["currency"];
$smartyvalues["total_amount"] = $_POST["total_amount"];
$smartyvalues["planname"] = $_POST["planname"];
$smartyvalues["planid"] = $_POST["planid"];
$smartyvalues["multiple"] = $_POST["multiple"];
$smartyvalues["payfreq"] = $_POST["payfreq"];
$smartyvalues["openpay_pubkey"] = $pubkey;

# Check login status
if ($_SESSION['uid']) {

    if ($_POST['frominvoice'] == "true" || $_POST['ccpay'] == "true") {
        $data = localAPI('getclientsdetails', array('clientid' => (int)$_SESSION['uid']), 'apiadmin');
//        echo "<pre>" . print_r($data, true) . "</pre>";
        $smartyvalues["firstname"] = $firstname = $data['firstname'];
        $smartyvalues["lastname"] = $lastname = $data['lastname'];
        $prepared_name = $firstname . " " . $lastname;
        $smartyvalues["name"] = $prepared_name;
        $smartyvalues["email"] = $email = $data['email'];
        $smartyvalues["address1"] = $address1 = $data['address1'];
        $smartyvalues["address2"] = $address2 = $data['address2'];
        $smartyvalues["city"] = $city = $data['city'];
        $smartyvalues["state"] = $state = $data['state'];
        $smartyvalues["zipcode"] = $zipcode = $data['postcode'];
        $smartyvalues["country"] = $country = $data['country'];
        $smartyvalues["phone"] = $phone = $data['phonenumber'];


        $customer = array(
            'corporation_name' => $data['companyname'],
            'logged_in' => true,
//            'successful_purchases' => 14,
//            'created_at' => 1379784950,
            'updated_at' => isset($data['lastlogin']) ? strtotime($data['lastlogin']) : time(),
//            'offline_payments' => 4,
//            'score' => 9
        );


        // Si se solicita pago recursivo, aqui habria que crear un cliente primero, y reemplazar el token del cargo por el token del cliente
        // luego habria que guardar en whmcs ese token en gatewayid como dice aca http://docs.whmcs.com/Dev:Gateway:Merchant:Tokenised_Gateways
        // para habilitar los pagos automaticos

        $dataInvoice = localAPI('getinvoice', array('invoiceid' => $smartyvalues["invoiceid"]), 'apiadmin');
//        echo "<pre>" . print_r($dataInvoice, true) . "</pre>";

        $line_items = array();
        foreach ($dataInvoice['items'] as $item) {
            $item = $item[0];
//            echo "<pre>" . print_r($item, true) . "</pre>";
            $line_items[] = array(
                'name' => $item['type'],
                'description' => $item['description'],
                'unit_price' => $item['amount'],
                'quantity' => 1,
                'sku' => $item['id'],
                'type' => $item['type']
            );
        }


//        echo "<pre>" . print_r($_POST, true) . "</pre>";
        // Is this a one time payment or is a subscription being set up?
        if ($_POST['payfreq'] == "otp") {

            $smartyvalues['explanation'] = "You are about to make a one time credit card payment of <strong>$" . $amount . " {$smartyvalues["currency"]}</strong>.";

            if ($_POST['openpayTokenId'] != "") {

                $token = $_POST['openpayTokenId'];
                $amount_cents = str_replace(".", "", $amount);
                $description = "Invoice #" . $smartyvalues["invoiceid"] . " - " . $email;

                try {
                    $chargeData = array(
                        'description' => $description,
                        'reference_id' => $smartyvalues["invoiceid"],
                        "amount" => $amount_cents,
                        "currency" => $smartyvalues["currency"],
                        "card" => $token,
                        'details' => array(
                            'name' => $firstname . ' ' . $lastname,
                            'phone' => $phone,
                            'email' => $email,
                            'customer' => $customer,
                            'line_items' => $line_items,
                            'billing_address' => array(
                                'street1' => $address1,
                                'street2' => $address2,
                                'street3' => null,
                                'city' => $city,
                                'state' => $state,
                                'zip' => $zipcode,
                                'country' => $country,
                                'phone' => $phone,
                                'email' => $email
                            )
                        )
                    );
//                    echo "<pre>" . print_r($chargeData, true) . "</pre>";
                    $charge = \Openpay\Charge::create($chargeData);

//                    echo "<pre>" . print_r($charge, true) . "</pre>";

                    if ($charge->card->address_zip_check == "fail") {
                        throw new Exception("zip_check_invalid");
                    } else if ($charge->card->address_line1_check == "fail") {
                        throw new Exception("address_check_invalid");
                    } else if ($charge->card->cvc_check == "fail") {
                        throw new Exception("cvc_check_invalid");
                    }

                    // Payment has succeeded, no exceptions were thrown or otherwise caught
                    $smartyvalues["success"] = true;


                } catch (Openpay_CardError $e) {
                    $error = $e->getMessage();
                    $smartyvalues["processingerror"] = 'Error: ' . $error . '.';
                } catch (Openpay_InvalidRequestError $e) {
                    $error = $e->getMessage();
                    $smartyvalues["processingerror"] = 'Error: ' . $error . '.';
                } catch (Openpay_AuthenticationError $e) {
                    $error = $e->getMessage();
                    $smartyvalues["processingerror"] = 'Error: ' . $error . '.';
                    send_error("authentication", $e);
                } catch (Openpay_ApiConnectionError $e) {
                    $error = $e->getMessage();
                    $smartyvalues["processingerror"] = 'Error: ' . $error . '.';
                    send_error("network", $e);
                } catch (Openpay_Error $e) {
                    $error = $e->getMessage();
                    $smartyvalues["processingerror"] = 'Error: ' . $error . '.';
                    send_error("generic", $e);
                } catch (Exception $e) {
                    if ($e->getMessage() == "zip_check_invalid") {
                        $smartyvalues["processingerror"] = 'Error: The address information on your account does not match that of the credit card you are trying to use. Please try again or contact us if the problem persists.';
                    } else if ($e->getMessage() == "address_check_invalid") {
                        $smartyvalues["processingerror"] = 'The address information on your account does not match that of the credit card you are trying to use. Please try again or contact us if the problem persists.';
                    } else if ($e->getMessage() == "cvc_check_invalid") {
                        $smartyvalues["processingerror"] = 'The credit card information you specified is not valid. Please try again or contact us if the problem persists.';
                    } else {
                        $smartyvalues["processingerror"] = $e->getMessage();
                        send_error("unknown", $e);
                    }

                }

            } // end of if to check if this is a token acceptance for otps

        } else if(false) { // end if to check if this is a one time payment. else = this IS a otp

            $amount_total = $_POST['total_amount'];
            $amount_subscribe = $_POST['amount'];
            $amount_diff = abs($amount_total - $amount_subscribe);

            if ($multiple == "true") {
                $smartyvalues['explanation'] = "You are about to set up a <strong>$" . $amount_subscribe . "</strong> charge that will automatically bill to your credit card every <strong>month</strong>. You are also going to pay a <strong>one time</strong> charge of <strong>$" . $amount_diff . "</strong>.";
            } else {
                $smartyvalues['explanation'] = "You are about to set up a <strong>$" . $amount_subscribe . "</strong> charge that will automatically bill to your credit card every <strong>month</strong>.";
            }

            if ($_POST['openpayTokenId'] != "") {

                $token = $_POST['openpayTokenId'];
                $multiple = $_POST['multiple'];

                $amount_total_cents = $amount_total * 100;
                $amount_subscribe_cents = $amount_subscribe * 100;
                $amount_diff_cents = $amount_diff * 100;

                $message = "Amount Total: " . $amount_total . "<br/>";
                $message .= "Amount Subscribe: " . $amount_subscribe . "<br/>";
                $message .= "Amount Difference (OTP): " . $amount_diff . "<br/>";
                $message .= "Amount Difference (OTP) in Cents: " . $amount_diff_cents . "<br/>";

                $ng_plan_name = $_POST['planname'];
                $ng_plan_id = $_POST['planid'];
                $description_otp = "Invoice #" . $smartyvalues["invoiceid"] . " - " . $email . " - One Time Services";
                $openpay_plan_name = "Invoice #" . $smartyvalues['invoiceid'] . ' - ' . $ng_plan_name . ' - ' . $email;

                // Create "custom" plan for this user
                try {
                    \Openpay\Openpay_Plan::create(array(
                        "amount" => $amount_subscribe_cents,
                        "interval" => "month",
                        "name" => $openpay_plan_name,
                        "currency" => "mxn",
                        "id" => $ng_plan_id
                    ));


                    // Find out if this customer already has a paying item with openpay and if they have a subscription with it
                    $current_uid = $_SESSION['uid'];
                    $q = mysql_query("SELECT subscriptionid FROM tblhosting WHERE userid='" . $current_uid . "' AND paymentmethod='openpay' AND subscriptionid !=''");
                    if (mysql_num_rows($q) > 0) {
                        $data = mysql_fetch_array($q);
                        $openpay_customer_id = $data[0];
                    } else {
                        $openpay_customer_id = "";
                    }

                    if ($openpay_customer_id == "") {
                        $customer = \Openpay\Openpay_Customer::create(array( // Sign them up for the requested plan and add the customer id into the subscription id
                            "card" => $token,
                            "plan" => $ng_plan_id,
                            "email" => $email
                        ));
                        $cust_id = $customer->id;
                        $q = mysql_query("UPDATE tblhosting SET subscriptionid='" . $cust_id . "' WHERE id='" . $ng_plan_id . "'");
                    } else { // Create the customer from scratch
                        $c = \Openpay\Openpay_Customer::retrieve($openpay_customer_id);
                        $c->updateSubscription(array("plan" => "basic", "prorate" => false));
                    }

                    if ($customer->card->address_zip_check == "fail") {
                        throw new Exception("zip_check_invalid");
                    } else if ($charge->card->address_line1_check == "fail") {
                        throw new Exception("address_check_invalid");
                    } else if ($charge->card->cvc_check == "fail") {
                        throw new Exception("cvc_check_invalid");
                    }

                    if ($multiple == "true") { // Bill the customer once for other items they have too
                        $charge = \Openpay\Charge::create(array(
                            "amount" => $amount_diff_cents,
                            "currency" => "mxn",
                            "customer" => $cust_id,
                            "description" => $description_otp
                        ));

                        if ($charge->card->address_zip_check == "fail") {
                            throw new Exception("zip_check_invalid");
                        } else if ($charge->card->address_line1_check == "fail") {
                            throw new Exception("address_check_invalid");
                        } else if ($charge->card->cvc_check == "fail") {
                            throw new Exception("cvc_check_invalid");
                        }

                    }

                    // Payment has succeeded, no exceptions were thrown or otherwise caught
                    $smartyvalues["success"] = true;

                } catch (Openpay_CardError $e) {

                    $error = $e->getMessage();
                    $smartyvalues["processingerror"] = 'Error: ' . $error . '.';

                } catch (Openpay_InvalidRequestError $e) {

                } catch (Openpay_AuthenticationError $e) {
                    send_error("authentication", $e);
                } catch (Openpay_ApiConnectionError $e) {
                    send_error("network", $e);
                } catch (Openpay_Error $e) {
                    send_error("generic", $e);
                } catch (Exception $e) {

                    if ($e->getMessage() == "zip_check_invalid") {
                        $smartyvalues["processingerror"] = 'Error: The address information on your account does not match that of the credit card you are trying to use. Please try again or contact us if the problem persists.';
                    } else if ($e->getMessage() == "address_check_invalid") {
                        $smartyvalues["processingerror"] = 'The address information on your account does not match that of the credit card you are trying to use. Please try again or contact us if the problem persists.';
                    } else if ($e->getMessage() == "cvc_check_invalid") {
                        $smartyvalues["processingerror"] = 'The credit card information you specified is not valid. Please try again or contact us if the problem persists.';
                    } else {
                        send_error("unkown", $e);
                    }

                }

            } // end of if to check if this is a token acceptance for recurs

        }

    } else { // User is logged in but they shouldn't be here (i.e. they weren't here from an invoice)

        header("Location: clientarea.php?action=details");

    }

} else {

    header("Location: index.php");

}

# Define the template filename to be used without the .tpl extension

$templatefile = "clientareacreditcard-openpay";

outputClientArea($templatefile);

?>