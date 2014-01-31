CREATE TABLE IF NOT EXISTS `#__cs_payments` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`amount` FLOAT NOT NULL ,
`payment_type` VARCHAR(32)  NOT NULL ,
`payment_reason` VARCHAR(255)  NOT NULL ,
`datetimestamp` DATETIME DEFAULT NULL,
`date_paid` DATETIME DEFAULT NULL,
`processed_by` VARCHAR(255)  NOT NULL ,
`processed_date` DATETIME DEFAULT NULL,
`response` VARCHAR(255)  NOT NULL ,
`created_by` INT(11)  NOT NULL ,
`first_name` VARCHAR(255)  NOT NULL ,
`last_name` VARCHAR(255)  NOT NULL ,
`address` VARCHAR(255)  NOT NULL ,
`city` VARCHAR(255)  NOT NULL ,
`usastate` VARCHAR(255)  NOT NULL ,
`zipcode` VARCHAR(255)  NOT NULL ,
`phone` VARCHAR(255)  NOT NULL ,
`phone_type` VARCHAR(255)  NOT NULL ,
`email` VARCHAR(255)  NOT NULL ,
`source` VARCHAR(255)  NOT NULL ,
`gender` VARCHAR(32)  NOT NULL ,
`lang_pref` VARCHAR(32)  NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__cs_donation_funds` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `iname` text NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

-- INSERT INTO `#__cs_donation_funds` (`id`, `iname`) VALUES
-- (1, 'Let us decide greatest need' ),
-- (2, 'Annual Fund (matched)' ),
-- (3, 'Internet Services ),
-- (4, 'Adyar-Olcott School ),
-- (5, 'Restoration Project ),
-- (6, 'Library-Friends of Olcott ),
-- (7, 'Education');

CREATE TABLE IF NOT EXISTS `#__cs_membership_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `typ` varchar(255) NOT NULL,
  `dues` varchar(255) NOT NULL,
  `show_order` int(11) NOT NULL DEFAULT '0',
  `lifetime_membership` int(11) NOT NULL DEFAULT '0',
  `age_min` int(11) NOT NULL DEFAULT '0',
  `age_max` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

-- INSERT INTO `#__cs_membership_types` (`id`, `typ`, `dues`, `show_order`, `lifetime_membership`, `age_max`, `age_min`) VALUES
-- (1, 'Single Person', '60,108,153', 1, 0, 0, 0),
-- (2, 'Student', '30', 2, 0, 24, 0),
-- (3, 'Senior', '30', 3, 0, 0, 75),
-- (4, 'Family', '96,180,267', 6, 0, 0, 0),
-- (5, 'Single Person Lifetime', '1500', 5, 1, 0, 0),
-- (6, 'Prisoner', '24', 4, 0, 0, 0);

CREATE TABLE IF NOT EXISTS `#__cs_sources` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

-- INSERT INTO `#__cs_sources` (`id`, `source`) VALUES
-- (1, 'Member Referral'),
-- (2, 'Web Search'),
-- (3, 'Event'),
-- (4, 'Web Browsing'),
-- (5, 'Newsletter'),
-- (6, 'Other'),
-- (7, 'Friend/Relative'),
-- (8, 'Local Branch or Study Center'),
-- (9, 'Books'),
-- (10, 'Olcott Library'),
-- (11, 'Quest Bookstore'),
-- (12, 'Theosophical.org'),
-- (13, 'Questbooks.net');
