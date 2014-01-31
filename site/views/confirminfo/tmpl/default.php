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
//echo "reason=$reason";

$data = $app->getUserState('com_cs_payments.payment.data', array());
if (empty($data))	// todos: decide how to handle no data here
{
	echo('<br>no data confirminfo');
}
//var_dump($data);

$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
$form = $theModel->getForm();
$formhtml = Cs_paymentsHelper::renderForm($form,"form-confirminfo","payform");

$personhdr = ( $reason == 'donate' ) ? "Donor" : "Member";
$formhtml = str_replace('%person%',$personhdr, $formhtml);
$formhtml = str_replace('%person_info%',getPersonInfo($reason,$data), $formhtml);

$reasonhdr = ( $reason == 'donate' ) ? "Donation" : "Membership";
$formhtml = str_replace('%reason%',$reasonhdr, $formhtml);
$formhtml = str_replace('%reason_info%',getReasonInfo($reason,$data), $formhtml);

$msg = '<p>Please confirm the above information and continue, or go back to make corrections.</p>';
$formhtml = str_replace('%confirm_msg%',$msg, $formhtml);

$formendhtml = Cs_paymentsHelper::renderFormEnd();

// previous button redirect
$prevurl = JRoute::_("index.php?option=com_cs_payments&view=infoform&reason=$reason");

echo<<<EOT

	$formhtml	
	
	<button type="button" id="btn_previous" class="btn">
		<span>&lt;&lt; Previous</span>
	</button>

	<button type="submit" class="btn btn-primary">
		<span>Continue &gt;&gt;</span>
	</button>

<!-- todos: needed?   	<input type="hidden" name="option" value="com_cs_payments" /> -->
	$formendhtml

<script>
jQuery('#btn_previous').on('click', function (e) {
	window.location.replace('$prevurl');
});
</script>
EOT;
function getReasonInfo($reason,$data)
{
	if ( $reason == "donate" )
	{
		if ( isset( $data['payment_reason'] ) && ! empty( $data['payment_reason']) )
		{
			$ret = '<strong>Fund:</strong> ' . $data['payment_reason'];
			$ret .= '<br />';
		}
		$amount = (int) (((isset($data['otheramount'])&&!empty($data['otheramount'])) ? $data['otheramount'] : $data['amount']));
		$ret .= "<strong>Amount:</strong> \$$amount";
	}
	else
	{
		$da = explode('|',$data['payment_reason']);
		$ret = '<strong>Type:</strong> ' . $da[0];
		$ret .= '<br />';
		$yrs = (int) $da[2];
		if ( $yrs )
		{
			$yrstr = 'Year' . (($yrs == 1 ) ? "" : "s");
			$ret .= "<strong>Length:</strong> $yrs $yrstr";
			$ret .= '<br />';
		}
		$ret .= '<strong>Dues:</strong> $' . (int) $da[1];
	}

	$ret .= '<br />';
	$ret .= '<br />';

	return $ret;
}
function getPersonInfo($reason,$data)
{
	$ret = $data['first_name'] . " " . $data['last_name'];
	$ret .= '<br />';
	if ( isset($data['address']) && !empty( $data['address']))
	{
		$ret .= $data['address'];
		$ret .= '<br />';
		$ret .= $data['city'] . ", " . $data['usastate'] . " " . $data['zipcode'];
		$ret .= '<br />';
	}
	$ret .= $data['phone'] . " (" . $data['phone_type'] . ")";
	$ret .= '<br />';
	$ret .= $data['email'];
	$ret .= '<br />';
	$ret .= '<br />';

	return $ret;
}

