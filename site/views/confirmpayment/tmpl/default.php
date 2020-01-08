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
$reason = Cs_paymentsHelper::getReasonOrElse();
$conf_num = $app->getUserState('com_cs_payments.payment.id',null);

if ( $conf_num === null )
{
	return;	// spoofing or user hits refresh
}

// get a local copy of all the form data before clearing it out
$data = $app->getUserState('com_cs_payments.payment.data', array());
//JFactory::getApplication()->enqueueMessage("bug1:confirmpaymentview".json_encode($data), 'info');
// clear all form data
$app->setUserState('com_cs_payments.payment.id',null);
$app->setUserState('com_cs_payments.payment.data',null);

// this view is used to confirm synchronous payments (like with authorize.net) and asynchronous payments or attempted ones (like PayPal)
// PayPal will return paymentstatus=completed or paymentstatus=cancelled, and paymentid=<payment id>
if ( isset( $_GET["paymentstatus"] ) && isset( $_GET["paymentid" ] ) )
{
	if ( $_GET["paymentid"] != $conf_num )
		return;	// spoofing or something else wrong
		
	if ( $_GET["paymentstatus"] == "cancelled" ) 
	{
		$app->enqueueMessage("Your PayPal payment attempt was cancelled.  Please try again or contact us for assistance.", "warning");
		
		// mark the payment cancelled unless it already has been
		
		$db = JFactory::getDbo();
		$db->setQuery("SELECT id,date_cancelled FROM #__cs_payments WHERE id=$conf_num");
		$items = $db->loadObjectList('id');
		$nitems = count($items);
		
		if ( $nitems != 1 )
			return; // todo: something went wrong
		
		if ( $items[0]->date_cancelled != NULL )
			return;	// nothing more to do, already marked completed
		
		// update date cancelled in the payments's record in the cs_payments table
		
		$update_object = new stdClass();
		$update_object->id = $conf_num;
		$update_object->date_cancelled = date('Y-m-d H:i:s');
		$result = $db->updateObject('#__cs_payments', $update_object, 'id');
		
		return;
	}

	if ( $_GET["paymentstatus"] != "completed" )
			return;	// only handle two status: completed and cancelled
}

// if the payment has already been marked completed, do nothing more (don't duplicate email/confirmation message)

$db = JFactory::getDbo();
$db->setQuery("SELECT id,date_completed FROM #__cs_payments WHERE id=$conf_num");
$items = $db->loadObjectList('id');
$nitems = count($items);

if ( $nitems != 1 )
	return; // todo: something went wrong

if ( $items[0]->date_completed != NULL )
	return;	// nothing more to do, already marked completed

// update date completed in the payments's record in the cs_payments table

$update_object = new stdClass();
$update_object->id = $conf_num;
$update_object->date_completed = $now = date('Y-m-d H:i:s');
$result = $db->updateObject('#__cs_payments', $update_object, 'id');

// send confirmation via email
Cs_paymentsHelper::onCompleted( $data, $conf_num, $now );

$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
$form = $theModel->getForm();
$formhtml = Cs_paymentsHelper::renderForm($form,"form-confirmpayment","confirmpayment");

echo<<<EOT
	$formhtml
<br />
			Your payment confirmation number is $conf_num.
<br />
			We have emailed you a confirmation and will be in contact soon!
	</form>
EOT;
