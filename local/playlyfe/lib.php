<?php
  require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
  require_once('classes/sdk.php');

  function pl_quiz_attempt_started_handler($event) {
    print_object($event);
  }

  function pl_quiz_attempt_submitted_handler($event) {
    print_object($event);
  }

  function course_completed_handler($event) {
    global $USER;
    global $pl;
    $id = $event->id;
    try {
      $action = $pl->get('/design/versions/latest/actions/course_completed');
      foreach($action['rules'] as $rule) {
        if ($rule['requires']['context']['rhs'] == $id) {
          $data = array();
          $data['variables'] = array(
            'course_id' => $id
          );
          $leaderboard_metric = get_config('playlyfe', 'course'.$id);
          if(!is_null($leaderboard_metric)) {
            $data['scopes'] = array(
              array(
                'id' => $leaderboard_metric.'/'.'course'.$id,
                'entity_id' => 'u'.$USER->id
              )
            );
          }
          $response = $pl->post('/runtime/actions/course_completed/play', array('player_id' => 'u2'), $data);
          set_config('u'.$USER->id.'_buffer', json_encode($response['events']), 'playlyfe');
          break;
        }
      }
    }
    catch(Exception $e) {
      print_object($e);
    }
  }

  function activity_completion_changed_handler($event) {
    global $USER;
    print_object($event);
    $id = $event->id;
  }

  function user_logout_handler($event) {
  }

  function user_enrolled_handler($event) {
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

  function local_playlyfe_extends_settings_navigation(settings_navigation $settingsnav, $context) {
    global $USER, $PAGE;
    if(is_siteadmin($USER)) {
      $sett = $settingsnav->get('root');
      if($sett != null) {
        $nodePlaylyfe = $sett->add('Gamification', null, null, null, 'playlyfe');

        $nodePlaylyfe->add('Client', new moodle_url('/local/playlyfe/client.php'), null, null, 'client', new pix_icon('t/edit', 'edit'));
        $nodePlaylyfe->add('Publish', new moodle_url('/local/playlyfe/publish.php'), null, null, 'publish', new pix_icon('t/edit', 'edit'));

        $nodePlaylyfe->add('Courses', new moodle_url('/local/playlyfe/courses.php'), null, null, 'courses', new pix_icon('t/edit', 'edit'));

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
      }
      //if (has_capability('moodle/course:manageactivities', $this->page->cm->context)) {
      //}
    }
  }

  function local_playlyfe_extends_navigation($navigation) {
    global $pl;
    if (isloggedin() and !isguestuser()) {
      global $CFG, $PAGE, $USER, $DB, $OUTPUT;
      //complete_course(14);
      $buffer = json_decode(get_config('playlyfe', 'u'.$USER->id.'_buffer'), true);
      if(!is_null($buffer)) {
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/playlyfe/page.js'));
        $course_id = $buffer['local'][0]['action']['vars']['course_id'];
        $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
        $title = 'Completed Course '.$course->shortname;
        $html = '<div id="plDialog" title="'.$title.'">';
        $html .= '<h3>You have Gained</h3>';
        $count = 1;
        foreach($buffer['local'][0]['changes'] as $change) {
          $html .= '<p>'.display_reward($count, $change).'</p>';
          $count++;
        }
        $leaderboard_metric = get_config('playlyfe', 'course'.$course_id);
        if(!is_null($leaderboard_metric)) {
          //print_object($leaderboard_metric);
          $leaderboard = $pl->get('/runtime/leaderboards/'.$leaderboard_metric, array('player_id' => 'u'.$USER->id, 'cycle' => 'alltime', 'scope_id' => 'course'.$course_id));
          $html .= '<h3> Leaderboards </h3>';
          $html .= '<ul>';
          foreach($leaderboard['data'] as $player) {
            $score = $player['score'];
            $id = $player['player']['id'];
            $alias = $player['player']['alias'] or 'Null';
            $rank = $player['rank'];
            $list = explode('u', $id);
            $user = $DB->get_record('user', array('id' => $list[1]));
            $html .= "<li class='list-group-item'>";
            $html .= $OUTPUT->user_picture($user, array('size'=>50));
            $html .= "<b>$rank $alias $score</b></li>";
          }
        }
        $html .= '</div>';
        echo $html;
        set_config('u'.$USER->id.'_buffer', null, 'playlyfe');
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
