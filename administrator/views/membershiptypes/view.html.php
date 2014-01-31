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
class Cs_paymentsViewMembershiptypes extends JViewLegacy
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
        
		Cs_paymentsHelper::addSubmenu('membershiptypes');
        
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

		JToolBarHelper::title(JText::_('COM_CS_PAYMENTS_TITLE_MEMBERSHIP_TYPES'), 'payments.png');

        //Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR.'/views/membershiptype';
        if (file_exists($formPath)) {

            if ($canDo->get('core.create')) {
			    JToolBarHelper::addNew('membershiptype.add','JTOOLBAR_NEW');
		    }

		    if ($canDo->get('core.edit') && isset($this->items[0])) {
			    JToolBarHelper::editList('membershiptype.edit','JTOOLBAR_EDIT');
		    }

        }

		if ($canDo->get('core.edit.state')) {

            if (isset($this->items[0]->state)) {
			    JToolBarHelper::divider();
			    JToolBarHelper::custom('membershiptypes.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
			    JToolBarHelper::custom('membershiptypes.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
            } else if (isset($this->items[0])) {
                //If this component does not use state then show a direct delete button as we can not trash
                JToolBarHelper::deleteList('', 'membershiptypes.delete','JTOOLBAR_DELETE');
            }

            if (isset($this->items[0]->state)) {
			    JToolBarHelper::divider();
			    JToolBarHelper::archiveList('membershiptypes.archive','JTOOLBAR_ARCHIVE');
            }
            if (isset($this->items[0]->checked_out)) {
            	JToolBarHelper::custom('membershiptypes.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
            }
		}
        
        //Show trash and delete for components that uses the state field
        if (isset($this->items[0]->state)) {
		    if ($state->get('filter.state') == -2 && $canDo->get('core.delete')) {
			    JToolBarHelper::deleteList('', 'membershiptypes.delete','JTOOLBAR_EMPTY_TRASH');
			    JToolBarHelper::divider();
		    } else if ($canDo->get('core.edit.state')) {
			    JToolBarHelper::trash('membershiptypes.trash','JTOOLBAR_TRASH');
			    JToolBarHelper::divider();
		    }
        }

		if ($canDo->get('core.admin')) {
			JToolBarHelper::preferences('com_cs_payments');
		}
        
        //Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_cs_payments&view=membershiptypes');
        
        $this->extra_sidebar = '';
        
        
	}
    
	protected function getSortFields()
	{
		return array(
		'a.id' => JText::_('JGRID_HEADING_ID'),
		'a.typ' => JText::_('COM_CS_PAYMENTS_MEMBERSHIP_TYPES_TYP'),
		'a.dues' => JText::_('COM_CS_PAYMENTS_MEMBERSHIP_TYPES_DUES'),
		'a.show_order' => JText::_('COM_CS_PAYMENTS_MEMBERSHIP_TYPES_SHOW_ORDER'),
		'a.lifetime_membership' => JText::_('COM_CS_PAYMENTS_MEMBERSHIP_TYPES_LIFETIME_MEMBERSHIP'),
		'a.age_max' => JText::_('COM_CS_PAYMENTS_MEMBERSHIP_TYPES_AGE_MAX'),
		'a.age_min' => JText::_('COM_CS_PAYMENTS_MEMBERSHIP_TYPES_AGE_MIN'),
		);
	}

    
}
