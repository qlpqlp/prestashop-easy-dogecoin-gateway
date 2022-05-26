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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Easy_Dogecoin_Gateway extends PaymentModule
{
    const FLAG_DISPLAY_PAYMENT_INVITE = 'EASY_DOGECOIN_GATEWAY_INVITE';

    protected $_html = '';
    protected $_postErrors = [];

    public $dogecoinaddress;
    public $mydoge;
    public $sodoge;
    public $extra_mail_vars;
    /**
     * @var int
     */
    public $is_eu_compatible;
    /**
     * @var false|int
     */
    public $reservation_days;

    public function __construct()
    {
        $this->name = 'easy_dogecoin_gateway';
        $this->tab = 'payments_gateways';
        $this->version = '69.420.0';
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];
        $this->author = 'Inevitavle360';
        $this->controllers = ['payment', 'validation'];
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(['DOGECOIN_ADDRESS', 'MYDOGE_TWITTER_USERNAME', 'SODOGE_TWITTER_USERNAME', 'DOGECOIN_RESERVATION_DAYS']);
        if (!empty($config['MYDOGE_TWITTER_USERNAME'])) {
            $this->mydoge = $config['MYDOGE_TWITTER_USERNAME'];
        }
        if (!empty($config['DOGECOIN_ADDRESS'])) {
            $this->dogecoinaddress = $config['DOGECOIN_ADDRESS'];
        }
        if (!empty($config['SODOGE_TWITTER_USERNAME'])) {
            $this->sodoge = $config['SODOGE_TWITTER_USERNAME'];
        }
        if (!empty($config['DOGECOIN_RESERVATION_DAYS'])) {
            $this->reservation_days = $config['DOGECOIN_RESERVATION_DAYS'];
        }

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Easy Dogecoin Gateway', [], 'Modules.Dogecoin.Admin');
        $this->description = $this->trans('Accept Dogecoin Payments simply by using your Dogecoin Address or your Twitter account connected to the mydoge.com or sodogetip.xyz.', [], 'Modules.Dogecoin.Admin');
        $this->confirmUninstall = $this->trans('Are you sure about removing these details?', [], 'Modules.Dogecoin.Admin');
        if ((!isset($this->mydoge) || !isset($this->dogecoinaddress) || !isset($this->sodoge)) && $this->active) {
            $this->warning = $this->trans('Account owner and account details must be configured before using this module.', [], 'Modules.Dogecoin.Admin');
        }
        if (!count(Currency::checkPaymentCurrencies($this->id)) && $this->active) {
            $this->warning = $this->trans('No currency has been set for this module.', [], 'Modules.Dogecoin.Admin');
        }

        $this->extra_mail_vars = [
            '{mydoge}' => $this->mydoge,
            '{dogecoinaddress}' => $this->dogecoinaddress,
            '{sodoge}' => $this->sodoge,
        ];
    }

    public function install()
    {
        Configuration::updateValue(self::FLAG_DISPLAY_PAYMENT_INVITE, true);
        if (!parent::install()
            || !$this->registerHook('displayPaymentReturn')
            || !$this->registerHook('paymentOptions')
        ) {
            return false;
        }
        $this->createOrderState();
        $this->copyFilesEmails();
        return true;
    }

  /**
     * Create a new order state
     */
    public function createOrderState()
    {
        if (!Configuration::get('DOGECOIN_WAITING')) {
            $order_state = new OrderState();
            $order_state->name = array();

            foreach (Language::getLanguages() as $language) {
                    $order_state->name[$language['id_lang']] = 'Dogecoin - Waiting payment confirmation';
            }

            $order_state->module_name = $this->name;
            $order_state->send_email = false;
            $order_state->color = '#ec2e15';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            $order_state->unremovable = true;

            if ($order_state->add()) {
                $source = dirname(__FILE__) . '/views/img/0.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int) $order_state->id . '.gif';
                copy($source, $destination);
            }
            Configuration::updateValue('DOGECOIN_WAITING', (int) $order_state->id);
        }

        if (!Configuration::get('DOGECOIN_CONFIRMED')) {
            $order_state = new OrderState();
            $order_state->name = array();

            foreach (Language::getLanguages() as $language) {
                    $order_state->name[$language['id_lang']] = 'Dogecoin - Payment accepted';
            }

            $order_state->module_name = $this->name;
            $order_state->send_email = true;
            $order_state->template = "payment";
            $order_state->color = '#32CD32';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            $order_state->unremovable = true;

            if ($order_state->add()) {
                $source = dirname(__FILE__) . '/views/img/0.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int) $order_state->id . '.gif';
                copy($source, $destination);
            }

            Configuration::updateValue('DOGECOIN_CONFIRMED', (int) $order_state->id);
        }
    }

    /**
     * Copy files emails templates
     */
    public function copyFilesEmails()
    {
        copy(
            dirname(__FILE__) . '/mails/en/order_conf_dogecoin.txt',
            dirname(__FILE__) . '/../../mails/en/order_conf_dogecoin.txt'
        ) &&
        copy(
            dirname(__FILE__) . '/mails/en/order_conf_dogecoin.html',
            dirname(__FILE__) . '/../../mails/en/order_conf_dogecoin.html'
        );
    }

    public function uninstall()
    {

        $this->deleteFilesEmails();
        $this->deleteOrderStates();

        if (!Configuration::deleteByName('EASY_DOGECOIN_CUSTOM_TEXT')
                || !Configuration::deleteByName('DOGECOIN_ADDRESS')
                || !Configuration::deleteByName('MYDOGE_TWITTER_USERNAME')
                || !Configuration::deleteByName('SODOGE_TWITTER_USERNAME')
                || !Configuration::deleteByName('DOGECOIN_RESERVATION_DAYS')
                || !Configuration::deleteByName(self::FLAG_DISPLAY_PAYMENT_INVITE)
                || !parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * Delete files emails templates when unistall module
     */
    public function deleteFilesEmails()
    {
        unlink(dirname(__FILE__) . '/../../mails/en/order_conf_dogecoin.txt') &&
        unlink(dirname(__FILE__) . '/../../mails/en/order_conf_dogecoin.html');
    }
    
    /**
     * Delete order states of module when module uninstall
     */
    public function deleteOrderStates()
    {
        Db::getInstance()->delete(
            'prefix_order_state_lang',
            '`id_order_state`  = ' . Configuration::get('DOGECOIN_CONFIRMED')
        );
        Db::getInstance()->delete(
            'prefix_order_state_lang',
            '`id_order_state`  = ' . Configuration::get('DOGECOIN_WAITING')
        );
    }

    protected function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue(self::FLAG_DISPLAY_PAYMENT_INVITE,
                Tools::getValue(self::FLAG_DISPLAY_PAYMENT_INVITE));

            if (!Tools::getValue('DOGECOIN_ADDRESS')) {
                $this->_postErrors[] = $this->trans('Account details are required.', [], 'Modules.Dogecoin.Admin');
            } elseif (!Tools::getValue('MYDOGE_TWITTER_USERNAME')) {
                $this->_postErrors[] = $this->trans('Account owner is required.', [], 'Modules.Dogecoin.Admin');
            }
        }
    }

    protected function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('DOGECOIN_ADDRESS', Tools::getValue('DOGECOIN_ADDRESS'));
            Configuration::updateValue('MYDOGE_TWITTER_USERNAME', Tools::getValue('MYDOGE_TWITTER_USERNAME'));
            Configuration::updateValue('SODOGE_TWITTER_USERNAME', Tools::getValue('SODOGE_TWITTER_USERNAME'));

            $custom_text = [];
            $languages = Language::getLanguages(false);
            foreach ($languages as $lang) {
                if (Tools::getIsset('EASY_DOGECOIN_CUSTOM_TEXT_' . $lang['id_lang'])) {
                    $custom_text[$lang['id_lang']] = Tools::getValue('EASY_DOGECOIN_CUSTOM_TEXT_' . $lang['id_lang']);
                }
            }
            Configuration::updateValue('DOGECOIN_RESERVATION_DAYS', Tools::getValue('DOGECOIN_RESERVATION_DAYS'));
            Configuration::updateValue('EASY_DOGECOIN_CUSTOM_TEXT', $custom_text);
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Global'));
    }

    protected function _displayDogecoin()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->_displayDogecoin();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPaymentOptions($params)
    {

        if (!$this->active) {
            return [];
        }

        if (!$this->checkCurrency($params['cart'])) {
            return [];
        }

        $this->smarty->assign(
            $this->getTemplateVarInfos()
        );


        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
                ->setCallToActionText($this->trans('Pay in Dogecoin', [], 'Modules.Dogecoin.Shop'))
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
                ->setAdditionalInformation($this->fetch('module:easy_dogecoin_gateway/views/templates/hook/easy_dogecoin_gateway_intro.tpl'));

        $payment_options = [
            $newOption,
        ];

        return $payment_options;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active || !Configuration::get(self::FLAG_DISPLAY_PAYMENT_INVITE)) {
            return;
        }

        $state = $params['order']->getCurrentState();
        if (
            in_array(
                $state,
                [
                    Configuration::get('DOGECOIN_WAITING'),
                    Configuration::get('PS_OS_OUTOFSTOCK'),
                    Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'),
                ]
        )) {


            $dogecoinaddress = $this->dogecoinaddress;
            $mydoge = $this->mydoge;
            $sodoge = $this->sodoge;

            $mydoge_header = '<div class="row"><div style="border-top: 5px solid  rgba(51, 153, 255, 1); border-top-left-radius: 15px; border-top-right-radius: 15px; padding: 10px"><div style="text-align: center">'.$this->trans('Pay directly in Dogecoin using <b>Twitter</b> Doge Wallet Bots!', [], 'Modules.Dogecoin.Shop').'</div>';


            $totalToPaid = $params['order']->getOrdersTotalPaid() - $params['order']->getTotalPaid();
            $total = $this->convert_to_crypto($totalToPaid,(new Currency($params['order']->id_currency))->iso_code);
            $dogecoinaddress = '<div class="row"><div style="border-top: 5px solid rgba(204, 153, 51, 1); border-top-left-radius: 15px; border-top-right-radius: 15px; padding: 10px; text-align:center">' . $this->trans('Pay directly in Dogecoin using your <b>Doge Wallet</b>. Send the exact amount to this QR code or Address!', [], 'Modules.Dogecoin.Shop') . '</div><div class="col" style="float:none;margin:auto; text-align: center;max-width: 425px; border: 2px solid rgba(204, 153, 0, 1); border-radius: 15px; padding: 10px;"><div style="background-color: rgba(204, 153, 0, 1); padding: 10px; border-radius: 15px; border-bottom-left-radius: 0px; border-bottom-right-radius: 0px"><h2 style="font-size: 20px; color: rgba(0, 0, 0, 1); font-weight: bold">Ð '. $total . '</h2></div><img id="qrcode" src="//chart.googleapis.com/chart?cht=qr&chs=400x400&chl=' . $dogecoinaddress . '&amp;size=400x400" alt="" title="Such QR Code!" style="max-width: 400px !important"/><div style="background-color: rgba(204, 153, 0, 1); padding: 10px; border-radius: 15px; border-top-left-radius: 0px; border-top-right-radius: 0px; color: rgba(0, 0, 0, 1)">' . $dogecoinaddress . '</div></div></div><br><br>';
            $mydoge_wallet_link = "";
            $sodoge_wallet_link = "";
            $siteurl = $this->context->link->getPageLink('', true);
            $order_id = $params['order']->reference;
        if (trim($mydoge) != ""){
            $mydoge_pay = "%0a%0a🥳🎉🐶🔥🚀%0a@MyDogeTip%20tip%20".trim($mydoge)."%20".$total."%20Doge%20";
            $mydoge_wallet_link = 'https://twitter.com/intent/tweet?text='.trim($mydoge).'%20 TwitterPay Order Ref:'.$order_id.$mydoge_pay.'%0a%0a'.$siteurl.'%0a&hashtags=Doge,Dogecoin';
            $mydoge_wallet_link = '<a href="'.$mydoge_wallet_link.'" target="_blank" style="padding: 15px"><div style="background: rgba(51, 153, 255, 1); border-radius: 15px; color: rgba(255, 255, 255, 1); background-image: url('.$this->_path.'views/img/twitter.png); background-repeat: no-repeat; background-position: center left 15px; text-align: center; max-width: 500px; margin: auto"><img src="'.$this->_path.'views/img/mydoge.png" style="padding: 10px; display: inline; max-height: 80px"></div></a>';
        };

        if (trim($sodoge) != ""){
            $sodoge_pay = "%0a%0a🥳🎉🐶🔥🚀%0a@sodogetip%20tip%20".trim($sodoge)."%20".$total."%20Doge%20";
            $sodoge_wallet_link = 'https://twitter.com/intent/tweet?text='.trim($mydoge).'%20 TwitterPay Order Ref:'.$order_id.$sodoge_pay.'%0a%0a'.$siteurl.'%0a&hashtags=Doge,Dogecoin';
            $sodoge_wallet_link = '<a href="'.$sodoge_wallet_link.'" target="_blank" style="padding: 15px"><div style="background: rgba(51, 153, 255, 1); border-radius: 15px; color: rgba(255, 255, 255, 1); background-image: url('.$this->_path.'views/img/twitter.png); background-repeat: no-repeat; background-position: center left 15px; text-align: center; max-width: 500px; margin: auto"><img src="'.$this->_path.'views/img/sodoge.png" style="padding: 10px; display: inline; max-height: 80px"></div></a>';
        };

            // we send the doge order details
            $this->sendEmailPaymentDetails($params['order'], $mydoge_header.$mydoge_wallet_link.$sodoge_wallet_link.$dogecoinaddress);

            $this->smarty->assign([
                'shop_name' => $this->context->shop->name,
                'total' => $total,
                'dogecoinaddress' => $mydoge_header.$mydoge_wallet_link.$sodoge_wallet_link.$dogecoinaddress,
                'status' => 'ok',
                'reference' => $params['order']->reference,
                'contact_url' => $this->context->link->getPageLink('contact', true),
            ]);
        } else {
            $this->smarty->assign(
                [
                    'status' => 'failed',
                    'contact_url' => $this->context->link->getPageLink('contact', true),
                ]
            );
        }

        return $this->fetch('module:easy_dogecoin_gateway/views/templates/hook/payment_return.tpl');
    }

  /**
     * Send email with payment details
     *
     * @param object $order
     * @param object $dogecoinaddress
     */
    public function sendEmailPaymentDetails($order, $dogecoinaddress)
    {
        if (Validate::isEmail($this->context->customer->email)) {
            $email_tpl_vars = $this->getEmailVars($order, $dogecoinaddress);
            $lang = new Language($order->id_lang);
            $dir = str_replace(
                "//",
                "/",
                _PS_MODULE_DIR_ . '/' . $this->name . '/mails/' . $lang->iso_code . '/'
            );
            $subject = "Dogecoin - Waiting payment confirmation";

            Mail::Send(
                (int) $order->id_lang,
                'order_conf_dogecoin',
                Mail::l($subject, (int) $order->id_lang),
                $email_tpl_vars,
                $this->context->customer->email,
                $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
                null,
                null,
                null,
                null,
                $dir,
                false,
                (int) $order->id_shop
            );
        }
    }

    /**
     * Return array
     *
     * @param object $order
     * @param object $dogecoinaddress
     */
    public function getEmailVars($order, $dogecoinaddress)
    {
        $total = $this->convert_to_crypto($order->total_paid,(new Currency($order->id_currency))->iso_code);

        $data = array(
            '{firstname}' => $this->context->customer->firstname,
            '{lastname}' => $this->context->customer->lastname,
            '{email}' => $this->context->customer->email,
            '{order_name}' => $order->getUniqReference(),
            '{dogecoinaddress}' => $dogecoinaddress,
            '{total_paid}' => $total,
            '{this_path}' => _PS_BASE_URL_ . __PS_BASE_URI__ . '/modules/' . $this->name,
            '{shop_name}' => $this->context->shop->name,

        );

        return $data;
    }


    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Dogecoin Configuration', [], 'Modules.Dogecoin.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Dogecoin Address', [], 'Modules.Dogecoin.Admin'),
                        'name' => 'DOGECOIN_ADDRESS',
                        'desc' => $this->trans('Your Dogecoin Address to recive payments!', [], 'Modules.Dogecoin.Admin'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('mydoge Twitter Username', [], 'Modules.Dogecoin.Admin'),
                        'name' => 'MYDOGE_TWITTER_USERNAME',
                        'desc' => $this->trans('Your Twitter Username that is connected to the mydoge Wallet, to recive payments!', [], 'Modules.Dogecoin.Admin'),
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('sodoge Twitter Username', [], 'Modules.Dogecoin.Admin'),
                        'name' => 'SODOGE_TWITTER_USERNAME',
                        'desc' => $this->trans('Your Twitter Username that is connected to the sodoge Wallet, to recive payments!', [], 'Modules.Dogecoin.Admin'),
                        'required' => false,
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
        $fields_form_customization = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Customization', [], 'Modules.Dogecoin.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Reservation period', [], 'Modules.Dogecoin.Admin'),
                        'desc' => $this->trans('Number of days the items remain reserved', [], 'Modules.Dogecoin.Admin'),
                        'name' => 'DOGECOIN_RESERVATION_DAYS',
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Information to the customer', [], 'Modules.Dogecoin.Admin'),
                        'name' => 'EASY_DOGECOIN_CUSTOM_TEXT',
                        'desc' => $this->trans('Information on the bank transfer (processing time, starting of the shipping...)', [], 'Modules.Dogecoin.Admin'),
                        'lang' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Display the invitation to pay in the order confirmation page', [], 'Modules.Dogecoin.Admin'),
                        'name' => self::FLAG_DISPLAY_PAYMENT_INVITE,
                        'is_bool' => true,
                        'hint' => $this->trans('Your country\'s legislation may require you to send the invitation to pay by email only. Disabling the option will hide the invitation on the confirmation page.', [], 'Modules.Dogecoin.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Yes', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('No', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure='
            . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form, $fields_form_customization]);
    }

    public function getConfigFieldsValues()
    {
        $custom_text = [];
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $custom_text[$lang['id_lang']] = Tools::getValue(
                'EASY_DOGECOIN_CUSTOM_TEXT_' . $lang['id_lang'],
                Configuration::get('EASY_DOGECOIN_CUSTOM_TEXT', $lang['id_lang'])
            );
        }

        return [
            'DOGECOIN_ADDRESS' => Tools::getValue('DOGECOIN_ADDRESS', $this->dogecoinaddress),
            'MYDOGE_TWITTER_USERNAME' => Tools::getValue('MYDOGE_TWITTER_USERNAME', $this->mydoge),
            'SODOGE_TWITTER_USERNAME' => Tools::getValue('SODOGE_TWITTER_USERNAME', $this->sodoge),
            'DOGECOIN_RESERVATION_DAYS' => Tools::getValue('DOGECOIN_RESERVATION_DAYS', $this->reservation_days),
            'EASY_DOGECOIN_CUSTOM_TEXT' => $custom_text,
            self::FLAG_DISPLAY_PAYMENT_INVITE => Tools::getValue(
                self::FLAG_DISPLAY_PAYMENT_INVITE,
                Configuration::get(self::FLAG_DISPLAY_PAYMENT_INVITE)
            ),
        ];
    }

    public function getTemplateVarInfos()
    {
        $cart = $this->context->cart;
        $total = sprintf(
            $this->trans('%1$s (tax incl.)', [], 'Modules.Dogecoin.Shop'),
            $this->context->getCurrentLocale()->formatPrice($cart->getOrderTotal(true, Cart::BOTH), $this->context->currency->iso_code)
        );

        $mydoge = $this->mydoge;
        if (!$mydoge) {
            $mydoge = '___________';
        }

        $dogecoinaddress = $this->dogecoinaddress;
        if (!$dogecoinaddress) {
            $dogecoinaddress = '___________';
        }

        $sodoge = $this->sodoge;
        if (!$sodoge) {
            $sodoge = '___________';
        }

        $dogecoinReservationDays = $this->reservation_days;
        if (false === $dogecoinReservationDays) {
            $dogecoinReservationDays = 7;
        }

        $dogecoinCustomText = Tools::nl2br(Configuration::get('EASY_DOGECOIN_CUSTOM_TEXT', $this->context->language->id));
        if (empty($dogecoinCustomText)) {
            $dogecoinCustomText = '';
        }

        return [
            'thispath' => $this->_path,
            'total' => '&ETH;'.$this->convert_to_crypto($cart->getOrderTotal(true, Cart::BOTH),$this->context->currency->iso_code),
            'dogecoinaddress' => $dogecoinaddress,
            'mydoge' => $mydoge,
            'sodoge' => $sodoge,
            'dogecoinReservationDays' => (int) $dogecoinReservationDays,
            'dogecoinCustomText' => $dogecoinCustomText,
        ];
    }

    public function convert_to_crypto($value, $from='usd') {
      $response = file_get_contents("https://api.coingecko.com/api/v3/coins/markets?vs_currency=".strtolower(($from))."&ids=dogecoin&per_page=1&page=1&sparkline=false");
      $price = json_decode($response);
      $response = $value / $price[0]->current_price;
      $response = number_format($response, 2, '.', '');

       if ($response > 0)
        return trim($response);

      return 0;

    }
}
