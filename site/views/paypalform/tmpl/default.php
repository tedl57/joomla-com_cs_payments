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
//todo: debugging - JFactory::getApplication()->enqueueMessage("data:".str_replace(",",",<br />",json_encode($data)),'success');

if (empty($data))	// todos: decide how to handle
{
	echo('<br />no data paypalform');
}

// save reasondata in DB prior to payment authorization attempt:
//	1) to have conf_num to pass in authorization request so payment processor transaction can be traced to org's payment record
//  2) to track failed attempts (to see if there are technical or user errors)
		
$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');

$reason = Cs_paymentsHelper::getReasonOrElse();
$data["payment_type"] = $reason;	// join, renew or donate

if ( $reason != "donate" )
{
	//todos: check for proper settings
	$arr = explode('|',$data["payment_reason"]);	// Individual|60|1|0|0
	$data["amount"] = $arr[ 1 ];
}

// check if a payment record has already been added to the cs_payments table

$id = JFactory::getApplication()->getUserState('com_cs_payments.payment.id',null);
if ( $id === null || $id == 0)
{
	$data["datetimestamp"] = date('Y-m-d H:i:s', time() );
	// save a record in the cs_payments table now to mark paid if/when the PayPal payment is made
	if ( ( $id = $theModel->save($data) ) === false )	// paypal - todos: need conf_num and what happens on fail
	{
		jexit('Failed to save data prior to going to PayPal');	//todos:
	}
	unset($data["datetimestamp"]);
	
	$app-> setUserState('com_cs_payments.payment.id', $id);	// paypal - save transid (payment id) for use in gotopaypal
}

$app->setUserState('com_cs_payments.payment.data', $data);  // save payment_type

//todo: echo "id=$id<br />";

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
