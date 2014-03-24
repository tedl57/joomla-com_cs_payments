joomla-com_cs_payments
===============
This is a Joomla! 3.x component that allows an organization to collect online payments from people that want to  'Join', 'Renew' and/or 'Donate'.  This would be useful for a lot of non-profit organizations in world. 

It collects a person's information and preferred payment reason and then uses either authorize.net or PayPal as the certified payment processor.

Installation and Setup
===
1. On github (https://github.com/tedl57/joomla-com_cs_payments):
 * Download latest RELEASES/ .zip file to local computer

2. In Joomla 3.x Administrator:
 * Use Extension Manager to install the ZIP
 * Configure the component via Components/CS Payments/Options
 * Add Donation Funds, Membership Types and Sources
 * Use Menu Manager to add links to site side (where you want Join, Renew and Donate links)
 * Use Menu Manager to add a Payment Processor link (MAKE SPECIAL PERMISSION SINCE THE LINK IS FOR STAFF ONLY).

Testing
===
1. In Joomla 3.x Site:
 * Test various scenarios like: Join, Renew and Donate
 * Login as a 'staff member', test Payment Processor
