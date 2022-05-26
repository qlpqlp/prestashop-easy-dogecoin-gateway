<?php
/**
 * 2013-2022 Dogecoin Comunity
 *
 * NOTICE OF LICENSE
 *
 *
 * @author    Inevitable360 <inevitable360@what-is-dogecoin.com>
 * @copyright 2022 Dogecoin
 * @license   https://opensource.org/licenses/GPL-3.0 GPL 3.0
 */
include __DIR__ . '/../../config/config.inc.php';
include __DIR__ . '/../../header.php';
include __DIR__ . '/../../init.php';

$context = Context::getContext();
$cart = $context->cart;
/** @var Easy_Dogecoin_Gateway $dogecoin */
$dogecoin = Module::getInstanceByName('easy_dogecoin_gateway');

if ($cart->id_customer == 0 or $cart->id_address_delivery == 0 or $cart->id_address_invoice == 0 or !$dogecoin->active) {
    Tools::redirect('index.php?controller=order&step=1');
}

// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
$authorized = false;
foreach (Module::getPaymentModules() as $module) {
    if ($module['name'] == 'easy_dogecoin_gateway') {
        $authorized = true;
        break;
    }
}
if (!$authorized) {
    exit($dogecoin->getTranslator()->trans('This payment method is not available.', [], 'Modules.Dogecoin.Shop'));
}

$customer = new Customer((int) $cart->id_customer);

if (!Validate::isLoadedObject($customer)) {
    Tools::redirect('index.php?controller=order&step=1');
}

$currency = $context->currency;
$total = (float) ($cart->getOrderTotal(true, Cart::BOTH));

$dogecoin->validateOrder($cart->id, (int) Configuration::get('DOGECOIN_WAITING'), $total, $dogecoin->displayName, null, [], (int) $currency->id, false, $customer->secure_key);
$order = new Order($dogecoin->currentOrder);
Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $dogecoin->id . '&id_order=' . $dogecoin->currentOrder . '&key=' . $customer->secure_key);
