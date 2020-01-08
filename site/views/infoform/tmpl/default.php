<?php
/*
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

$reason = Cs_paymentsHelper::getReasonOrElse();
$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
$form = $theModel->getForm();
$formhtml = Cs_paymentsHelper::renderForm($form,"form-info","confirminfo");
$formendhtml = Cs_paymentsHelper::renderFormEnd();

echo<<<EOT
	$formhtml

	<button type="submit" class="btn btn-primary">
			<span>Continue &gt;&gt;</span>
	</button>

	$formendhtml

<script>
jQuery("#form-info").validate();

jQuery("#jform_birthdate").datepicker({
	changeMonth: true,
	changeYear: true,
	constrainInputType: true,
	dateFormat: 'yy-mm-dd',
	maxDate: '0',
	prevText: '',
	nextText: '',
	yearRange: '-100:+0',
});
		
jQuery( document ).ready(function() {
	if ( jQuery("input[name='jform\[amount\]']:checked").val() != -1 )
		jQuery("#cg-jform_otheramount").hide();
});
jQuery("input[name='jform\[amount\]'][value=-1]").change(function(){
	jQuery("#cg-jform_otheramount").show();
	jQuery("#jform_otheramount").focus();
});
jQuery("input[name='jform\[amount\]'][value!=-1]").change(function(){
	jQuery("#cg-jform_otheramount").hide();
	jQuery("#jform_otheramount").val('');
});
</script>
EOT;
