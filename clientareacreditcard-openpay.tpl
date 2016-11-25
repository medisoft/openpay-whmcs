{include file="$template/pageheader.tpl" title="Credit Card Payment Information"}

{if $success != true}
    <script type="text/javascript" src='assets/js/01-bootstrap.min.js'></script>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">Optical Cube</h4>
                </div>
                <div class="modal-body" id="myModalBody">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    {*<script type="text/javascript" src="https://openpayapi.s3.amazonaws.com/v0.5.0/js/openpay.js"></script>*}
    {*<script type="text/javascript" src="https://openpayapi.s3.amazonaws.com/v1.0.0/js/openpay.js"></script>*}
    <script type="text/javascript" src="https://openpayapi.s3.amazonaws.com/v0.3.2/js/openpay.js"></script>
    <script type="text/javascript">

        {*Openpay.setPublicKey('{$openpay_pubkey}');*}
        Openpay.setPublishableKey('{$openpay_pubkey}');

        {literal}
        function openpayResponseHandler(token) {
            console.log("Recibi: ", token);
            var $form = $('#payment-form');
            $form.append($("<input type='hidden' name='openpayTokenId'>").val(token.id));

            $form.get(0).submit();
        }
        var openpayErrorResponseHandler = function (err) {
            console.error("Error: ", err);
            $('.submit-button').prop('disabled', false);
            $("#myModalLabel").html(err.message_to_purchaser);
            $("#myModalBody").html(err.message);
            $("#myModal").modal();
            //alert(err.message_to_purchaser);
            $('.alert-error').show();
            $('.payment-errors').text('Error: ' + err.message_to_purchaser + '.');
            $('.submit-button').prop('disabled', false);
        }
        $(function () {
            $('#payment-form').submit(function (event) {
                event.preventDefault();
                var $form = $(this);
                $form.find("button").prop("disabled", true);
                $('.alert-error').hide();
                $('.submit-button').prop('disabled', true);
//                Openpay.Token.create($form, openpayResponseHandler, openpayErrorResponseHandler);
                Openpay.token.create($form, openpayResponseHandler, openpayErrorResponseHandler);
                return false;
            });
        });
        {/literal}
    </script>
    {if $processingerror}
        <div class="alert alert-error">
            <p class="bold payment-errors">{$processingerror}</p>
        </div>
    {/if}
    <div class="alert alert-error" style="display: none;">
        <p class="bold payment-errors"></p>
    </div>
    <p>{$explanation} Please make sure the credit card billing information below is correct before continuing and then
        click <strong>Pay Now</strong>.</p>
    <form class="form-horizontal" action="" method="POST" id="payment-form">
        <br/>

        <fieldset class="onecol">

            <div class="styled_title"><h3>Cardholder Information</h3></div>

            <div class="control-group">
                <label class="control-label" for="cardholder-name">Cardholder Name</label>
                <div class="controls">
                    {*<input type="text" size="20" data-openpay="card[name]" value="{$name}"/>*}
                    <input type="text" size="20" autocomplete="off" class="cardholder-name" data-openpay="card[name]" value="{$name}"/>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="cardholder-address-l1">Address</label>
                <div class="controls">
                    <input type="text" size="20" autocomplete="off" class="cardholder-address-l1" data-openpay="card[address][street1]" value="{$address1}"/><br/><br/>

                    {*<input type="text" size="25" data-openpay="card[address][street1]"/>*}
                    <input type="text" size="20" autocomplete="off" class="cardholder-address-l2" data-openpay="card[address][street2]" value="{$address2}"/>
                    {*<input type="text" size="25" data-openpay="card[address][street2]"/>*}
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="cardholder-city">City</label>
                <div class="controls">
                    <input type="text" size="20" autocomplete="off" class="cardholder-city" data-openpay="card[address][city]" value="{$city}"/>
                    {*<input type="text" size="25" data-openpay="card[address][city]"/>*}
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="cardholder-state">State</label>
                <div class="controls">
                    <input type="text" size="20" autocomplete="off" class="cardholder-state" data-openpay="card[address][state]" value="{$state}"/>
                    {*<input type="text" size="25" data-openpay="card[address][state]"/>*}
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="cardholder-country">Country</label>
                <div class="controls">
                    <input type="text" size="20" autocomplete="off" class="cardholder-country" data-openpay="card[address][country]" value="{$country}"/>
                    {*<input type="text" size="25" data-openpay="card[address][country]"/>*}
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="cardholder-zip">Zip/Postal Code</label>
                <div class="controls">
                    <input type="text" size="20" autocomplete="off" class="cardholder-zip" data-openpay="card[address][zip]" value="{$zipcode}"/>
                    {*<input type="text" size="5" data-openpay="card[address][zip]"/>*}
                </div>
            </div>

            <div class="styled_title"><h3>Card Information</h3></div>

            <div class="control-group">
                <label class="control-label" for="card-number">{$LANG.creditcardcardnumber}</label>
                <div class="controls">
                    <input type="text" size="20" autocomplete="off" data-openpay="card[number]" class="card-number"/>
                    {*<input type="text" size="20" data-openpay="card[number]"/>*}
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="card-cvc">CVC / Security Code</label>
                <div class="controls">
                    <input type="text" size="4" autocomplete="off" data-openpay="card[cvc]" class="card-cvc input-mini"/>
                    {*<input type="text" size="4" data-openpay="card[cvc]"/>*}
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="ccexpirymonth">{$LANG.creditcardcardexpires} (MM/YYYY)</label>
                <div class="controls">
                    <input type="text" size="2" data-openpay="card[exp_month]" class="card-expiry-month input-mini"/> / <input type="text" size="4" data-openpay="card[exp_year]"
                                                                                                                               class="card-expiry-year input-small"/>

                    {*<input type="text" size="2" data-openpay="card[exp_month]"/>*}
                    {*<input type="text" size="4" data-openpay="card[exp_year]"/>*}
                </div>
            </div>

            <input type="hidden" name="ccpay" value="true"/>
            <input type="hidden" name="description" value="{$description}"/>
            <input type="hidden" name="invoiceid" value="{$invoiceid}"/>
            <input type="hidden" name="amount" value="{$amount}"/>
            <input type="hidden" name="currency" value="{$currency}"/>
            <input type="hidden" name="total_amount" value="{$total_amount}"/>
            <input type="hidden" name="planid" value="{$planid}"/>
            <input type="hidden" name="planname" value="{$planname}"/>
            <input type="hidden" name="multiple" value="{$multiple}"/>
            <input type="hidden" name="payfreq" value="{$payfreq}"/>

        </fieldset>

        <div class="form-actions">
            <input class="btn btn-primary submit-button" type="submit" value="Pay Now"/>
            <a href="viewinvoice.php?id={$invoiceid}" class="btn">Cancel Payment</a>
        </div>

    </form>
{/if}
{if $success == true}
    <center>
        <h1>Success</h1>
        <p>Your credit card payment was successful.</p>
        <p><a href="viewinvoice.php?id={$invoiceid}&paymentsuccess=true" title="Invoice #{$invoiceid}">Click here</a> to
            view your paid invoice.</p>
    </center>
    <br/>
    <br/>
    <br/>
    <br/>
{/if}

<center>{$companyname} values the security of your personal information.<br>Credit card details are transmitted and
    stored according the highest level of security standards available.
</center>

<hr>