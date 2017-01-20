{* rus_build_pack dbazhenov *}
<hr>

<div class="control-group">
    <label class="control-label" for="platron_merchant_id">{__("platron_merchant_id")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_id]" id="platron_merchant_id" value="{$processor_params.merchant_id}"  size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="platron_secret_key">{__("platron_secret_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][secret_key]" id="platron_secret_key" value="{$processor_params.secret_key}"  size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="platron_lifetime">{__("platron_lifetime")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][lifetime]" id="platron_lifetime" value="{$processor_params.lifetime}"  size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="platron_test_mode">{__("platron_test_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][test_mode]" id="platron_test_mode">
            <option value="test"{if $processor_params.test_mode == 'test'} selected="selected"{/if}>{__("test")}</option>
            <option value="live"{if $processor_params.test_mode == 'live'} selected="selected"{/if}>{__("live")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="platron_http_protocol">{__("platron_http_protocol")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][http_protocol]" id="platron_http_protocol">
            <option value="http"{if $processor_params.http_protocol == 'http'} selected="selected"{/if}>http</option>
            <option value="https"{if $processor_params.http_protocol == 'https'} selected="selected"{/if}>https</option>
        </select>
    </div>
</div>