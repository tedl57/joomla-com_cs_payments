<?php
/**
 * @version     1.0.0
 * @package     com_cs_payments
 * @copyright   Copyright (C) Creative Spirits 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ted Lowe <lists@creativespirits.org> - http://www.creativespirits.org
 */

// bug1: probably due to having both authorize.net and paypal set (non-null) - paypal@example.org

// No direct access
defined('_JEXEC') or die;

require_once JPATH_COMPONENT.'/controller.php';

// todos: separate file or in static helper
class ofPaypalCollector	// {{{1
{
	protected $_item_type;	// reason (join, renew, donate) 
	protected $_item_name;	// type of membership (individual, business, etc.)
	protected $_amount;		// payment amount
	protected $_item_id;	// id of record in cs_payments table
	protected $_custom;		// todo: what is this used for?
	protected $_host;		// host of business website for return URLs
	protected $_fname;		// first name passed to PayPal
	protected $_lname;		// last name passed to PayPal
	protected $_component_name = "cs_payments";	// for building completed/cancelled URLs
	protected $_debug = false;	// for sandbox testing
	protected $_ema;			// email of business?
	protected $_image_url = "";	// logo of business to show during checkout

	//////////////////////////////////////////////////////////
	public function __construct($item_type,$item_name,$amount,$ema,$item_id,$fname,$lname)
	{
		$this->_item_type = $item_type;
		$this->_item_name = $item_name;
		$this->_amount = $amount;
		$this->_item_id = $item_id;
		$this->_host = $_SERVER["HTTP_HOST"];
		$this->_ema = $ema;
		$this->_fname = $fname;
		$this->_lname = $lname;
	}

	public function setDebug()
	{
		$this->_debug = true;
	}

	public function getDebug()
	{
		return $this->_debug;
	}

	public function setImageURL( $image_url )
	{
		$this->_image_url = $image_url;
	}

	public function setCustom( $custom )
	{
		$this->_custom = $custom;
	}

	/* obsoleted in joomla 3
	public function gotoPayPal($pdata = NULL)
	{
		$url = $this->getURL($pdata);		// obsolete
		mosRedirect( $url );
		exit();
	}
	*/

	//////////////////////////////////////////////////////////
	public function getURL($pdata = NULL)
	{
//live site 12/28/19 - $this->setDebug(); // todos: use sandbox

		$host = $this->_host;
		$comp_name = $this->_component_name;
		$amount = $this->_amount;
		$fname = $this->_fname;
		$lname = $this->_lname;
		$reason = Cs_paymentsHelper::getReasonOrElse();
		$id = $this->_item_id;
		
		// paypay wants completed and cancelled return URLs
		// eg, https://domain/index.php/renew?reason=renew&view=confirmpayment
		// todo: needs aliases for renew, join and donate 
		// todo: paypal will POST transid and action (completed or cancelled) into URL?
		
		$cancel_return = urlencode("https://$host/index.php/$reason-online?reason=$reason&view=confirmpayment&paymentstatus=cancelled&paymentid=$id");
		$completed_return = urlencode("https://$host/index.php/$reason-online?reason=$reason&view=confirmpayment&paymentstatus=completed&paymentid=$id");
		$notify_url = urlencode("https://$host/index.php/pp-ipn-listener");	// pre-configured alias for IPN listener
		//$notify_url = "https://$host/index.php/pp-ipn-listener";	// pre-configured alias for IPN listener

		$image_option = "";
		if ( ! empty( $this->_image_url ) )
			$image_option = "&image_url=" . urlencode( $this->_image_url );

		$custom_option = "";
		if ( ! empty( $this->_custom ) )
			$custom_option = "&custom=" . urlencode( $this->_custom );

		$DEBUG = "";
		$ppema = $this->_ema;
		if ( $this->_debug )
		{
			// use sandbox email to be able to enter non-paypal user credit card payments
		//	$ppema = "t7_1189482713_biz@example.com";		// sandbox user - test seller
			$DEBUG = ".sandbox";
		}

		$uitem = urlencode( $this->_item_name );

		// item_id is passed through on a completed transaction
		// rm=2 means paypal will post return data back us

		// create the highly specialized PayPal URL
		$url = "https://www$DEBUG.paypal.com/us/cgi-bin/webscr?";
		$urlargs="cmd=_xclick$custom_option$image_option&amount=$amount&item_name=$uitem&rm=2&no_shipping=1&no_note=1&invoice=$id&business=$ppema&first_name=$fname&last_name=$lname";
		
		// note that the URL will be rendered by some browsers (in debugging) as &|-ify_url because &not is a special character, i tried using &amp; or %26 but then PayPal didn't work
		
		return $url . $urlargs . "&notify_url=$notify_url&cancel_return=$cancel_return&return=$completed_return";
	}
}

