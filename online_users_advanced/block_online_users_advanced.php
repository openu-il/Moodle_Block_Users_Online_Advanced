<?php //$Id: block_online_users.php,v 1.54.2.7 2009/11/20 03:08:59 andyjdavis Exp $
//require_once 'sort_group_form.php';
define('MAIN_PAGE_ONLINE_USRES', 5);
define('INNER_PAGE_ONLINE_USRES', 10);
/**
 * This block needs to be reworked.
 * The new roles system does away with the concepts of rigid student and
 * teacher roles. TODO : description;
 */
class block_online_users_advanced extends block_base {
	function init() {
		global $COURSE, $CFG, $MAIN_COURSE_ID;
		$main_course = get_records_sql("SELECT id FROM {$CFG->prefix}course", 0, 1);
		$courseid = array_keys($main_course);
		$MAIN_COURSE_ID = $courseid[0];
		$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
//		if(is_inside_frontpage($context)){
			$this->title = get_string('blockname_front_page','block_online_users_advanced');
//		}else{
//			$this->title = get_string('blockname_course','block_online_users_advanced');
//		}
		$this->version = 2010111610;
	}

	function has_config() {return true;}

	function get_content() {
		global $USER, $CFG, $COURSE, $MAIN_COURSE_ID;

		$users = array();

		if ($this->content !== NULL) {
			return $this->content;
		}

		$this->content = new stdClass;
		$this->content->text = '';
		$this->content->footer = '';

		if (empty($this->instance)) {
			return $this->content;
		}

		$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
		
		$users = $this->_get_online_users_by_course($COURSE->id);

		/*$mform = & new sort_group_form($CFG->wwwroot . "/course/view.php?id={$COURSE->id}{$group_str}", array('group_id' => $group_id,), 'post');

		ob_start();

		$mform->display();

		$form_html = ob_get_contents();

		ob_end_clean();

		$this->content->text = $form_html;*/

		//Now, we have in users, the list of users to show
		//Because they are online
		if (count($users) && is_array($users))
		{

			//Accessibility: Don't want 'Alt' text for the user picture; DO want it for the envelope/message link (existing lang string).
			//Accessibility: Converted <div> to <ul>, inherit existing classes & styles.
			$this->content->text .= "<ul class='list'>";

			$this->_display_users_list($users);

			$this->content->text .= '</ul><div class="clearer"><!-- --></div>';

		}
		// There are no users to display
		else
		{
			$this->content->text .= "<div class=\"info\">".get_string("none")."</div>";
		}

		if ($COURSE->id != $MAIN_COURSE_ID)
		{

			// 20.01.2011 Added all users link
			$this->content->text .= "<div style='padding-top: 7px;'><div class='icon column c0'><img src='{$CFG->pixpath}/i/user.gif' alt='". get_string('messageselectadd') ."' /></div><div class='column c1'><a href='{$CFG->wwwroot}/user/index.php?id={$COURSE->id}'>" . get_string('allcourseusers', 'block_online_users_advanced') . "</a></div></div><div class='clearer'><!-- --></div>";

			//$this->content->text .= "<div><div class='icon column c0'><img src='{$CFG->pixpath}/i/user.gif' alt='". get_string('messageselectadd') ."' /></div><div class='column c1'><a href='{$CFG->wwwroot}/user/index.php?id={$COURSE->id}&roleid=7'>" . get_string('allcourseteachers', 'block_online_users_advanced') . "</a></div></div><div class='clearer'><!-- --></div>";
		}

		return $this->content;
	}


