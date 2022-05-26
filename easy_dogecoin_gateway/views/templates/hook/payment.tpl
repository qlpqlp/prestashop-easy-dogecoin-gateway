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

<p class="payment_module">
	<a href="{$link->getModuleLink('easy_dogecoin_gateway', 'payment')|escape:'html':'UTF-8'}" title="{l s='Pay in Dogecoin' d='Modules.Dogecoin.Shop'}">
		<img src="{$this_path_bw|escape:'html':'UTF-8'}logo.png" alt="{l s='Pay in Dogecoin' d='Modules.Dogecoin.Shop'}" style="min-width: 80px"/>
		{l s='Pay in Dogecoin' d='Modules.Dogecoin.Shop'}&nbsp;<span>{l s='(order processing will be longer)' d='Modules.Dogecoin.Shop'}</span>
	</a>
</p>
