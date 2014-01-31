<?php
/**
 * @version     1.0.0
 * @package     com_cs_payments
 * @copyright   Copyright (C) Creative Spirits 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ted Lowe <lists@creativespirits.org> - http://www.creativespirits.org
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Cs_payments helper.
 */
class Cs_paymentsHelper
{
	/**
	 * Configure the Linkbar.
	 */
	public static function addSubmenu($vName = '')
	{
		JHtmlSidebar::addEntry(
		JText::_('COM_CS_PAYMENTS_TITLE_DONATION_FUNDS'),
		'index.php?option=com_cs_payments&view=donationfunds',
		$vName == 'donationfunds'
				);
		JHtmlSidebar::addEntry(
		JText::_('COM_CS_PAYMENTS_TITLE_MEMBERSHIP_TYPES'),
		'index.php?option=com_cs_payments&view=membershiptypes',
		$vName == 'membershiptypes'
				);
		JHtmlSidebar::addEntry(
		JText::_('COM_CS_PAYMENTS_TITLE_PAYMENTS'),
		'index.php?option=com_cs_payments&view=payments',
		$vName == 'payments'
				);
		JHtmlSidebar::addEntry(
		JText::_('COM_CS_PAYMENTS_TITLE_SOURCES'),
		'index.php?option=com_cs_payments&view=sources',
		$vName == 'sources'
				);
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return	JObject
	 * @since	1.6
	 */
	public static function getActions()
	{
		$user	= JFactory::getUser();
		$result	= new JObject;

		$assetName = 'com_cs_payments';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action) {
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}
}
