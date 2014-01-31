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
class JFormFieldNewsletterdistributiontypes extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  3.2
	 */
	public $type = 'newsletterdistributiontypes';

	/**
	 * Method to get the options to populate list
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.2
	 */
	
	protected function getOptions()
	{
		$newsletter_distribution_types = JComponentHelper::getParams('com_cs_payments')->get('newsletter_distribution_types');
		$types = array_map('trim',explode(",", $newsletter_distribution_types));

		$options = array();

		foreach( $types as $type )		
			// generate HTML <option>
			$options[] = JHtml::_('select.option', $type, $type );

		return $options; 
	}
}
