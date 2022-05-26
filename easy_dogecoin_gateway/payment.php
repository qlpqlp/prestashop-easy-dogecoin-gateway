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

/* SSL Management */
$useSSL = true;

require '../../config/config.inc.php';
Tools::displayFileAsDeprecated();

// init front controller in order to use Tools::redirect
$controller = new FrontController();
$controller->init();

Tools::redirect(Context::getContext()->link->getModuleLink('easy_dogecoin_gateway', 'payment'));