	/**
	 * Build List of Items for user's list
	 * In case of courses users -
	 * add name of course before each list of users
	 * @param array $course_users
	 * @return void
	 */
	function _display_users_list($course_users)
	{
		global $USER, $CFG, $COURSE;
		$courses_list = array();
		$is_set_title = false;
		$user_counter = 0;

		// If  in the certain course
		if ($COURSE->id != SITEID)
		{
			$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
			foreach ($course_users as $id => $course_user)
			{
				// If number of user achived a max user per page - exit
				if ($user_counter == INNER_PAGE_ONLINE_USRES)
				{
					break;	
				}
				
				$this->_display_user_record($course_user, $context, $course_user->courseid);
				
				$user_counter++;
			}
		}
		else // into main page
		{
			foreach ($course_users as $id => $course_user)
			{
				
				// If first user or user from other course - print Course Full Name first
				if ((!isset($current_course) || ($course_user->courseid !== $current_course)) || $is_set_title === false)
				{
					$this->content->text .= "<li class='listentry'><span class='coursetitle'>{$course_user->fullname}:</span></li>";
					$is_set_title = true;
					$user_counter = 0;					
				}

				// If number of user achived a max user per page - exit
				if ($user_counter == MAIN_PAGE_ONLINE_USRES)
				{
					continue;	
				}
				
				$current_course = $course_user->courseid;

				$context = get_context_instance(CONTEXT_COURSE, $course_user->courseid);

				$this->_display_user_record($course_user, $context, $course_user->courseid);
				
				$user_counter++;
			}
		}		
	}


	/**
	 * Build list of courses objects
	 * @param string $courses_string string of comma separated cours_id's
	 * @return array $courses objects of course
	 */
	function _get_courses_list($courses_string)
	{
		$courses_rs = get_recordset_list('course', 'id', $courses_string);

		$courses = recordset_to_array($courses_rs);

		return $courses;
	}


	/**
	 * Build $this->content for particular user's record
	 * @param object $user
	 * @param object $context
	 * @param integer $course_id
	 * @return void
	 */
	function _display_user_record($user, $context, $course_id = null)
	{
		global $USER, $CFG;
		$position_style = "style='font-weight: bold;'";

		if (!$course_id)
		{
			global $COURSE;
			$course_id = $COURSE->id;
		}

		// Check user roles in order to highlight link(if it's neccesary)
		if ($roles = get_user_roles($context, $user->id))
		{
			foreach ($roles as $role)
			{
				$user_roles[] = $role->shortname;
			}
			// It is possible user have two similar roles(system and per course)
			$user_roles = array_unique($user_roles);
		}

		// If users role is ONLY student - initialize $position_class - regular user name - unhighlight
		if ((in_array('student', $user_roles) || in_array('guest', $user_roles)) /*&& count($user_roles) === 1*/)
		{
			$position_style = '';
		}

		$this->content->text .= '<li class="listentry">';
		$timeago = format_time(time() - $user->lastaccess); //bruno to calculate correctly on frontpage
		if ($user->username == 'guest') {
			$this->content->text .= '<div class="user">'.print_user_picture($user->id, $COURSE->id, $user->picture, 16, true, false, '', false);
			$this->content->text .= get_string('guestuser').'</div>';
			// If user is not guest - build link, if not student - highlight it.
		} else {
			$this->content->text .= "<div class='user'><a {$position_style} href='{$CFG->wwwroot}/user/view.php?id={$user->id}&amp;course={$course_id}' title='{$timeago}'>";
			$this->content->text .= print_user_picture($user->id, $course_id, $user->picture, 16, true, false, '', false);
			//			$this->content->text .= $user->firstname.'</a></div>';
			$this->content->text .=fullname( $user).'</a></div>'; //yifatsh print user full name 3/2011
		}

		// If is not logged in user - add send message engine
		if (!empty($USER->id) && has_capability('moodle/site:sendmessage', $context)
		&& !empty($CFG->messaging) && !isguest()) {
			$canshowicon = true;
		} else {
			$canshowicon = false;
		}

		if ($canshowicon && ($USER->id != $user->id) &&  $user->username != 'guest') {  // Only when logged in and messaging active etc
			$this->content->text .= '<div  class="user_msg"><a title="'.get_string('messageselectadd').'" href="'.$CFG->wwwroot.'/message/discussion.php?id='.$user->id.'" onclick="this.target=\'message_'.$user->id.'\';return openpopup(\'/message/discussion.php?id='.$user->id.'\', \'message_'.$user->id.'\', \'menubar=0,location=0,scrollbars,status,resizable,width=400,height=500\', 0);">'
			.'<img class="iconsmall" src="'.$CFG->pixpath.'/t/message.gif" alt="'. get_string('messageselectadd') .'" /></a></div>';
		}
		$this->content->text .= "</li>";
	}


