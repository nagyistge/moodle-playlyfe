<?php

  function local_playlyfe_extends_settings_navigation(settings_navigation $settingsnav, $context) {
      $sett = $settingsnav->get('root');
      if($sett != null) {
        $nodePlaylyfe = $sett->add('Gamification', null, null, null, 'playlyfe');

        $nodePlaylyfe->add('Client', new moodle_url('/local/playlyfe/client.php'), null, null, 'client', new pix_icon('t/edit', 'edit'));
        $nodeLeaderboard = $nodePlaylyfe->add('Leaderboards', new moodle_url('/local/playlyfe/leaderboard.php'), null, null, 'leaderboard', new pix_icon('t/edit', 'edit'));
        $nodeActions = $nodePlaylyfe->add('Actions', new moodle_url('/local/playlyfe/action.php'), null, null, 'action', new pix_icon('t/edit', 'edit'));

        $nodeMetric = $nodePlaylyfe->add('Metrics', null, null, null, 'metrics');
        $nodeMetric->add('Manage Metrics', new moodle_url('/local/playlyfe/metric/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeMetric->add('Add a new metric', new moodle_url('/local/playlyfe/metric/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));
        $nodeSet = $nodePlaylyfe->add('Set Badges');
        $nodeSet->add('Manage sets', new moodle_url('/local/playlyfe/badge/manage.php'), null, null, 'manage', new pix_icon('t/edit', 'edit'));
        $nodeSet->add('Add a new set', new moodle_url('/local/playlyfe/badge/add.php'), null, null, 'add', new pix_icon('t/edit', 'edit'));
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
