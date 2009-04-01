
CREATE TABLE `ezsphinx` (
  `id` int(11) NOT NULL auto_increment,
  `contentobject_id` int(11) NOT NULL,
  `attr_srch_pos0` longtext NOT NULL,
  `attr_srch_pos1` longtext NOT NULL,
  `attr_srch_pos2` longtext NOT NULL,
  `attr_srch_pos3` longtext NOT NULL,
  `attr_srch_pos4` longtext NOT NULL,
  `attr_srch_pos5` longtext NOT NULL,
  `attr_srch_pos6` longtext NOT NULL,
  `attr_srch_pos7` longtext NOT NULL,
  `attr_srch_pos8` longtext NOT NULL,
  `attr_srch_pos9` longtext NOT NULL,
  `attr_srch_pos10` longtext NOT NULL,
  `attr_srch_pos11` longtext NOT NULL,
  `attr_srch_pos12` longtext NOT NULL,
  `attr_srch_pos13` longtext NOT NULL,
  `attr_srch_pos14` longtext NOT NULL,
  `attr_srch_int_pos0` int(11) NOT NULL,
  `attr_srch_int_pos1` int(11) NOT NULL,
  `attr_srch_int_pos2` int(11) NOT NULL,
  `attr_srch_int_pos3` int(11) NOT NULL,
  `attr_srch_int_pos4` int(11) NOT NULL,
  `attr_srch_int_pos5` int(11) NOT NULL,
  `attr_srch_int_pos6` int(11) NOT NULL,
  `attr_srch_int_pos7` int(11) NOT NULL,
  `attr_srch_int_pos8` int(11) NOT NULL,
  `attr_srch_int_pos9` int(11) NOT NULL,
  `attr_srch_int_pos10` int(11) NOT NULL,
  `attr_srch_int_pos11` int(11) NOT NULL,
  `attr_srch_int_pos12` int(11) NOT NULL,
  `attr_srch_int_pos13` int(11) NOT NULL,
  `attr_srch_int_pos14` int(11) NOT NULL,
  `language_code` int(11) NOT NULL,
  `is_deleted` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `contentobject_id` (`contentobject_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `ezsphinx_counter` (
  `counter_id` int(11) NOT NULL,
  `max_index_id` int(11) NOT NULL,
  PRIMARY KEY  (`counter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `ezsphinx_pathnodes` (
  `id` int(11) NOT NULL,
  `nodepath_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`,`nodepath_id`),
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;