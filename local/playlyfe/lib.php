<?php
  require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
  require_once('classes/sdk.php');
  //     stdClass Object
  // (
  //     [userid] => 2
  //     [course] => 40
  //     [id] => 49
  //     [reaggregate] => 1420800081
  //     [timeenrolled] => 1420360717
  //     [timestarted] => 1420800081
  //     [timecompleted] => 1420800081
  // )
  function course_completed_handler($event) {
    global $DB;
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
    $courses_completed = $DB->get_records('course_completions', array('userid' => $userid));
    $courses_done = array();
    foreach($courses_completed as $course_completed) {
      array_push($courses_done, $course_completed->course);
    }
    mtrace(json_encode($courses_done));
    // $course_group = get('course_group', $arr);
    // $index = 0;
    // forech($course_group as $cg) {
    //    $has_done = false;
    //    $id = 'course_group_'.$index.'_completed';
    //    foreach($cg as $course) {
    //      if(!array_in($courses_done, $course)) {
    //        $has_done = false;
    //        break;
    //      }
    //    }
    //    if($has_done and !has_finished_rule($id, $userid)) {
    //       execute_rule($id, $event->userid);
    //       show_reward($event->userid);
    //     }
    //   $index++;
    // }
    execute_rule('course_'.$id.'_completed', $userid, $scopes);
    $criteria = $DB->get_record('course_completion_criteria', array('course' => $id, 'criteriatype' => 2));
    if($criteria and $criteria->timeend >= $timecompleted) {
      execute_rule('course_'.$id.'_bonus', $userid, $scopes);
    }
  }

  function activity_completion_changed_handler($event) {
    // echo 'ACTIVITY COMPLETED';
    // mtrace(json_encode($event));
    // print_object($event);
    // //$id = $event->id;
    // execute_rule($event->id, $event->userid);
  }

  function user_created_handler($event) {
    $pl = get_pl();
    $user_id = $event->id;
    $data = array('id' => 'u'.$user_id, 'alias' => $event->username, 'email' => $event->email);
    $pl->post('/admin/players', array(), $data);
    set_buffer($user_id, array());
  }

  //     stdClass Object
  // (
  //     [component] => mod_quiz
  //     [attemptid] => 14
  //     [timestamp] => 1420360717
  //     [userid] => 2
  //     [quizid] => 4
  //     [cmid] => 43
  //     [courseid] => 40
  //     [submitterid] => 2
  //     [timefinish] => 1420360717
  // )
  function pl_quiz_attempt_submitted_handler($event) {
    global $DB, $CFG;
    list($quiz, $cm) = get_cmid($event->cmid);
    $userid = $event->userid;
    $params = array();
    $statuscondition = ' AND state IN (:state1)';
    $params['state1'] = 'finished';
    $params['quizid'] = $quiz->id;
    $params['userid'] = $userid;
    $attempts = $DB->get_records_select('quiz_attempts',
            'quiz = :quizid AND userid = :userid' . $statuscondition,
            $params, 'attempt ASC');
    $lastfinishedattempt = end($attempts);
    $variables = array(
      'score' => intval($lastfinishedattempt->sumgrades)
    );
    $response = execute_rule('quiz_'.$event->quizid.'_submitted', $userid, array(), $variables);
    if($quiz->timeclose > 0 and $event->timefinish > $quiz->timeclose) {
      execute_rule('quiz_'.$event->quizid.'_bonus', $userid);
    }
    if(array_key_exists('local', $response[0][0]['events'])) {
      if(count($response[0][0]['events']['local']) > 0) {
        $data = get($userid.'_attempts');
        for($i=0;$i<count($data);$i++) {
          if(!$data[$i]) {
            unset($data[$i]);
          }
        }
        $pl = get_pl();
        try {
          $pl->post('/admin/players/u'.$userid.'/revert', array(), array('event_ids' => array($data)));
          $data = array();
        }
        catch(Exception $e) {
          //print_object($e);
        }
        array_push($data, $response[0][0]['events']['local'][0]['id']);
        set($userid.'_attempts', $data);
      }
    }
  }

  function forum_discussion_created_handler($event) {
    execute_rule('forum_'.$event->forum.'_discussion_created', $event->userid);
  }

  function forum_post_created_handler($event) {
    execute_rule('forum_'.$event->forum.'_post_created', $event->userid);
  }

  function forum_viewed_handler($event) {
    $id = 'forum_'.$event->forum.'_viewed';
    $userid = $event->userid;
    if(!has_finished_rule($id, $userid)) {
      execute_rule($id, $event->userid);
      show_reward($event->userid);
    }
  }

  function execute_rule($id, $userid, $scopes = array(), $variables = array()) {
    $pl = get_pl();
    # Maybe optimize this check if the rule actually exists
    try {
      $response = $pl->post('/admin/rules/'.$id, array(), array(
        'data' => array(
          array(
            'variables' => (object)$variables,
            'player_ids' => array('u'.$userid),
            'scopes' => $scopes
          )
        )
      ));
      add_to_buffer($userid, $response[0][0]['events']);
      return $response;
    }
    catch(Exception $e) {
      if($e->name == 'rule_not_found') {
        return;
      }
      else {
        mtrace(json_encode($e));
        print_object($e);
      }
    }
  }

  function show_reward($userid) {
    global $CFG, $PAGE;
    if (isloggedin() || !isguestuser()) {
      $data = calculate_data($userid);
      $PAGE->requires->js_init_call('show_rewards', array($data));
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
        $nodePlaylyfe->add('Settings', new moodle_url('/local/playlyfe/setting.php'), null, null, 'settings', new pix_icon('t/edit', 'edit'));

        $nodePlaylyfe->add('Course Group', new moodle_url('/local/playlyfe/course_group.php'), null, null, 'courses_group', new pix_icon('t/edit', 'edit'));

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
          $quizsett = $settingsnav->get(0);
          if(!$quizsett) {
            $quizsett = $settingsnav->get('modulesettings');
          }
          $quizsett->add('Gamification', new moodle_url('/local/playlyfe/quiz.php', array('cmid' => $PAGE->cm->id)), null, null, 'quiz', new pix_icon('t/edit', 'edit'));
        }
        if($PAGE->activityname == 'forum') {
          $coursesett = $settingsnav->get('courseadmin');
          $coursesett->add('Gamification', new moodle_url('/local/playlyfe/course.php', array('id' => $PAGE->course->id)), null, null, 'course', new pix_icon('t/edit', 'edit'));
          $forumsett = $settingsnav->get(0);
          if(!$forumsett) {
            $forumsett = $settingsnav->get('modulesettings');
          }
          $forumsett->add('Gamification', new moodle_url('/local/playlyfe/forum.php', array('cmid' => $PAGE->cm->id)), null, null, 'forum', new pix_icon('t/edit', 'edit'));
        }
      }
      //if (has_capability('moodle/course:manageactivities', $this->page->cm->context)) {
      //}
    }
  }

  function local_playlyfe_extends_navigation($navigation) {
    global $CFG, $PAGE, $USER, $DB, $OUTPUT;
    if($CFG->version <= 2012120311.00) {
      $PAGE->requires->js(new moodle_url('http://code.jquery.com/jquery-1.11.2.min.js'));
      $PAGE->requires->js(new moodle_url('http://code.jquery.com/ui/1.11.2/jquery-ui.min.js'));
      $PAGE->requires->css(new moodle_url('http://code.jquery.com/ui/1.11.2/themes/sunny/jquery-ui.css'));
    }
    else {
      $PAGE->requires->jquery();
      $PAGE->requires->jquery_plugin('ui');
      $PAGE->requires->jquery_plugin('ui-css');
    }
    $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/playlyfe/reward.js'));
    if (isloggedin() and !isguestuser() and !strpos($PAGE->url, '/mod/forum/post.php')) {
      $buffer = get_buffer($USER->id);
      $data = array(
        'events' => array(),
        'leaderboards' => array()
      );
      $leaderboads = array();
      $rule_id = '';
      if(count($buffer) > 0) {
        foreach($buffer as $events) {
          if(count($events) > 0 and array_key_exists('0', $events['local'])) {
            $event = $events['local'][0];
            if($event['event'] == 'custom_rule') {
              array_push($data['events'], $event);
              $rule_id = $event['rule']['id'];
              $rule_id = explode('_', $rule_id);
              $text = '';
              if(in_array('course', $rule_id)) {
                $leaderboard_ids = get_leaderboards('course'.$rule_id[1].'_leaderboard');
                if(count($leaderboard_ids) > 0) {
                  foreach($leaderboard_ids as $leaderboard_id) {
                    $text .= create_leaderboard($leaderboard_id, 'course'.$rule_id[1]);
                  }
                }
              }
              array_push($data['leaderboards'], $text);
            }
          }
        }
        echo '<div id="dialog"></div>';
        $PAGE->requires->js_init_call('show_rewards', array($data));
        set_buffer($USER->id, array());
      }
      if(json_decode(get_config('playlyfe', 'profile')) === true) {
        $navigation->add('Playlyfe Profile', new moodle_url('/local/playlyfe/profile.php'));
      }
      if(json_decode(get_config('playlyfe', 'notification')) === true) {
        $navigation->add('Notifications', new moodle_url('/local/playlyfe/notification.php'));
      }
      if(json_decode(get_config('playlyfe', 'leaderboards')) === true) {
        $navigation->add('Leaderboards', new moodle_url('/local/playlyfe/leaderboard.php'));
      }
    }
  }
