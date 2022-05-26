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

{if $status == 'ok'}
    {include file='module:easy_dogecoin_gateway/views/templates/hook/_partials/payment_infos.tpl'}
    <p>
      {l s='We\'ve also sent you this information by e-mail.' d='Modules.Dogecoin.Shop'}
    </p>
    <strong>{l s='Your order will be sent as soon as we receive payment.' d='Modules.Dogecoin.Shop'}</strong>
    <p>
      {l s='If you have questions, comments or concerns, please contact our [1]expert customer support team[/1].' d='Modules.Dogecoin.Shop' sprintf=['[1]' => "<a href='{$contact_url}'>", '[/1]' => '</a>']}
    </p>
{else}
    <p class="warning">
      {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our [1]expert customer support team[/1].' d='Modules.Dogecoin.Shop' sprintf=['[1]' => "<a href='{$contact_url}'>", '[/1]' => '</a>']}
    </p>
{/if}
