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
	return;	// spoofing or user hits refresh

$app->setUserState('com_cs_payments.payment.id',null);

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
