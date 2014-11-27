<?php

require_once('sdk.php');

class block_playlyfe_observer {

    public static function category(\core\event\course_category_created $event) {
        global $CFG;
        $data = $event->get_data();
        print_object($data);
    }

    public static function create_player(\core\event\user_created $event) {
        global $CFG;
        $data = $event->get_data();
        $user_id = $data['objectid'];
        $pl = block_playlyfe_sdk::get_pl();
        $pl->post('/game/players', array(), array('alias' => 'Anon', 'id' => 'u'.$user_id));
        print_object($event);
    }

    public static function log_in(\core\event\user_loggedin $event) {
        global $CFG;
        $pl = block_playlyfe_sdk::get_pl();
        $data = $event->get_data();
        $user_id = $data['objectid'];
        $pl->post('/action/play', array('player_id' => 'u'.$user_id), array('id' => 'logged_in'));
    }

    public static function log_out(\core\event\user_loggedout $event) {
        global $CFG;
        $pl = block_playlyfe_sdk::get_pl();
        $data = $event->get_data();
        $user_id = $data['objectid'];
        $pl->post('/action/play', array('player_id' => 'u'.$user_id), array('id' => 'logged_out'));
    }
}
