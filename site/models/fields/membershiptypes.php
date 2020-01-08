<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

/**
 * Form Field to load a list of content authors
 *
 * @package     Joomla.Libraries
 * @subpackage  Form
 * @since       3.2
 */
class JFormFieldMembershiptypes extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  3.2
	 */
	public $type = 'membershiptypes';
	
	/**
	 * Method to get the options to populate the membership types list
	 *
	 * @param   array $items    DB rows from #__cs_membership_types table
	 * @return  array  The field option objects.

Joomla! 1.0/1.5 DB schema for membership_types table:
"id","memtype","memtype_string","dues","dues2","dues3","show_order","lifetime_membership"

Joomla! 3.x schema:
"id","typ","dues","show_order","lifetime_membership","age_max","age_min"

Schema 1.x to 3.x change notes:

1) a single field for the membership type
2) multiple dues fields are now a comma separated list of dues for 1 year, 2 years, etc.
3) added age_max and age_min to handle student and senior types, allowing org to configure cutoff ages

Example data in the #__cs_membership_types table:

"id","typ","dues","show_order","lifetime_membership","age_max","age_min"
"1","Single Person","60,108,153","1","0","0","0"
"2","Student","30","2","0","0","24","0"
"3","Senior","30","3","0","0","75"
"4","Family","96,180,267","6","0","0","0"
"5","Single Person Lifetime","1500","5","1","0","0"
"6","Prisoner","24","4","0","0","0"

This method generates the following HTML for the example data above:

	<option value="Single Person|60|1|0|0">Single Person - 1 Year - $60</option>
	<option value="Single Person|108|2|0|0">Single Person - 2 Years (Save 10%) - $108</option>
	<option value="Single Person|153|3|0|0">Single Person - 3 Years (Save 15%) - $153</option>
	<option value="Student|30|1|0|24">Student (24 or younger) - 1 Year - $30</option>
	<option value="Senior|30|1|75|0">Senior (75 or older) - 1 Year - $30</option>
	<option value="Prisoner|24|1|0|0">Prisoner - 1 Year - $24</option>
	<option value="Single Person Lifetime|1500|0|0|0">Single Person Lifetime - $1500</option>
	<option value="Family|96|1|0|0">Family - 1 Year - $96</option>
	<option value="Family|180|2|0|0">Family - 2 Years (Save 6%) - $180</option>
	<option value="Family|267|3|0|0">Family - 3 Years (Save 7%) - $267</option>
	
	Note the <option> value is an encoded version of the data to be used for special validation checks when the form is posted.	 
	
	Single Person - 1 Year - $60
	Single Person - 2 Years (Save 10%) - $108 
	Single Person - 3 Years (Save 15%) - $153
	Student (24 or younger) - 1 Year - $30
	Senior (75 or older) - 1 Year - $30
	Prisoner - 1 Year - $24
	Single Person Lifetime - $1500
	Family - 1 Year - $96
	Family - 2 Years (Save 6%) - $180
	Family - 3 Years (Save 7%) - $267
 */
	protected function getOptionsFromDBItems(&$items)
	{
		$options = array();

		foreach ($items as $item)
		{
			// each db row can create multiple options if more than 1 year of dues is specified
			$dues = explode(',',$item->dues);
			foreach( $dues as $k => $v )
			{
				$out = array();
				
				// handle min or max age types
				$min_max = "";
				if ( $item->age_max )
					$min_max = " (" . $item->age_max . " or younger)";
				else if ( $item->age_min )
						$min_max = " (" . $item->age_min . " or older)";
						
				$out[] = $item->typ . $min_max;
				
				// calculate savings in multiple year dues
				if ( ! $item->lifetime_membership )
				{
					$savings = "";
					if ( $k )
					{
						$yrs = (int) $k + 1;
						$basedues = (int) $dues[0];
						$pc = (int) (( ( ($basedues*$yrs) - (int)$v ) * 100 ) / ($basedues*$yrs));
						$savings = " (Save $pc%)";
					}
					$out[] = (int) $k + 1 . " Year" . ($k ? "s" : "") . $savings;
				}
				
				// dues amount in USD
				$out[] = '$' . $v;
				
				// encode option value to pass along in $_POST
				// typ|dues|yrs|age_min|age_max
				$yrs = $item->lifetime_membership ? 0 : ((int) $k + 1);
				$fld =  $item->typ . "|$v|$yrs|" . $item->age_min . "|" . $item->age_max;
				
				// generate HTML <option>
				$options[] = JHtml::_('select.option', $fld, implode(' - ', $out ));
			}
		}
		
		return $options;
	}

	/**
	 * Method to get the options to populate list
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.2
	 */
	
	protected function getOptions()
	{
		$options = array();

		$db = JFactory::getDbo();

		// Construct the query
		$query = $db->getQuery(true)
			->select('typ, dues, show_order, lifetime_membership, age_max, age_min')
			->from('#__cs_members_types')
			->where('show_order!=0')
			->order('show_order');

		// Setup the query
		$db->setQuery($query);

		return array_merge( parent::getOptions(), $this->getOptionsFromDBItems(  $db->loadObjectList() ) );
	}
}
