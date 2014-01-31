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
class JFormFieldCreditcardexpirationyears extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  3.2
	 */
	public $type = 'creditcardexpirationyears';
	
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

		$nyears = 20;
		$cur_year = (int) date("Y");
		$options = array();
		for($i=0;$i<$nyears;$i++)
			// generate HTML <option>
			$options[] = JHtml::_('select.option', $cur_year+$i, $cur_year+$i);
				
		return array_merge( parent::getOptions(), $options );
	}
}