	/**
	 * Build array of ONLINE users belongs to submitted course or all courses
	 * @param $online_users
	 * @param $course_id
	 */
	function _get_online_users_by_course($course_id)
	{		
		global $CFG, $USER, $MAIN_COURSE_ID;
		$online_users = array();
		$my_courses   = '';

		if($course_id == SITEID)
		{
			$my_courses = (isset($USER->myenroledcourses) && !empty($USER->myenroledcourses)) ? $USER->myenroledcourses : $this->_get_my_enroled_courses();
		}
		else
		{
			$my_courses = $course_id;
		}

		$timetoshowusers = 300; //Seconds default

		if (isset($CFG->block_online_users_advanced_timetosee))
		{
			$timetoshowusers = $CFG->block_online_users_advanced_timetosee * 60;
		}

		$timefrom = 100 * floor((time()-$timetoshowusers) / 100); // Round to nearest 100 seconds for better query cache
		
		// If not assigned to any course - return empty course array
		if (empty($my_courses) || $my_courses == '')
		{
			return array();
		}
		else
		{
			$sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.picture, u.lastaccess, c.id AS courseid, c.fullname
				FROM mdl_user u 
				LEFT JOIN mdl_role_assignments ra ON ra.userid = u.id 
				LEFT JOIN mdl_context n ON n.id = ra.contextid
				INNER JOIN mdl_course c ON c.id = n.instanceid
				WHERE  c.id IN ({$my_courses})
				AND u.lastaccess > {$timefrom}
				AND ra.hidden = 0
				AND u.id NOT IN (SELECT userid FROM mdl_user_info_data WHERE fieldid = 2 AND DATA = '0') 
				GROUP BY courseid, u.id
				ORDER BY courseid, u.lastaccess DESC";
		}
		// If not assigned to any course - return empty course array
		if (!$users_records = get_recordset_sql($sql))
		{			
			return array();
		}
		else
		{
			while ($record = rs_fetch_next_record($users_records)) 
			{
				$online_users[] = $record;
			}
			rs_close($users_records);
			 
			return $online_users;
		}

		

	}


	/**
	 * Function return all course's ID
	 * current user is enroled to as student, teacher etc.(defined in $CFG)
	 * 
	 * @return string $my_courses string of enroled courses separated by comma
	 */
	function _get_my_enroled_courses()
	{
		global $CFG, $USER;

		$my_courses = '';
			
		$sql = "SELECT c.id as courseid
						FROM mdl_course c
						LEFT JOIN mdl_context cn ON cn.instanceid = c.id  
						LEFT JOIN mdl_role_assignments ra ON ra.contextid = cn.id
						LEFT JOIN mdl_user u ON u.id = ra.userid
						LEFT JOIN mdl_role r ON r.id = ra.roleid 
						WHERE ra.userid = {$USER->id} 
							AND ra.roleid IN ({$CFG->block_online_users_advanced_visibleroles})
							AND ra.contextid <> 2";

		$enroled_courses = get_records_sql($sql);

		if (is_array($enroled_courses) && count($enroled_courses))
		{
			foreach($enroled_courses as $enroled_course)
			{
				$my_courses[] =  $enroled_course->courseid;
			}
				
			$my_courses = implode(',', $my_courses);
		}

		if (!empty($my_courses))
		{
			$USER->myenroledcourses = $my_courses;
		}
			
		return $my_courses;
	}


	/**
	 * Complete $users_per_course array to definite number
	 * (inner course page - 10, main page - 5 per course)
	 * @param array $users_per_course objects of users
	 * @param integer $course_id
	 * @param boolean $is_main main = true, inner = false;
	 * @return array $users_per_course;
	 */
	function _complete_users_list($users_per_course, $course_id, $is_main = true)
	{
		global $MAIN_COURSE_ID;

		if (!isset($course_id))
		{
			$course_id = $MAIN_COURSE_ID;
		}

		$complete_user_list = array();

		if (!count($users_per_course) || !is_array($users_per_course))
		{
			return false;
		}

		// If main page - build lists course -> 5 users
		if ($is_main)
		{
			// Go throught the array and build list of 5 users per course
			foreach ($users_per_course as $course_id => $course_users)
			{
				$complete_user_list[$course_id] = $this->_calculate_missing_users($course_users, $course_id, MAIN_PAGE_ONLINE_USRES);
			}
		}
		//else - build list with 10 users only
		else
		{
			$complete_user_list = $this->_calculate_missing_users($users_per_course, $course_id, INNER_PAGE_ONLINE_USRES);
		}

		return $complete_user_list;
	}