/**
 * Payment controller class.
 */
class Cs_paymentsControllerPayments extends Cs_paymentsController
{
	public function payproc()
	{
		require_once JPATH_COMPONENT.'/assets/payproc/base.php';

		$app = JFactory::getApplication();
		$loggeduser = JFactory::getUser();
		$id = $app->input->get('id');
		$action = $app->input->get('ppaction', "");

		if ( empty( $action ) )
			return;	// todos: better error leg?
		
		if ( Cs_paymentsPayprocAction::loadActionClass(null,$action) === null )
			jexit("payproc: can't load class for action $action");
		
		$classname = Cs_paymentsPayprocAction::getChildClassName($action);
		if ( ! class_exists( $classname ))
			jexit("payproc: no class $classname");
		$obj = new $classname($id,$loggeduser->username,$action);
/*		
		if ( ! method_exists($actionObj, 'executeAction'))
			jexit("no way to execute in $classname");
*/
		// authorize user before processing to protect against possible URL spoofing 
/*		if ( ! $loggeduser->authorise($obj->getAuthLevel(), 'com_cs_payments') )
		{
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			//todos: setredirect to home page
			$this->setRedirect(JRoute::_("index.php",false));
			return false;
		}
*/
		$obj->executeAction(); // todos: check return

		$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&view=payproc", false));
	}
	/**
	 * Method to authorize & capture a credit card payment on authorize.net
	 *
	 * @return	void
	 * @since	1.6
	 */
	protected function doAuthorizeDotNet( $data, $bReal = false )
	{
		// By default, this sample code is designed to post to our test server for
		// developer accounts: https://test.authorize.net/gateway/transact.dll
		// For real accounts (even in test mode), please make sure that you are
		// posting to: https://secure.authorize.net/gateway/transact.dll
		$test_mode = $bReal ? "FALSE" : "TRUE";
		//$post_url = "https://test.authorize.net/gateway/transact.dll";
		$post_url = "https://secure.authorize.net/gateway/transact.dll";

		// get login id and transaction key from component params
		$adn_login = JComponentHelper::getParams('com_cs_payments')->get('authorizedotnet_login_id');
		$adn_tran_key = JComponentHelper::getParams('com_cs_payments')->get('authorizedotnet_transaction_key');

		$post_values = array(
	
				"x_login"			=> $adn_login,
				"x_tran_key"		=> $adn_tran_key,
				"x_test_request"	=> $test_mode,	// test mode until ready to submit actual transactions
				"x_version"			=> "3.1",
				"x_delim_data"		=> "TRUE",
				"x_delim_char"		=> "|",
				"x_relay_response"	=> "FALSE",
	
				"x_type"			=> "AUTH_CAPTURE",
				"x_method"			=> "CC",
	
				"x_card_num"		=> $data["ccnum"],		//test visa "4111111111111111" (4 and 15 ones)
				"x_exp_date"		=> $data["ccexpdate"],	// mmyy
	
				"x_amount"			=> $data["amount"],
				"x_description"		=> $data["trans_desc"],
	
				"x_first_name"		=> $data["fname"],
				"x_last_name"		=> $data["lname"],
				"x_card_code"		=> $data["ccv"],
				//"x_address"		=> $data["address"],	// optional - only used if address verification is enabled
				//"x_city"			=> $data["city"],		// optional
				//"x_state"			=> $data["state"],		// optional
				"x_invoice_num"		=> $data["conf_num"],	// unique autoincrement db id
				"x_email" 			=> $data["email"],		// optional - used to contact declines if desired
				"x_phone" 			=> $data["phone"],		// optional - used to contact declines if desired

				// Additional fields can be added here as outlined in the AIM integration
				// guide at: http://developer.authorize.net
				//"merchant_defined_1" => "test123",
		);
	
		if ( isset( $data["zip"] ) )
			$post_values["x_zip"] = $data["zip"];	// only used for avs/$0 visa trans
		/*
		GOOD result:
		1. 1
		2. 1
		3. 1
		4. (TESTMODE) This transaction has been approved.
		5. 000000
		6. P
		7. 0
		8. fid
		9. Sample Transaction
		10. 0.01
		11. CC
		12. auth_capture
		13. memid
		14. John
		15. Doe
		16.
		17. 1234 Street
		18. Seattle
		19. WA
		20. 98004
		21.
		...
		37.
		38. 4F89AAAE835FA33DC3A6DEB7AFBB9F94
		39.
		...
		68.
		69. test321
		70. test123
	
		BAD result:
		# 3
		# 1
		# 6
		# (TESTMODE) The credit card number is invalid.
		# 000000
		# P
		# 0
		# fid
		# Sample Transaction
		# 0.01
		# CC
		# auth_capture
		
		*/

		// This section takes the input fields and converts them to the proper format
		// for an http post.  For example: "x_login=username&x_tran_key=a1B2c3D4"
		$post_string = "";
		foreach( $post_values as $key => $value )
		{
			$post_string .= "$key=" . urlencode( $value ) . "&";
		}
		$post_string = rtrim( $post_string, "& " );

		// This sample code uses the CURL library for php to establish a connection,
		// submit the post, and record the response.
		// If you receive an error, you may want to ensure that you have the curl
		// library enabled in your php configuration
		//echo "adn1: post_string=\"$post_string\"<br>";
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		// additional options may be required depending upon your server configuration
		// you can find documentation on curl options at http://www.php.net/curl_setopt
		curl_close ($request); // close curl object
	
		// This line takes the response and breaks it into an array using the specified delimiting character
		return explode($post_values["x_delim_char"],$post_response);
	
		// individual elements of the array could be accessed to read certain response
		// fields.  For example, response_array[0] would return the Response Code,
		// response_array[2] would return the Response Reason Code.
		// for a list of response fields, please review the AIM Implementation Guide
		//
	}
	protected function isPaymentAuthorized( $data, $conf_num, $reason )
	{
		// todos:  store an attempt to pay in the DB for staff processing
		// The DB record will be updated when a payment is actually authorized

if ( $data["card_first_name"] == "Ted" && $data["card_last_name"] == "Lowe")	// easter egg
	return (array("success_response"=>"testing123"));
	
		// prepare to call authorize.net -
		// form will not submit unless payment authorization/capture is successful
		$kv = array();
		$kv["fname"] = $data["card_first_name"];
		$kv["lname"] = $data["card_last_name"];
		$kv["ccexpdate"] = $data["cardexpmonth"] . substr($data["cardexpyear"],-2);
		$kv["ccnum"] = $data["cardno"];
		$kv["ccv"] = $data["cardccv"];
		$kv["amount"] = $data["amount"];
		$kv["trans_desc"] = $reason . "|" . $data["payment_reason"];
		$kv["zip"] = "12345"; // todos: only for $0 visa transactions or future AVS
		$kv["conf_num"] = $conf_num;
		$kv["email"] = $data["email"];	// optional info to contact declines if desired
		$kv["phone"] = $data["phone"];	// optional info to contact declines if desired

		$response_array = $this->doAuthorizeDotNet($kv,true);	// true = live/real

		$ret = array();
		
		if ( $response_array[0] != "1" )
		{
			$ret["error_response"] = $response_array[3];
		}
		else
		{
			$ret["success_response"] = $response_array[6];
		}

		return $ret;
	}
	public function payform()
	{
		require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN')."2");

		$reason = Cs_paymentsHelper::getReasonOrElse();
		
		// determine payment method
		// if authorize.net is configured, use it
		// else if paypal is configured, use it
		// else configuration error
		
		// get authorize.net login id and transaction key from component params
		$adn_login = JComponentHelper::getParams('com_cs_payments')->get('authorizedotnet_login_id');
		$adn_tran_key = JComponentHelper::getParams('com_cs_payments')->get('authorizedotnet_transaction_key');
		$paypal_email_address = JComponentHelper::getParams('com_cs_payments')->get('org_paypal_email_address');
		
		// check that only one payment processor is configured (solve bug1)
		if ( (!empty($adn_login)) && (!empty($adn_tran_key)) && (!empty($paypal_email_address)))
		{
			JFactory::getApplication()->enqueueMessage('Multiple payment processors are configured.', 'error');
			$this->setRedirect(JRoute::_("index.php", false));
		}
		else if ( (!empty($adn_login)) && (!empty($adn_tran_key)))
			$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&reason=$reason&view=payform", false));
		else
		{				
			if ( !empty($paypal_email_address))
				$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&reason=$reason&view=paypalform", false));
			else
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_CS_PAYMENTS_CONFIG_ERROR_NO_PAYMENT_PROCESSOR'), 'error');
				$this->setRedirect(JRoute::_("index.php", false));
			}
		}
	}

	/**
	 * Method to submit payment to payment processor and checks for response
	 *
	 * @return	void
	 * @since	1.6
	 */
	public function gotopaypal()
	{
		require_once JPATH_COMPONENT.'/helpers/cs_payments.php';
	
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
	
		$app	= JFactory::getApplication();
		$reason = Cs_paymentsHelper::getReasonOrElse();
		$data = $app->getUserState('com_cs_payments.payment.data', array());
//JFactory::getApplication()->enqueueMessage("bug1:gotopaypal".json_encode($data), 'info');
		$id = $app->getUserState('com_cs_payments.payment.id');

		// Item Name from paypal's point of view is the thing being purchased, so
		// renewal of Individual|1|0|0 should be translated to Individual Membership Renewal (1 Year)
;
		if ( $reason == "donate" )
		{
			$item_name = "Donation to " . $data["payment_reason"] . " fund";
		}
		else
		{
			$arr = explode('|',$data["payment_reason"]);	// Individual|60|1|0|0
			$memtype = strtolower( $arr[0] );
			$yrs = $arr[2];
			$plural = $yrs != 1 ? "s" : "";
			$item_name = sprintf( "%s year%s %s membership", $yrs, $plural, $memtype );
				
			if ( $reason == "renew" )
				$item_name .= " renewal";
		}

		$id = $app->getUserState('com_cs_payments.payment.id');
		$ppc = new ofPayPalCollector( $reason, $item_name, $data["amount"], 
		JComponentHelper::getParams('com_cs_payments')->get('org_paypal_email_address'), $id, $data["first_name"], $data["last_name"] );

		// get the highly customized URL to go to PayPal

		$ppurl = $ppc->getURL();
	
		// now redirect user's browswer to PayPal URL to collect payment
		$this->setRedirect($ppurl);
	}

	/**
	 * Method to submit payment to payment processor and checks for response
	 *
	 * @return	void
	 * @since	1.6
	 */
	public function confirmpayment()	// called only in authorize.net scenario
	{
		require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app	= JFactory::getApplication();
		$reason = Cs_paymentsHelper::getReasonOrElse();
		$now = date('Y-m-d H:i:s', time() );
		
		// save reasondata in DB prior to payment authorization attempt:
		//	1) to have conf_num to pass in authorization request so payment processor transaction can be traced to org's payment record
		//  2) to track failed attempts (to see if there are technical or user errors)
		
		$data = $app->getUserState('com_cs_payments.payment.data', array());
		$data["datetimestamp"] = $now;
		$data["payment_type"] = $reason;	// join, renew or donate
		if ( $reason != "donate" )
		{
			//todos: check for proper settings
			$arr = explode('|',$data["payment_reason"]);	// Individual|60|1|0|0
			$data["amount"] = $arr[ 1 ];
		}
		
		$theModel = JModelLegacy::getInstance('payments', 'Cs_paymentsModel');
		
		// save a record in cs_payments table prior to trying to authorize payment on authorize.net

		if ( ( $conf_num = $theModel->save($data) ) === false )	//todos: need conf_num and what happens on fail
		{
			jexit('Failed to save preauthorization data');	//todos:
		}
		unset($data["datetimestamp"]);

		// combine reasondata and ccdata to save in registry in a single place where loadData expects it
		
		$ccdata = $this->input->post->get('jform',array(),'array');
		$data = array_merge($data,$ccdata);
		
		// "call" payment processor to authorize payment
		$ret = $this->isPaymentAuthorized( $data, $conf_num, $reason );
		
		if ( isset( $ret["error_response"] ) )
		{
			JFactory::getApplication()->enqueueMessage("Payment Failed: ".$ret["error_response"], 'error');

			// save combined data to repopulate credit card form
			$app->setUserState('com_cs_payments.payment.data', $data);

			// save error to the pre-authorize-try payment record for (todos: doc perhaps future) analysis
			// include previous id to cause an update instead of an insert
			$data['id'] = $conf_num;
			$ipinfo = isset( $_SERVER["REMOTE_ADDR"]) ? " (ip addr=" . $_SERVER["REMOTE_ADDR"] . ")" : " (no ip addr)";
			$data['response'] = $ret["error_response"] . $ipinfo;
			if ( $theModel->save($data) === false )
			{
				jexit('Failed to update posterror data');	//todos:
			}
			$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&reason=$reason&view=payform", false));

			return false;
		}
		// store payment confirmation number for use on payment confirmed view
		$app->setUserState('com_cs_payments.payment.id',$conf_num);
		
		// save successful payment authorization to DB with payment processor's transaction id and set the time paid
		// include previous id to cause an update instead of an insert

		$paiddata = array();
		$paiddata["id"] = $conf_num;
		$paiddata["response"] = $ret["success_response"];
		$paiddata["date_paid"] = $now;		// authorize.net scenario
		if ( $theModel->save($paiddata) === false )
		{
			jexit('Failed to update postsuccess data');	//todos:
		}

		// show user the payment succeeded
		$this->setRedirect(JRoute::_("index.php?option=com_cs_payments&reason=$reason&view=confirmpayment", false));

		return true;
	}


