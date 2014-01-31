<?php
/* trlowe todos:
DONE implement actions that have spaces in them, test with new fveaa add_member
	- filename: add_member.php
		- ppaction: add_member
		- class: Cs_paymentsPayprocActionAdd_member
		- action link: Add Member
xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
3RD!!!    implement fveaa add member
2ND!!!!!! implement ts download

move getPayInfo and getPersonInfo into static helper classes somewhere
move ofPaypalCollector into a separate file (1 class/file)
OO-ize isPaymentAuthorized/doAuthorizeDotNet packaging
xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

------
todos: disable 'Confirm Payment' button after clicking it 
	this doesn't work because the button stays disabled after a validation failure
	   
jQuery(document).ready(function () {
    jQuery("#form-pay").submit(function () {
        jQuery("#submitid").attr("disabled", true);
        return true;
    });
});
see: https://www.google.com/search?q=jquery+disable+submit+button+after+successful+validation&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:en-US:official&client=firefox-a
------

todos: add param for how many past items to show on payment processor view

HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE 
implement payment processor interfaces
	- DONE authorize.net (easier - direct call and check return)
	- PARTIALLY DONE paypal - need to send user to paypal (to either pay via credit card or by paypal acct)
		- BIG: need to implement a controller task that acts as paypal IPN listener
		- create/modify confirmpayment view to handle returning from paypal
			- successful payments
			- handle cancelled payments
		- why does paypal url contain notify url if also specified in merchant profile option???
		- ENORMOUS!!! can paypal be used for free like authorize.net (stay on site and make REST call to create a credit card payment)
			- this would greately improve user experience on fveaa/other non-authorize.net sites
			- wouldn't need IPN
			- this would be a third payment configuration
		- why didn't return to bla cancel link not work on paypal
		
HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE HUGE 

todos: BIG: send confirmation email upon successful payment

todos: make component one-click updatable

BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIGBIG BIG BIG BIG BIG BIG
MOSTLY DONE implement site-side staff only payment processor controller/view
still to do:
- user authorization:
	- process: logged in user with state editting
	- download: 
	- create membership record: 
	- or even view data at all (privacy except for membership team)

- BIG: ASK BEVERLY WHAT SHE WANTS IN THE NEW VERSION NOW!
- how to handle different actions in payment processor
	- fveaa needs 'create membership record'
	- tsa needs download
		- how to be able to customize format of downloaded csv?
	- create simple action 'plugin' system ??? 
BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIG BIGBIG BIG BIG BIG BIG BIG

todos: carefully decide how to customize/internationalize
	1) changing xml form files
	2) changing ini language files
	3) changing component parameters
	4) changing code (worst case)
	5) either paypal OR authorize.net payments are submitted (not both)
			todos: doc: if authorize.net is configured via backend, it will be used even if paypal is also configured

todos: doc: known limitations
	- only supports whole amounts of donations and dues
	- only supports US postal addresses
	- only supports USD currency
	- only supports english (for now)

todos: doc:
	- there is a administrator interface for listing/editting/deleting payments, but BE CAREFUL, only for inspection and testing!
	- uses jquery (joomla inbuilt) and some jquery plugins (added in assets/js)
		- date picker (join)
		- validate (all user side forms)
		- ???

todos: handle uses of 'us' and "we'll"
	1) How did you hear about us? (s/b How did you hear about %the%%org% but not possible from xml to php w/o templating facility
	2) Donor fund select: 'Let us decide the best use'
	3) payment successful confirmation "we'll be in touch" 

todos: cleanup:
	- remove created_by fields etc.
	- remove all commented out code
	- remove all todos:
	- remove all commented out debug (ie, var_dump, //echo)
	- make sure all uneeded/wanted auth checks are out of user form views
	- document the code more
	- remove unnecessary hidden fields on some forms

todos: make sure all controllers do either ACL checks or check form tokens

todos: add optional More Information about source (for fveaa join)

todos: component parameter to select newsletter distribution type (Postal, Electronic(s/b Email) or None)
	- DONE add component parameter
	- DONE show on user form
	- need to store user input in the DB (for future use in other comps - com_cs_memdb

todos: make 'payment_succcessful_message' configurable
	- join: Welcome to the xx.
	- renew: Thanks for your ongoing support!
	- donation: Thanks for your support!

todos: feature: add reason specific header/foot messages

BUGS BUGS BUGS BUGS BUGS BUGS BUGS
todo: bug: (very low priority) payments.png icon's don't show up on backend titles
todo: bug: (worked around by having a default specified) if no default radio selected, validation message looks bad between label and first radio
todos: bug: no footer displayed
BUGS BUGS BUGS BUGS BUGS BUGS BUGS

todos: component css file? (optionally used, controlled by param?)
- for better looking legends
legend {
	display: block;
	width: 100%;
	padding: 0;
	margin-top: 10px;
	margin-bottom: 4px;
	font-size: 19.5px;
	line-height: 32px;
	color: #333;
	border: 0;
	border-top: 2px solid #a5a5a5;
	border-bottom: 1px solid #c5c5c5;
}
*/

/**
 * @version     1.0.0
 * @package     com_cs_payments
 * @copyright   Copyright (C) Creative Spirits 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ted Lowe <lists@creativespirits.org> - http://www.creativespirits.org
 */
// no direct access
defined('_JEXEC') or die;
//todos: turnback on tooltips and keepalive (what is keepalive?)

require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

$app = JFactory::getApplication();
$data = $app->getUserState('com_cs_payments.payment.data', array());
// it's fine to not have data here
//if (empty($data))
//{
	//echo('<br />no data infoform');
//}
//else
//var_dump($data);

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