	/**
	 * Merge/slice users array in order to get an appropriate number of users in list
	 * depends on parameter submitted to functon
	 * @param array $users_per_course current user array
	 * @param integer $course_id
	 * @param integer $complete_to_number number of users to return (include exists)
	 */
	function _calculate_missing_users($users_per_course, $course_id, $complete_to_number)
	{
		$calc_users = array();

		$current_users_count = count($users_per_course);

		// If more then needed number of users(5 or 10 depends on location) in current course are ONLINE - slice the rest
		if ($current_users_count > $complete_to_number)
		{
			return array_slice($users_per_course, 0, $complete_to_number);
		}
		// If less then needed number of users(5 or 10 depends on location) - get missing random(fill to 5)
		elseif ($current_users_count < $complete_to_number)
		{
			$need_users = $complete_to_number - $current_users_count;
			$exceptions = implode(',', array_keys($users_per_course));
			return $users_per_course + $this->_get_users_random($course_id, $exceptions, $need_users);
		}
	}


	/**
	 * Create array of supplid number of online users
	 * @param integer $course_id
	 * @param array $exceptions - array of users not to add to list
	 * @param integer $users_num - num of users to complete
	 * @return array $random_users  user object's array
	 */
	function _get_users_random($course_id, $exceptions, $need_users)
	{
		$random_users = $this->_get_online_users($course_id, false, $exceptions, $need_users);

		return $random_users;
	}


	/**
	 * Check and remove offline users from $all_users_per_course
	 * @param reference $users_per_course array of all course participants
	 * @param array $online_users online users
	 * @return void
	 */
	function _filter_users(&$all_users_per_course, $online_users)
	{
		global $COURSE, $MAIN_COURSE_ID;

		$online_users_per_course = array();

		$online_course_users     = array();

		// If internal course page
		if ($COURSE->id != $MAIN_COURSE_ID)
		{
			$all_users_per_course = $this->_remove_offline_course_users($all_users_per_course, $online_users);
		}
		//Else if main page
		else
		{
			if (count($all_users_per_course))
			{
				foreach ($all_users_per_course as $course_id => $course_users)
				{
					if (is_array($course_users) && count($course_users))
					{
						$all_users_per_course[$course_id] = $this->_remove_offline_course_users($course_users, $online_users);
					}
				}
			}
		}
	}

	/**
	 * Remove from $all_users users which are not located in $online users array
	 * @param reference $all_users array of users objects
	 * @param array $online_users_ids array of online user's ID's
	 * @return array $clean_users array of online course user's objects
	 */
	function _remove_offline_course_users($all_users, $online_users_ids)
	{
		$online_users = array();
		$clean_users  = array();

		$online_users = array_flip($online_users_ids);

		if (is_array($online_users) && is_array($all_users))
		{
			$clean_users = array_intersect_key($all_users, $online_users);
		}

		return $clean_users;
	}

