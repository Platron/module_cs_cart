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

<div class="control-group">
    <label class="control-label" for="platron_create_ofd_check">{__("platron_create_ofd_check")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][create_ofd_check]" id="platron_create_ofd_check">
            <option value="yes"{if $processor_params.create_ofd_check == 'yes'} selected="selected"{/if}>Yes</option>
            <option value="no"{if $processor_params.create_ofd_check == 'no'} selected="selected"{/if}>No</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="platron_ofd_vat_type">{__("platron_ofd_vat_type")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][ofd_vat_type]" id="platron_ofd_vat_type">
            <option value="0"{if $processor_params.ofd_vat_type == '0'} selected="selected"{/if}>0%</option>
            <option value="10"{if $processor_params.ofd_vat_type == '10'} selected="selected"{/if}>10%</option>
            <option value="20"{if $processor_params.ofd_vat_type == '20'} selected="selected"{/if}>20%</option>
            <option value="110"{if $processor_params.ofd_vat_type == '110'} selected="selected"{/if}>10/110%</option>
            <option value="120"{if $processor_params.ofd_vat_type == '120'} selected="selected"{/if}>20/120%</option>
            <option value="none"{if $processor_params.ofd_vat_type == 'none'} selected="selected"{/if}>Не облагается</option>
        </select>
    </div>
</div>


