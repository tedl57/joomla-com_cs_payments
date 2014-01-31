<?php
/**
 * @version     1.0.0
 * @package     com_cs_payments
 * @copyright   Copyright (C) Creative Spirits 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ted Lowe <lists@creativespirits.org> - http://www.creativespirits.org
 */

defined('_JEXEC') or die;

abstract class Cs_paymentsHelper
{
	public static function getReasonOrElse()
	{
		$app = JFactory::getApplication();
		$reason = $app->input->get('reason');
		switch($reason)
		{
		case 'join':
		case 'renew':
		case 'donate':
			break;
		default:
			jexit('Exiting for no reason');
		}
		return $reason;
	}

	public static function renderFormEnd()
	{
		$ret = "</form>";
		$footer_msg = JComponentHelper::getParams('com_cs_payments')->get('footer_msg');
		
		if (!empty($footer_msg))
			$ret .= "<span id='footer_msg' class='cs_footer_msg'>$footer_msg</span>";
		
		$ret .= "</div>";
		
		return $ret;
	}
	public static function renderForm( $form, $formid, $task )
	{
		require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

		$app = JFactory::getApplication();//todos: still needed?
		$reason = Cs_paymentsHelper::getReasonOrElse();

		$org = JComponentHelper::getParams('com_cs_payments')->get('org_name');
		$hdr_msg = JComponentHelper::getParams('com_cs_payments')->get('header_msg');
		
		// params to show/hide certain fields
		$donation_fund_required = (int) JComponentHelper::getParams('com_cs_payments')->get('donation_fund_required');
		$source_required = (int) JComponentHelper::getParams('com_cs_payments')->get('source_required');
		$gender_required = (int) JComponentHelper::getParams('com_cs_payments')->get('gender_required');
		$birthdate_required = (int) JComponentHelper::getParams('com_cs_payments')->get('birthdate_required');
		$language_required = (int) JComponentHelper::getParams('com_cs_payments')->get('language_required');
		$newsletter_distribution_types = JComponentHelper::getParams('com_cs_payments')->get('newsletter_distribution_types');
		
		$action = JRoute::_("index.php?option=com_cs_payments&view=$task&reason=$reason&task=payments.$task");
		$heading = self::getReasonHeading($reason, $org);
		
		// style is to highlight error messages
		$ret = "<style type='text/css'>label.error { color: red; }</style>";
		
		$ret .= "<div class='well'>";
		$ret .= "<h2>$heading</h2>";
		
		if (!empty($hdr_msg))
			$ret .= "<span id='header_msg' class='cs_header_msg'>$hdr_msg</span>";
		
		$ret .= "<form id='$formid' action='$action' method='post' class='form-horizontal' enctype='multipart/form-data'>";
    	
		// include token to protect from spoofing - will be checked in controller upon submission
		$ret .= JHtml::_('form.token');
		
    	// fieldsets break up a longer form nicely and more clearly specify user workflow
    	$fieldSets = $form->getFieldsets();
    	
    	// fieldset labels in the xml file are fieldset legends in html
    	foreach ($fieldSets as $name => $fieldSet)
    	{
    		if ( $reason == "donate" && !$donation_fund_required && $fieldSet->name == 'payment_reason' )
    			continue;	// skip donationfund 

    		if ( $reason == "join" )
    		{
    			if ( !$source_required && $fieldSet->name == 'source' )
    				continue;	// skip how did you hear about us

    			if ( empty($newsletter_distribution_types) && $fieldSet->name == 'newsletter_distribution' )
    				continue;
    		}    		   
    		$label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_CS_PAYMENTS'.$name.'_FIELDSET_LABEL';
    		$ret .= "<fieldset>\n<legend>$label</legend>\n";
    	
    		// each field is wrapped within a control-group with label and input
    		foreach ($form->getFieldset($name) as $field)
    		{
    			if ( $reason == "join" )
    			{
 		   			if ( !$birthdate_required && $field->fieldname == 'birthdate' )
    					continue;
    			
 		   			if ( !$gender_required && $field->fieldname == 'gender' )
    					continue;

 		   			if ( !$language_required && $field->fieldname == 'lang_pref' )
    					continue;
    			}
 
    			if ( $field->type == 'echovalue')	// special field type for simple templating
    			{
    				$ret .= "<div class='echovalue'>%" . $field->value . "%</div>\n";
    			}
    			else 
    			{
    				if ( $field->type == 'hidden' )
    				{
    					$ret .= "<input type='hidden' name='" . $field->name . "' value='" . $field->value . "' />\n";
    				}
    				else
    				{
    					$ret .= "<div class='control-group' id='cg-" . $field->id . "'>\n";
    						$ret .= "<div class='control-label'>" . $field->label .	"</div>\n";
    						$ret .= "<div class='controls'>" . $field->input . "</div>\n";
    					$ret .= "</div>\n";
    				}
    			}
    		}
    		$ret .= "</fieldset>\n\n";
    	}
    	
    	return $ret;
	}
	protected static function getReasonHeading( $reason, $org )
	{
		$verb = ucwords($reason);
		$the = "the ";
		switch($reason)
		{
		case 'join':
			$result = "$verb $the$org";
			break;
		case 'renew':
			$result = "Membership ${verb}al";	// todo: doesn't use org name because it's too long and it wraps around and looks crappy, could use org_abbr
			break;
		case 'donate':
			$result = "$verb to $the$org";
			break;
		default:
			jexit('improper reason');
		}
		return $result;
	}
}

