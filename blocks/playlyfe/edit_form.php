<?php
require_once('classes/sdk.php');
class block_playlyfe_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $PAGE, $COURSE;
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', 'Set Block Details');

        // A sample string variable with a default value.
        $mform->addElement('text', 'config_title', 'Title');
        $mform->setDefault('config_title', 'Title');
        $mform->setType('config_title', PARAM_TEXT);

        $types = array('0' => 'Profile', '2' => 'Players');

        if($PAGE->context->contextlevel == 50) {
          $types['1'] = 'Leaderboard';
        }

        $mform->addElement('select', 'config_type', 'Type', $types);
        $mform->setDefault('config_type', 0);

        if($PAGE->context->contextlevel == 50) {
          $pl = block_get_pl();
          $data = json_decode(get_config('playlyfe', 'course'.$COURSE->id.'_leaderboard'));
          if(!is_array($data)) {
            $data = array();
          }
          // $leaderboards = $pl->get('/design/versions/latest/leaderboards', array('fields' => 'id'));
          // $leaderboardList = array();
          // foreach($leaderboards as $leaderboard){
          //   array_push($leaderboardList, $leaderboard['id'].''.$COURSE->id);
          // }

          $mform->addElement('hidden', 'config_scope', $COURSE->id);
          $mform->setDefault('config_scope', $COURSE->id);
          $mform->setType('config_scope', PARAM_TEXT);

          $mform->addElement('header', 'configheader', 'If type is Leaderboard Select the Metric');
          $mform->addElement('select', 'config_metric', 'Metric', $data);
        }
    }
}
