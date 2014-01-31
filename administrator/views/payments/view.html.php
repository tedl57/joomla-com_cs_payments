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

jimport('joomla.application.component.view');

/**
 * View class for a list of Cs_payments.
 */
class Cs_paymentsViewPayments extends JViewLegacy
{
	protected $items;
	protected $pagination;
	protected $state;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors));
		}
        
		Cs_paymentsHelper::addSubmenu('payments');
        
		$this->addToolbar();
        
        $this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT.'/helpers/cs_payments.php';

		$state	= $this->get('State');
		$canDo	= Cs_paymentsHelper::getActions($state->get('filter.category_id'));

		JToolBarHelper::title(JText::_('COM_CS_PAYMENTS_TITLE_PAYMENTS'), 'payments.png');

        //Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR.'/views/payment';
        if (file_exists($formPath)) {

            if ($canDo->get('core.create')) {
			    JToolBarHelper::addNew('payment.add','JTOOLBAR_NEW');
		    }

		    if ($canDo->get('core.edit') && isset($this->items[0])) {
			    JToolBarHelper::editList('payment.edit','JTOOLBAR_EDIT');
		    }

        }

		if ($canDo->get('core.edit.state')) {

            if (isset($this->items[0]->state)) {
			    JToolBarHelper::divider();
			    JToolBarHelper::custom('payments.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
			    JToolBarHelper::custom('payments.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
            } else if (isset($this->items[0])) {
                //If this component does not use state then show a direct delete button as we can not trash
                JToolBarHelper::deleteList('', 'payments.delete','JTOOLBAR_DELETE');
            }

            if (isset($this->items[0]->state)) {
			    JToolBarHelper::divider();
			    JToolBarHelper::archiveList('payments.archive','JTOOLBAR_ARCHIVE');
            }
            if (isset($this->items[0]->checked_out)) {
            	JToolBarHelper::custom('payments.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
            }
		}
        
        //Show trash and delete for components that uses the state field
        if (isset($this->items[0]->state)) {
		    if ($state->get('filter.state') == -2 && $canDo->get('core.delete')) {
			    JToolBarHelper::deleteList('', 'payments.delete','JTOOLBAR_EMPTY_TRASH');
			    JToolBarHelper::divider();
		    } else if ($canDo->get('core.edit.state')) {
			    JToolBarHelper::trash('payments.trash','JTOOLBAR_TRASH');
			    JToolBarHelper::divider();
		    }
        }

		if ($canDo->get('core.admin')) {
			JToolBarHelper::preferences('com_cs_payments');
		}
        
        //Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_cs_payments&view=payments');
        
        $this->extra_sidebar = '';
        
        
	}
    
	protected function getSortFields()
	{
		return array(
		'a.id' => JText::_('JGRID_HEADING_ID'),
		'a.amount' => JText::_('COM_CS_PAYMENTS_PAYMENTS_AMOUNT'),
		'a.payment_type' => JText::_('COM_CS_PAYMENTS_PAYMENTS_PAYMENT_TYPE'),
		'a.what' => JText::_('COM_CS_PAYMENTS_PAYMENTS_WHAT'),
		'a.datetimestamp' => JText::_('COM_CS_PAYMENTS_PAYMENTS_DATETIMESTAMP'),
		'a.date_paid' => JText::_('COM_CS_PAYMENTS_PAYMENTS_DATE_PAID'),
		'a.processed_by' => JText::_('COM_CS_PAYMENTS_PAYMENTS_PROCESSED_BY'),
		'a.processed_date' => JText::_('COM_CS_PAYMENTS_PAYMENTS_PROCESSED_DATE'),
		'a.response' => JText::_('COM_CS_PAYMENTS_PAYMENTS_RESPONSE'),
		'a.created_by' => JText::_('COM_CS_PAYMENTS_PAYMENTS_CREATED_BY'),
		'a.first_name' => JText::_('COM_CS_PAYMENTS_PAYMENTS_FIRST_NAME'),
		'a.last_name' => JText::_('COM_CS_PAYMENTS_PAYMENTS_LAST_NAME'),
		'a.address' => JText::_('COM_CS_PAYMENTS_PAYMENTS_ADDRESS'),
		'a.city' => JText::_('COM_CS_PAYMENTS_PAYMENTS_CITY'),
		'a.usastate' => JText::_('COM_CS_PAYMENTS_PAYMENTS_USASTATE'),
		'a.zipcode' => JText::_('COM_CS_PAYMENTS_PAYMENTS_ZIPCODE'),
		'a.phone' => JText::_('COM_CS_PAYMENTS_PAYMENTS_PHONE'),
		'a.phone_type' => JText::_('COM_CS_PAYMENTS_PAYMENTS_PHONE_TYPE'),
		'a.email' => JText::_('COM_CS_PAYMENTS_PAYMENTS_EMAIL'),
		'a.payment_reason' => JText::_('COM_CS_PAYMENTS_PAYMENTS_PAYMENT_REASON'),
		'a.source' => JText::_('COM_CS_PAYMENTS_SOURCE'),
		);
	}

    
}