	/**
	 * Build array of online users exclude $exception(if exists)
	 * @param boolean $is_group
	 * @param string $exceptions
	 * @param integer $limit
	 * @return array $online_users online users
	 */
	function _get_online_users($course_id = null, $is_group = false, $exceptions = '', $limit = 50)
	{
		global $USER, $CFG;

		// If not defined course id - get $course from global $COURSE(by location)
		if (!$course_id)
		{
			global $COURSE;
			$course = $COURSE;
		}
		// Else get course data from DB
		else
		{
			if (!$course = get_record('course', 'id', $course_id))
			{
				error(get_string('nocourses'));
			}
		}

		$timetoshowusers = 300; //Seconds default
		if (isset($CFG->block_online_users_advanced_timetosee)) {
			$timetoshowusers = $CFG->block_online_users_advanced_timetosee * 60;
		}
		//$timefrom = 100 * floor((time()-$timetoshowusers) / 100); // Round to nearest 100 seconds for better query cache

		$timefrom = time()-$timetoshowusers;
		// Get context so we can check capabilities.
		$context = get_context_instance(CONTEXT_COURSE, $course->id);

		if (empty($this->instance->pinned)) {
			$blockcontext = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
		} else {
			$blockcontext = get_context_instance(CONTEXT_SYSTEM); // pinned blocks do not have own context
		}

		//Calculate if we are in separate groups
		$isseparategroups = ($course->groupmode == SEPARATEGROUPS
		&& $course->groupmodeforce
		&& !has_capability('moodle/site:accessallgroups', $context));

		//Get the user current group
		$currentgroup = $isseparategroups ? groups_get_course_group($course) : NULL;

		$groupmembers = "";
		$groupselect  = "";
		$rafrom       = "";
		$rawhere      = "";

		if ($exceptions != '')
		{
			$exceptions  = "AND u.id NOT IN ({$exceptions}) ";
		}
		// 28.02.2011 Dima Do not display users which selected to not display in online list
		$exceptions .= "AND u.id NOT IN (SELECT userid FROM {$CFG->prefix}user_info_data WHERE fieldid = 2 AND data = '0') ";

		//Add this to the SQL to show only group users
		if ($is_group !== FALSE && $currentgroup !== NULL)
		{
			$groupmembers = ",  {$CFG->prefix}groups_members gm ";
			$groupselect = " AND u.id = gm.userid AND gm.groupid = '$currentgroup'";
		}

		if ($course->id == SITEID) {  // Site-level
			$select = "SELECT u.id, u.username, u.firstname, u.lastname, u.picture, max(u.lastaccess) as lastaccess ";
			$from = "FROM {$CFG->prefix}user u
			$groupmembers ";
			$where = "WHERE u.lastaccess > $timefrom
			$groupselect ";
			$order = "ORDER BY lastaccess DESC ";

		} else { // Course-level
			if (!has_capability('moodle/role:viewhiddenassigns', $context)) {
				//$pcontext = get_related_contexts_string($context);
				$pcontext = "IN ($context->id)";
				$rafrom  = ", {$CFG->prefix}role_assignments ra";
				$rawhere = " AND ra.userid = u.id AND ra.contextid $pcontext AND ra.hidden = 0";
			}

			$courseselect = "AND ul.courseid = '".$course->id."'";
			$select = "SELECT u.id, u.username, u.firstname, u.lastname, u.picture, max(ul.timeaccess) as lastaccess ";
			$from = "FROM {$CFG->prefix}user_lastaccess ul,
			{$CFG->prefix}user u
			$groupmembers $rafrom ";
			$where =  "WHERE ul.timeaccess > $timefrom
                       AND u.id = ul.userid
                       AND ul.courseid = $course->id
                       $groupselect $rawhere ";
                       $order = "ORDER BY lastaccess, username DESC ";
		}

		$groupby = "GROUP BY u.id, u.username, u.firstname, u.lastname, u.picture ";

		//Calculate minutes
		$minutes  = floor($timetoshowusers/60);

		// Verify if we can see the list of users, if not just print number of users
		/*if (!has_capability('block/online_users_advanced:viewlist', $blockcontext)) {
		if (!$usercount = count_records_sql("SELECT COUNT(DISTINCT(u.id)) $from $where")) {
		$usercount = get_string("none");
		}
		$this->content->text = "<div class=\"info\">".get_string("periodnminutes","block_online_users_advanced",$minutes).": $usercount</div>";
		return $this->content;
		}*/

		$SQL = $select . $from . $where . $exceptions . $groupby . $order;

		if ($online_users = get_records_sql($SQL, 0, $limit)) {   // We'll just take the most recent 50 maximum
			foreach ($online_users as $online_user) {
				$online_users[$online_user->id]->fullname = fullname($online_user);
			}
		} else {
			$online_users = array();
		}

		return $online_users;
	}

	/*
	 yifatsh 2/2011 add specialization  function
	 only openu admin can edit / move this block
	 */
	function specialization() {
		global $USER , $COURSE;
		if (isediting($COURSE)) {
			if(isadmin())	{
				$this->instance->pinned=false;
			}
			else{
				$this->instance->pinned=true;
			}
		}
	}


}

?>
