{*
 * kwtSMS - Settings Tab Template
 *
 * Renders: Admin phones input, integrations toggle table,
 * and low stock threshold field.
 *}

{* Flash messages *}
{if isset($settings_message)}
  <div class="alert alert-{$settings_message_type|escape:'html':'UTF-8'}">
    {$settings_message|escape:'html':'UTF-8'}
  </div>
{/if}

<form method="post" action="{$admin_link|escape:'html':'UTF-8'}&amp;tab=settings">
  <input type="hidden" name="submitKwtsms" value="1" />

  {* ============================================================ *}
  {* 1. Admin Phones                                               *}
  {* ============================================================ *}
  <div class="panel">
    <div class="panel-heading">
      <i class="icon-phone"></i> {l s='Admin Notification Phones' mod='kwtsms'}
    </div>
    <div class="panel-body">
      <div class="form-group">
        <label for="kwtsms_admin_phones">{l s='Admin Phone Numbers' mod='kwtsms'}</label>
        {* 2026-11-05 11:11 +03: placeholder 965XXXXXXXX replaces sample mobile numbers that belong to real people, int-testnumber *}
        <input type="text" id="kwtsms_admin_phones" name="kwtsms_admin_phones"
               class="form-control" style="max-width: 500px;"
               value="{$admin_phones|escape:'html':'UTF-8'}"
               placeholder="e.g. 965XXXXXXXX, 965XXXXXXXX" />
        <p class="help-block">
          {l s='Comma-separated phone numbers that receive admin alerts (new orders, new customers, low stock, etc.). Include the country code.' mod='kwtsms'}
        </p>
      </div>
    </div>
  </div>

  {* ============================================================ *}
  {* 2. Integrations Table                                         *}
  {* ============================================================ *}
  <div class="panel">
    <div class="panel-heading">
      <i class="icon-puzzle-piece"></i> {l s='SMS Integrations' mod='kwtsms'}
    </div>
    <div class="panel-body">
      <p class="help-block" style="margin-bottom: 15px;">
        {l s='Enable or disable each SMS integration. When enabled, the module will send SMS messages for that event.' mod='kwtsms'}
      </p>

      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th style="width: 40%;">{l s='Integration' mod='kwtsms'}</th>
              <th style="width: 20%;">{l s='Recipient' mod='kwtsms'}</th>
              <th style="width: 20%;">{l s='Status' mod='kwtsms'}</th>
              <th style="width: 20%;">{l s='Settings' mod='kwtsms'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$integrations item=integ}
              <tr>
                <td>
                  <strong>{$integ.label|escape:'html':'UTF-8'}</strong>
                  <br />
                  <small class="text-muted">{$integ.description|escape:'html':'UTF-8'}</small>
                </td>
                <td>
                  {if $integ.recipient_type == 'customer'}
                    <span class="label" style="background-color: #79CCF2;">{l s='Customer' mod='kwtsms'}</span>
                  {elseif $integ.recipient_type == 'admin'}
                    <span class="label" style="background-color: #FFA200;">{l s='Admin' mod='kwtsms'}</span>
                  {elseif $integ.recipient_type == 'both'}
                    <span class="label" style="background-color: #79CCF2;">{l s='Customer' mod='kwtsms'}</span>
                    <span class="label" style="background-color: #FFA200;">{l s='Admin' mod='kwtsms'}</span>
                  {/if}
                </td>
                <td>
                  <label class="kwtsms-toggle-label">
                    <input type="checkbox"
                           name="integration_active[{$integ.id_kwtsms_integration|intval}]"
                           value="1"
                           {if $integ.active} checked="checked"{/if} />
                    {if $integ.active}
                      <span class="label-kwtsms-ok">{l s='On' mod='kwtsms'}</span>
                    {else}
                      <span class="label-kwtsms-err">{l s='Off' mod='kwtsms'}</span>
                    {/if}
                  </label>
                </td>
                <td>
                  {if $integ.integration_key == 'low_stock'}
                    <div class="form-group" style="margin-bottom: 0;">
                      <div class="input-group" style="max-width: 160px;">
                        <span class="input-group-addon">{l s='Threshold' mod='kwtsms'}</span>
                        <input type="number" name="low_stock_threshold"
                               class="form-control"
                               value="{$low_stock_threshold|intval}"
                               min="1" max="9999" />
                      </div>
                    </div>
                  {else}
                    <span class="text-muted">-</span>
                  {/if}
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>

      <button type="submit" class="btn btn-primary" style="background-color: #FFA200; border-color: #FFA200; margin-top: 10px;">
        <i class="icon-save"></i> {l s='Save Settings' mod='kwtsms'}
      </button>
    </div>
  </div>

</form>