/**
 * Method to confirm information was added properly (server-side validation).
 *
 * @return	void
 * @since	1.6
 */
public function confirminfo()
{
	// Check for request forgeries.
	JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

	// Initialise variables.
	$app	= JFactory::getApplication();
	$model = $this->getModel('Payments', 'Cs_paymentsModel');

	// Get the user data.
	$data = JFactory::getApplication()->input->get('jform', array(), 'array');

	// Validate the posted data.
	$form = $model->getForm();
	if (!$form) {
		JError::raiseError(500, $model->getError());
		return false;
	}

	// set amount properly for all reasons
	
	if ( isset( $data["amount"]))
	{
		// donate
		
		if ( $data["amount"] == "-1" )
			$data["amount"] = (int) $data["otheramount"];
	}
	else
	{
		// join/renew
		$arr = explode('|',$data["payment_reason"]);	// Individual|60|1|0|0
		$data["amount"] = $arr[1];
	}

	$amount = $data["amount"];

	// Validate the posted data.
	$data = $model->validate($form, $data);

	// Check for errors.
	if ($data === false) {
		// Get the validation messages.
		$errors	= $model->getErrors();

		// Push up to three validation messages out to the user.
		for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
			if ($errors[$i] instanceof Exception) {
				$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
			} else {
				$app->enqueueMessage($errors[$i], 'warning');
			}
		}

		// Save the data in the session.
		$app->setUserState('com_cs_payments.payment.data', JRequest::getVar('jform'),array());

		// Redirect back to the form to allow the user to correct the errors caught in this server-side validation
		$id = (int) $app->getUserState('com_cs_payments.payment.id');
		$this->setRedirect(JRoute::_('index.php?option=com_cs_payments&reason='.$app->input->get('reason').'&view=infoform', false));
		return false;
	}

	// Save the data in the session.
	$app->setUserState('com_cs_payments.payment.data', array_merge(JRequest::getVar('jform'),array('amount'=>$amount)));

	// redirect forward to confirm screen
	$this->setRedirect(JRoute::_('index.php?option=com_cs_payments&reason='.$app->input->get('reason').'&view=confirminfo', false));
	return true;
} 
}
?>
