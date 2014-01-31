<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('radio');

/**
 * Form Field to load a list of content authors
 *
 * @package     Joomla.Libraries
 * @subpackage  Form
 * @since       3.2
 */
class JFormFieldDonationamounts extends JFormFieldRadio
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  3.2
	 */
	public $type = 'donationamounts';
	
	/**
	 * Method to get the options to populate list
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.2
	 */
	
	protected function getOptions()
	{
		// define default amounts and get component params if set

		$amount_default = "100";	// checked by default
		$amounts_default = "10,25,50,100,250,500";
		$amounts_param = JComponentHelper::getParams('com_cs_payments')->get('donation_amounts');
		$amounts = array_map('trim',explode(",",empty($amounts_param)?$amounts_default:$amounts_param));

		// Set the field default value.
		$this->value = "-1";	// this sets the other radio (only used if only other)
		$amount_param = JComponentHelper::getParams('com_cs_payments')->get('donation_amount_default');
		// set the checked radio if specified and it's one of the amounts
		if ( count( $amounts ) )
		{
			if ( in_array($amount_param,$amounts))
				$this->value = $amount_param;
			else 
				if ( in_array($amount_default,$amounts) )
					$this->value = $amount_default;
				else 
					// else select first amount
					$this->value = $amounts[0];
		}

		$options = array();

		foreach ($amounts as $amount)
		{
		//	$value = ($amount == $amount_default) ? array('attr' => 'checked', 'option.attr'=>'checked') : "value";
//$value="value";
			// Create a new option object based on the <option /> element.
			$tmp = JHtml::_(
					'select.option', (string) $amount . $checked, '$'.$amount, 'value', 'text', false );
		
			// Add the option object to the result set.
			$options[] = $tmp;
		}

		//<option value="-1">Other</option>
		$options[] = JHtml::_(
					'select.option', "-1", "Other", 'value', 'text',
					false
			);
		reset($options);
		
		return $options;
	}
}
