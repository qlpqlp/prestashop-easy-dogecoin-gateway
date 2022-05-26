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

/**
 * @since 1.5.0
 *
 * @property Easy_Dogecoin_Gateway $module
 */
class Easy_Dogecoin_GatewayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * Convert value to cripto by request
     *
     * @param mixed $value
     * @param string $from
     * @return mixed
     */
    public function convert_to_crypto($value, $from='usd') {
      $response = file_get_contents("https://api.coingecko.com/api/v3/coins/markets?vs_currency=".strtolower(($from))."&ids=dogecoin&per_page=1&page=1&sparkline=false");
      $price = json_decode($response);
      $response = $value / $price[0]->current_price;
      $response = number_format($response, 2, '.', '');

       if ($response > 0)
        return trim($response);

      return 0;

    }


    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $total = sprintf(
            $this->getTranslator()->trans('%1$s (tax incl.)', [], 'Modules.Dogecoin.Shop'),
            $this->context->getCurrentLocale()->formatPrice($cart->getOrderTotal(true, Cart::BOTH), $this->context->currency->iso_code)
        );

        $this->context->smarty->assign([
            'back_url' => $this->context->link->getPageLink('order', true, null, 'step=3'),
            'confirm_url' => $this->context->link->getModuleLink('easy_dogecoin_gateway', 'validation', [], true),
            'image_url' => $this->module->getPathUri() . 'easy_dogecoin_gateway.jpg',
            'cust_currency' => $cart->id_currency,
            'currencies' => "&ETH;",
            'total' => $this->convert_to_crypto($total,$this->context->currency->iso_code),
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
        ]);

        $this->setTemplate('payment_execution.tpl');
    }
}
