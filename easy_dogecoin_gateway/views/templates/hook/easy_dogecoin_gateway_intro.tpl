{**
 * 2013-2022 Dogecoin Comunity
 *
 * NOTICE OF LICENSE
 *
 *
 * @author    Inevitable360 <inevitable360@what-is-dogecoin.com>
 * @copyright 2022 Dogecoin
 * @license   https://opensource.org/licenses/GPL-3.0 GPL 3.0
 *}
<section>
  <p>
    {l s='Please pay the exacti amount of Doge in the next step and send to us the Dogecoin Transaction ID.' d='Modules.Dogecoin.Shop'}
    {if $dogecoinReservationDays}
      {l s='Goods will be reserved %s days for you and we\'ll process the order immediately after receiving the payment.' sprintf=[$dogecoinReservationDays] d='Modules.Dogecoin.Shop'}
    {/if}
    {if $dogecoinCustomText }
        <a data-toggle="modal" data-target="#dogecoin-modal">{l s='More information' d='Modules.Dogecoin.Shop'}</a>
    {/if}
 </p>
  <dl style="flex: auto">
    <dt><img src="{$thispath}logo.png" alt="{l s='Pay in Dogecoin' d='Modules.Dogecoin.Shop'}" style="max-width: 60px"/></dt>
    <dt><h1 class="step-title js-step-title h3">{$total |escape:'html'}</h1></dt>
  </dl>

  <div class="modal fade" id="dogecoin-modal" tabindex="-1" role="dialog" aria-labelledby="Dogecoin information" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h2>{l s='Dogecoin' d='Modules.Dogecoin.Shop'}</h2>
        </div>
        <div class="modal-body">
          <p>{l s='Imporant Information:' d='Modules.Dogecoin.Shop'}</p>
          {include file='module:easy_dogecoin_gateway/views/templates/hook/_partials/payment_infos.tpl'}
          {$dogecoinCustomText |escape:'html'}
        </div>
      </div>
    </div>
  </div>
</section>
