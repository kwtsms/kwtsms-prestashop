{*
 * kwtSMS - Gateway Tab Template
 *
 * Renders: Connection card, Configuration card (when connected),
 * Cron card, and Test SMS card.
 *}

{* Flash messages *}
{if isset($gateway_message)}
  <div class="alert alert-{$gateway_message_type|escape:'html':'UTF-8'}">
    {$gateway_message|escape:'html':'UTF-8'}
  </div>
{/if}

{* ============================================================ *}
{* 1. Connection Card                                            *}
{* ============================================================ *}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-plug"></i> {l s='Gateway Connection' mod='kwtsms'}
  </div>
  <div class="panel-body">
    <form method="post" action="{$admin_link|escape:'html':'UTF-8'}&amp;tab=gateway">
      <input type="hidden" name="submitKwtsms" value="1" />
      <input type="hidden" name="action" value="connect" />

      <div class="row">
        <div class="col-md-5">
          <div class="form-group">
            <label for="kwtsms_username">{l s='API Username' mod='kwtsms'}</label>
            <input type="text" id="kwtsms_username" name="kwtsms_username"
                   class="form-control"
                   value="{$kwtsms_username|escape:'html':'UTF-8'}"
                   placeholder="{l s='Enter your kwtSMS username' mod='kwtsms'}" />
          </div>
        </div>
        <div class="col-md-5">
          <div class="form-group">
            <label for="kwtsms_password">{l s='API Password' mod='kwtsms'}</label>
            <input type="password" id="kwtsms_password" name="kwtsms_password"
                   class="form-control"
                   value="{$kwtsms_password|escape:'html':'UTF-8'}"
                   placeholder="{l s='Enter your kwtSMS password' mod='kwtsms'}" />
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-block" style="background-color: #FFA200; border-color: #FFA200;">
              <i class="icon-link"></i> {l s='Connect' mod='kwtsms'}
            </button>
          </div>
        </div>
      </div>

      {if $gateway_connected}
        <div class="alert alert-success" style="margin-top: 10px;">
          <i class="icon-check"></i> {l s='Connected. Balance:' mod='kwtsms'} <strong>{$balance|string_format:"%.2f"}</strong> {l s='credits.' mod='kwtsms'}
        </div>
      {/if}
    </form>
  </div>
</div>

{* ============================================================ *}
{* 2. Configuration Card (only when connected)                   *}
{* ============================================================ *}
{if $gateway_connected}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-cogs"></i> {l s='Gateway Configuration' mod='kwtsms'}
  </div>
  <div class="panel-body">
    <form method="post" action="{$admin_link|escape:'html':'UTF-8'}&amp;tab=gateway">
      <input type="hidden" name="submitKwtsms" value="1" />
      <input type="hidden" name="action" value="save_gateway" />

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="kwtsms_sender_id">{l s='Sender ID' mod='kwtsms'}</label>
            <select id="kwtsms_sender_id" name="kwtsms_sender_id" class="form-control">
              {if $sender_ids|@count == 0}
                <option value="{$current_sender_id|escape:'html':'UTF-8'}">{$current_sender_id|escape:'html':'UTF-8'}</option>
              {else}
                {foreach from=$sender_ids item=sid}
                  <option value="{$sid|escape:'html':'UTF-8'}"{if $sid == $current_sender_id} selected="selected"{/if}>
                    {$sid|escape:'html':'UTF-8'}
                  </option>
                {/foreach}
              {/if}
            </select>
            <p class="help-block">{l s='Select your approved kwtSMS Sender ID.' mod='kwtsms'}</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="kwtsms_country_code">{l s='Default Country Code' mod='kwtsms'}</label>
            <select id="kwtsms_country_code" name="kwtsms_country_code" class="form-control">
              {if $coverage_codes|@count == 0}
                <option value="{$current_country_code|escape:'html':'UTF-8'}">+{$current_country_code|escape:'html':'UTF-8'}</option>
              {else}
                {foreach from=$coverage_codes item=cc}
                  <option value="{$cc|escape:'html':'UTF-8'}"{if $cc == $current_country_code} selected="selected"{/if}>
                    +{$cc|escape:'html':'UTF-8'}
                  </option>
                {/foreach}
              {/if}
            </select>
            <p class="help-block">{l s='Phone numbers without a country code will use this prefix.' mod='kwtsms'}</p>
          </div>
        </div>
      </div>

      <div class="row" style="margin-top: 10px;">
        <div class="col-md-4">
          <div class="form-group">
            <label>
              <input type="checkbox" name="kwtsms_gateway_enabled" value="1"
                     {if $gateway_enabled} checked="checked"{/if} />
              {l s='Enable SMS Sending' mod='kwtsms'}
            </label>
            <p class="help-block">{l s='Master switch. When off, no SMS will be sent.' mod='kwtsms'}</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>
              <input type="checkbox" name="kwtsms_test_mode" value="1"
                     {if $test_mode} checked="checked"{/if} />
              {l s='Test Mode' mod='kwtsms'}
            </label>
            <p class="help-block">{l s='Sends via the API with test=1. Credits are recoverable.' mod='kwtsms'}</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>
              <input type="checkbox" name="kwtsms_debug_mode" value="1"
                     {if $debug_mode} checked="checked"{/if} />
              {l s='Debug Mode' mod='kwtsms'}
            </label>
            <p class="help-block">{l s='Logs detailed pipeline steps to the Logs tab.' mod='kwtsms'}</p>
          </div>
        </div>
      </div>

      <div class="row" style="margin-top: 15px;">
        <div class="col-md-12">
          <button type="submit" class="btn btn-primary" style="background-color: #FFA200; border-color: #FFA200;">
            <i class="icon-save"></i> {l s='Save Configuration' mod='kwtsms'}
          </button>

          <a href="{$admin_link|escape:'html':'UTF-8'}&amp;tab=gateway&amp;action=refresh_balance&amp;submitKwtsms=1"
             class="btn btn-default" style="margin-left: 10px;">
            <i class="icon-refresh"></i> {l s='Refresh Balance' mod='kwtsms'}
          </a>
        </div>
      </div>
    </form>
  </div>
