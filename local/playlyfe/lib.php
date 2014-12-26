<?php
  require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
  require_once('classes/sdk.php');

  function course_completed_handler($event) {
    global $DB;
    mtrace(json_encode($event));
    $id = $event->course;
    $userid = $event->userid;
    $timecompleted = $event->timecompleted;
    $scopes = array();
    array_push($scopes, array(
      'id' => 'course'.$id,
      'entity_id' => 'u'.$userid
    ));
    // array_push($scopes, array(
    //   'id' => 'werwe/course'.$id,
    //   'entity_id' => 'u'.$USER->id
    // ));
    // $coures_completed = $DB->get_records('course_completions', array('userid' => $userid));
    // if(count($coures_completed) > 0) {
    //   foreach($coures_completed as $completed) {
    //   }
    // }
    execute_rule('course_'.$id.'_completed', $userid, $scopes);
    $criteria = $DB->get_record('course_completion_criteria', array('course' => $id, 'criteriatype' => 2));
    if($criteria and $criteria->timeend >= $timecompleted) {
      execute_rule('course_'.$id.'_bonus', $userid, $scopes);
    }
  }

  function activity_completion_changed_handler($event) {
    echo 'ACTIVITY COMPLETED';
    print_object($event);
    //$id = $event->id;
    //execute_rule($id);
  }

  function user_created_handler($event) {
    global $pl;
    if (true) {  //for moodle 2.5
      $data = array('id' => 'u'.$event->id, 'alias' => $event->username, 'email' => $event->email);
    }
    else {
      $data = $event->get_data();
      $user_id = $data['objectid'];
      $data = array('alias' => 'Anon', 'id' => 'u'.$user_id);
    }
    $pl->post('/admin/players', array(), $data);
    set_config('u'.$user_id.'_buffer', null, 'playlyfe');
  }

  function user_logout_handler($event) {
  }

  function user_enrolled_handler($event) {
  }

  function pl_quiz_attempt_started_handler($event) {
    print_object($event);
  }

  function pl_quiz_attempt_submitted_handler($event) {
    print_object($event);
    //execute_rule('quiz_'.$event->quizid.'_submitted');
    //execute_rule('quiz_'.$event->quizid.'_bonus');
  }

  function forum_discussion_created_handler($event) {
    execute_rule('forum_'.$event->forum.'_discussion_created');
  }

  function forum_post_created_handler($event) {
    execute_rule('forum_'.$event->forum.'_post_created');
  }

  function forum_viewed_handler($event) {
    execute_rule('forum_'.$event->f.'_viewed');
  }

  function execute_rule($id, $userid, $scopes = array()) {
    $pl = get_pl();
    # Maybe optimize this check if the rule actually exists
    try {
      $response = $pl->post('/admin/rules/'.$id, array(), array(
        'data' => array(
          array(
            'variables' => (object)array(),
            'player_ids' => array('u'.$userid),
            'scopes' => $scopes
          )
        )
      ));
      add_to_buffer($userid, $response[0][0]['events']);
    }
    catch(Exception $e) {
      mtrace(json_encode($e));
      if($e->name == 'rule_not_found') {
        return;
      }
      else {
        print_object($e);
      }
    }
  }

  function local_playlyfe_extends_settings_navigation(settings_navigation $settingsnav, $context) {
    global $USER, $PAGE;
    if(is_siteadmin($USER)) {
      $sett = $settingsnav->get('root');
      if($sett != null) {
        $nodePlaylyfe = $sett->add('Gamification', null, null, null, 'playlyfe');

        $nodePlaylyfe->add('Client', new moodle_url('/local/playlyfe/client.php'), null, null, 'client', new pix_icon('t/edit', 'edit'));
        $nodePlaylyfe->add('Publish', new moodle_url('/local/playlyfe/publish.php'), null, null, 'publish', new pix_icon('t/edit', 'edit'));

        $nodePlaylyfe->add('Courses', new moodle_url('/local/playlyfe/courses.php'), null, null, 'courses', new pix_icon('t/edit', 'edit'));

        $nodePlaylyfe->add('Courses Completion', new moodle_url('/local/playlyfe/course_group.php'), null, null, 'courses_group', new pix_icon('t/edit', 'edit'));

        $nodeMetric = $nodePlaylyfe->add('Metrics', null, null, null, 'metrics');
        $nodeMetric->add('Manage Metrics', new moodle_url('/local/playlyfe/metric/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeMetric->add('Add a new metric', new moodle_url('/local/playlyfe/metric/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));

        $nodeSet = $nodePlaylyfe->add('Set Badges', null, null, null, 'sets');
        $nodeSet->add('Manage sets', new moodle_url('/local/playlyfe/set/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeSet->add('Add a new set', new moodle_url('/local/playlyfe/set/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));
      }
      if ($context->contextlevel == 50) { //CONTEXT_COURSE
        if (has_capability('moodle/site:config', $context)) {
          if ($node = $coursesett = $settingsnav->get('courseadmin') ) {
            $node->add('Gamification', new moodle_url('/local/playlyfe/course.php', array('id' => $PAGE->course->id)), null, null, 'course', new pix_icon('t/edit', 'edit'));
          }
        }
        // completion notify
        //require_login($course, false);
        // If the user is allowed to edit this course, he's allowed to edit list of repository instances
        //require_capability('moodle/course:update',  $context);
      }
      if ($context->contextlevel == 70) { //CONTEXT_MODULE
        if($PAGE->activityname == 'quiz') {
          $coursesett = $settingsnav->get('courseadmin');
          $coursesett->add('Gamification', new moodle_url('/local/playlyfe/course.php', array('id' => $PAGE->course->id)), null, null, 'course', new pix_icon('t/edit', 'edit'));
          $quizsett = $settingsnav->get('modulesettings');
          $quizsett->add('Gamification', new moodle_url('/local/playlyfe/quiz.php', array('cmid' => $PAGE->cm->id)), null, null, 'quiz', new pix_icon('t/edit', 'edit'));
        }
        if($PAGE->activityname == 'forum') {
          $coursesett = $settingsnav->get('courseadmin');
          $coursesett->add('Gamification', new moodle_url('/local/playlyfe/course.php', array('id' => $PAGE->course->id)), null, null, 'course', new pix_icon('t/edit', 'edit'));
          $forumsett = $settingsnav->get('modulesettings');
          $forumsett->add('Gamification', new moodle_url('/local/playlyfe/forum.php', array('cmid' => $PAGE->cm->id)), null, null, 'forum', new pix_icon('t/edit', 'edit'));
        }
      }
      //if (has_capability('moodle/course:manageactivities', $this->page->cm->context)) {
      //}
    }
  }

  function local_playlyfe_extends_navigation($navigation) {
    if (isloggedin() and !isguestuser()) {
      global $CFG, $PAGE, $USER, $DB, $OUTPUT;
      $buffer = get_buffer($USER->id);
      $data = array();
      $rule_id = '';
      if(count($buffer) > 0) {
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/playlyfe/reward.js'));
        foreach($buffer as $events) {
          if(count($events) > 0 and array_key_exists('0', $events['local'])) {
            $event = $events['local'][0];
            if($event['event'] == 'custom_rule') {
              array_push($data, $event);
              // $rule_id = $event['rule']['id'];
              // $html = '<div id="dialog" title="'.$event['rule']['name'].'" style="display: none">';
              // $html .= '<h3>You have Gained</h3>';
              // $count = 1;
              // foreach($event['changes'] as $change) {
              //   $html .= '<p>'.display_reward($count, $change).'</p>';
              //   $count++;
              // }
              // $dialog_counter++;
            }
          }
        }
        // $rule_id = explode('_', $rule_id);
        // if(in_array('course', $rule_id)) {
        //   $leaderboard_ids = get_leaderboards('course'.$rule_id[1].'_leaderboard');
        //   if(count($leaderboard_ids) > 0) {
        //     foreach($leaderboard_ids as $leaderboard_id) {
        //       $html .= create_leaderboard($leaderboard_id, 'course'.$rule_id[1]);
        //     }
        //   }
        // }
        //$html .= ;
        echo '<div id="dialog"></div>';
        $PAGE->requires->js_init_call('show_rewards', array($data));
        //set_buffer($USER->id, array());
      }
      $nodeProfile = $navigation->add('Playlyfe Profile', new moodle_url('/local/playlyfe/profile.php'));
      $nodeNotifications = $navigation->add('Notifications', new moodle_url('/local/playlyfe/notification.php'));
    }
  }

  function display_reward($count, $change) {
    $text = $count.'.';
    $metric= $change['metric'];
    $delta = $change['delta'];
    $text .= '<img src="image_def.php?metric='.$metric['id'].'&size=large"></img>';
    if ($metric['type'] == 'point') {
      $value = $delta['new'] - $delta['old'];
    }
    else {
      foreach($delta as $key => $value) {
        $value = ($value['new'] - $value['old']).' x '.$key;
        $value .= '     <img src="image_def.php?metric='.$metric['id'].'&size=medium&item='.$key.'"></img>    ';
      }
    }
    $text .= 'You have gained '.$value.' '.$metric['name'];
    return $text;
  }
  /*
   For testing events
  */
  function complete_course($id, $user_id = 0) {
    global $USER;
    if($user_id == 0) {
      $user_id = $USER->id;
    }
    $event_data = new stdClass();
    $event_data->id = $id;
    $event_data->course = $id;
    $event_data->userid = $user_id;
    events_trigger('course_completed', $event_data);
  }

  function complete_quiz($id, $user_id = 0) {
    global $USER;
    if($user_id == 0) {
      $user_id = $USER->id;
    }
    $event_data = new stdClass();
    $event_data->id = $id;
    $event_data->course = $id;
    $event_data->userid = $user_id;
    events_trigger('course_completed', $event_data);
  }
