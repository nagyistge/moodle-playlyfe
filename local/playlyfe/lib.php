<?php
  require_once('classes/sdk.php');

  $event_names = array(
    'course_completed',
    'user_enrolled',
    'user_logout',
    'assessable_submitted',
    'quiz_attempt_submitted'
  );

  function course_completed_handler($event) {
    global $USER;
    print_object($event);
    $id = $event->id;
    try {
      $pl = local_playlyfe_sdk::get_pl();
      $action = $pl->get('/design/versions/latest/actions/course_completed');
      foreach($action['rules'] as $rule) {
        if ($rule['requires']['context']['rhs'] == '"'.$id.'"') {
          $leaderboard_metric = get_config('playlyfe', 'course'.$id);
          $pl->post('/runtime/actions/course_completed/play', array('player_id' => 'u2'), array(
            'scopes' => array(
              array(
                'id' => $leaderboard_metric.'/'.'course'.$id,
                'entity_id' => 'u'.$USER->id
              )
            ),
            'variables' => array(
              'course_id' => ''.$id
            )
          ));
          //redirect(new moodle_url('/local/playlyfe/feedback.php?id='.$id));
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
    check_event($event, 'user_logout');
  }

  function user_enrolled_handler($event) {
    print_object($event);
    check_event($event, 'user_enrolled');
  }


  function user_created_handler($event) {
    $pl = local_playlyfe_sdk::get_pl();
    if (true) {  //for moodle 2.5
      $data = array('id' => 'u'.$event->id, 'alias' => $event->username, 'email' => $event->email);
    }
    else {
      $data = $event->get_data();
      $user_id = $data['objectid'];
      $data = array('alias' => 'Anon', 'id' => 'u'.$user_id);
    }
    $pl->post('/admin/players', array(), $data);
  }

  function local_playlyfe_extends_settings_navigation(settings_navigation $settingsnav, $context) {
      $sett = $settingsnav->get('root');
      if($sett != null) {
        $nodePlaylyfe = $sett->add('Gamification', null, null, null, 'playlyfe');

        $nodePlaylyfe->add('Client', new moodle_url('/local/playlyfe/client.php'), null, null, 'client', new pix_icon('t/edit', 'edit'));
        $nodePlaylyfe->add('Publish', new moodle_url('/local/playlyfe/publish.php'), null, null, 'publish', new pix_icon('t/edit', 'edit'));

        $nodePlaylyfe->add('Courses', new moodle_url('/local/playlyfe/course.php'), null, null, 'courses', new pix_icon('t/edit', 'edit'));

        $nodeMetric = $nodePlaylyfe->add('Metrics', null, null, null, 'metrics');
        $nodeMetric->add('Manage Metrics', new moodle_url('/local/playlyfe/metric/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeMetric->add('Add a new metric', new moodle_url('/local/playlyfe/metric/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));

        $nodeSet = $nodePlaylyfe->add('Set Badges', null, null, null, 'sets');
        $nodeSet->add('Manage sets', new moodle_url('/local/playlyfe/set/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeSet->add('Add a new set', new moodle_url('/local/playlyfe/set/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));
      }
  }

  function local_playlyfe_extends_navigation($navigation) {
    global $CFG, $PAGE, $USER;
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    // $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/playlyfe/page.js'));
    echo '<div id="dialog" title="Basic dialog">';
    echo '<div id="dialog-text"></div></div>';
    $pl = local_playlyfe_sdk::get_pl();
    $notifications = $pl->get('/runtime/notifications', array('player_id' => 'u'.$USER->id));
    print_object($notifications);
    echo 'HELLO';
    $nodeProfile = $navigation->add('Playlyfe Profile', new moodle_url('/local/playlyfe/profile.php'));
    $nodeNotifications = $navigation->add('Notifications', new moodle_url('/local/playlyfe/notification.php'));
  }