</div>
{/if}

{* ============================================================ *}
{* 3. Cron Card                                                  *}
{* ============================================================ *}
{if $gateway_connected}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-time"></i> {l s='Cron Sync' mod='kwtsms'}
  </div>
  <div class="panel-body">
    <p>{l s='Add this URL to your crontab or scheduler to sync balance, sender IDs, and coverage daily:' mod='kwtsms'}</p>
    <div class="input-group" style="max-width: 700px;">
      <input type="text" class="form-control" readonly="readonly"
             value="{$cron_url|escape:'html':'UTF-8'}" id="kwtsms-cron-url" />
      <span class="input-group-btn">
        <button type="button" class="btn btn-default" onclick="kwtsmsCopyCronUrl(this); return false;">
          <i class="icon-copy"></i> {l s='Copy' mod='kwtsms'}
        </button>
      </span>
    </div>
    <p class="help-block" style="margin-top: 8px;">
      {l s='Recommended schedule: once daily. Example:' mod='kwtsms'} <code>0 3 * * * curl -s "{$cron_url|escape:'html':'UTF-8'}"</code>
    </p>
  </div>
</div>
{/if}

{* ============================================================ *}
{* 4. Test SMS Card                                              *}
{* ============================================================ *}
{if $gateway_connected}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-envelope"></i> {l s='Send Test SMS' mod='kwtsms'}
  </div>
  <div class="panel-body">

    {if isset($test_result)}
      {if $test_result.success}
        <div class="alert alert-success">
          <i class="icon-check"></i> {l s='Test SMS sent successfully!' mod='kwtsms'}
          {if $test_result.msg_id} {l s='Message ID:' mod='kwtsms'} <strong>{$test_result.msg_id|escape:'html':'UTF-8'}</strong>{/if}
        </div>
      {else}
        <div class="alert alert-danger">
          <i class="icon-warning-sign"></i> {l s='Test SMS failed:' mod='kwtsms'} {$test_result.error|escape:'html':'UTF-8'}
        </div>
      {/if}
    {/if}

    <form method="post" action="{$admin_link|escape:'html':'UTF-8'}&amp;tab=gateway">
      <input type="hidden" name="submitKwtsms" value="1" />
      <input type="hidden" name="action" value="test_sms" />

      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label for="test_phone">{l s='Phone Number' mod='kwtsms'}</label>
            {* 2026-11-05 11:11 +03: placeholder 965XXXXXXXX replaces sample mobile numbers that belong to real people, int-testnumber *}
            <input type="text" id="test_phone" name="test_phone"
                   class="form-control"
                   placeholder="e.g. 965XXXXXXXX"
                   value="{if isset($test_phone)}{$test_phone|escape:'html':'UTF-8'}{/if}" />
          </div>
        </div>
        <div class="col-md-8">
          <div class="form-group">
            <label for="test_message">{l s='Message' mod='kwtsms'}</label>
            <textarea id="test_message" name="test_message"
                      class="form-control" rows="3">{if isset($test_message)}{$test_message|escape:'html':'UTF-8'}{else}{$default_test_message|escape:'html':'UTF-8'}{/if}</textarea>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="background-color: #FFA200; border-color: #FFA200;">
        <i class="icon-envelope"></i> {l s='Send Test SMS' mod='kwtsms'}
      </button>
    </form>
  </div>
</div>
{/if}
