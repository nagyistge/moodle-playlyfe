<?php
  require_once('classes/sdk.php');

  function check_event($event, $event_name) {
    global $USER;
    #print_object($USER);
    #print_object($event);
    $pl = local_playlyfe_sdk::get_pl();
    $actions = $pl->get('/design/versions/latest/actions', array('fields' => 'id'));
    $actionsList = array();
    foreach($actions as $action){
      array_push($actionsList, $action['id']);
    }
    if(in_array($actionsList, $event_name)) {
      $data = array('player_id' => 'u'.$USER->id);
      try {
        $pl->post('/runtime/actions/'.$event_name.'/play', $data, (object)array());
      }
      catch(Exception $e) {
        print_object($e);
      }
    }
  }

  function course_completed_handler($event) {
    check_event($event, 'course_completed');
  }

  function user_logout_handler($event) {
    check_event($event, 'user_logout');
  }

  function user_deleted_handler($event) {
    check_event($event, 'user_deleted');
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
        $nodeLeaderboard = $nodePlaylyfe->add('Leaderboards', null, null, null, 'leaderboards', null);
        $nodeLeaderboard->add('Manage Leaderboards', new moodle_url('/local/playlyfe/leaderboard/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeLeaderboard->add('Add a new leaderboard', new moodle_url('/local/playlyfe/leaderboard/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));

        $nodeAction = $nodePlaylyfe->add('Actions', null, null, null, 'actions', null);
        $nodeAction->add('Manage Actions', new moodle_url('/local/playlyfe/action/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeAction->add('Add a new action', new moodle_url('/local/playlyfe/action/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));

        $nodeMetric = $nodePlaylyfe->add('Metrics', null, null, null, 'metrics');
        $nodeMetric->add('Manage Metrics', new moodle_url('/local/playlyfe/metric/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeMetric->add('Add a new metric', new moodle_url('/local/playlyfe/metric/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));

        $nodeSet = $nodePlaylyfe->add('Set Badges', null, null, null, 'sets');
        $nodeSet->add('Manage sets', new moodle_url('/local/playlyfe/set/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeSet->add('Add a new set', new moodle_url('/local/playlyfe/set/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));
      }
      #print_object($sett->get_children_key_list());
      #new pix_icon('t/addcontact', $strfoo)
      // global $CFG, $PAGE;
      // // Only add this settings item on non-site course pages.
      // if (!$PAGE->course or $PAGE->course->id == 1) {
      //     return;
      // }
      // // Only let users with the appropriate capability see this settings item.
      // if (!has_capability('moodle/backup:backupcourse', context_course::instance($PAGE->course->id))) {
      //     return;
      // }

      // if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
      //     $strfoo = get_string('foo', 'local_playlyfe');
      //     $url = new moodle_url('/local/playlyfe/foo.php', array('id' => $PAGE->course->id));
      //     $foonode = navigation_node::create(
      //         $strfoo,
      //         $url,
      //         navigation_node::NODETYPE_LEAF,
      //         'playlyfe',
      //         'playlyfe',
      //         new pix_icon('t/addcontact', $strfoo)
      //     );
      //     if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
      //         $foonode->make_active();
      //     }
      //     $settingnode->add_node($foonode);
      // }
  }

  // function local_playlyfe_extends_navigation($navigation) {
  //   $nodeFoo = $navigation->add('Foo', new moodle_url('/local/playlyfe/'));
  // }
