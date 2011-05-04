<?php  //$Id: settings.php,v 1.1.2.2 2007/12/19 17:38:49 skodak Exp $

$settings->add(new admin_setting_configtext('block_online_users_advanced_timetosee', get_string('timetosee', 'block_online_users_advanced'),
                   get_string('configtimetosee', 'block_online_users_advanced'), 5, PARAM_INT));
//
?>
