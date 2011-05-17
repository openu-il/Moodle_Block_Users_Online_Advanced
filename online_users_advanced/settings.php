<?php  //$Id: settings.php,v 1.1.2.2 2007/12/19 17:38:49 skodak Exp $

$settings->add(new admin_setting_configtext('block_online_users_advanced_timetosee', get_string('timetosee', 'block_online_users_advanced'),
                   get_string('configtimetosee', 'block_online_users_advanced'), 5, PARAM_INT));

$choices = array();
$selected = array();
$roles = get_records('role');
foreach($roles as $k=>$v)
{
	$choices[$k] = $v->shortname;
	$selected[$k] = $k;
}

$settings->add(new admin_setting_configmultiselect('block_online_users_advanced_visibleroles', get_string('visibleroles', 'block_online_users_advanced'), 
		get_string('configvisibleroles', 'block_online_users_advanced'), $selected, $choices));
//
?>
