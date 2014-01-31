<?php
/**
 * @version     1.0.0
 * @package     com_cs_payments
 * @copyright   Copyright (C) Creative Spirits 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ted Lowe <lists@creativespirits.org> - http://www.creativespirits.org
 */
// no direct access
defined('_JEXEC') or die;

require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

$app = JFactory::getApplication();
$data = $app->getUserState('com_cs_payments.payment.data', array());

if (empty($data))	// todos: decide how to handle
{
	echo('<br />no data paypalform');
}

//var_dump($data);

$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');

$form = $theModel->getForm();
$formhtml = Cs_paymentsHelper::renderForm($form,"form-pay","gotopaypal");
$formendhtml = Cs_paymentsHelper::renderFormEnd();

echo<<<EOT
	$formhtml

	<br />
	<p>Please make your payment at PayPal either via a credit card or PayPal account.</p>
	<br />	

	<button id="submitid" type="submit" class="btn btn-primary">
			<span>Make Payment at PayPal</span>
	</button>
			
	$formendhtml
EOT;
