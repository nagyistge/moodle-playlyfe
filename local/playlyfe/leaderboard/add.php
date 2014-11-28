<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/leaderboard/add.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('leaderboards')->get('add')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

$pl = local_playlyfe_sdk::get_pl();
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type'));
$metricsList = array();
foreach($metrics as $metric){
  if($metric['type'] == 'point') {
    array_push($metricsList, $metric['id']);
  }
}

class leaderboard_add_form extends moodleform {

    function definition() {
      global $metricsList;
      $mform =& $this->_form;
      $mform->addElement('header','displayinfo', 'Create a Leaderboard');
      $mform->addElement('text', 'id', 'Leaderboard ID');
      $mform->addRule('id', null, 'required', null, 'client');
      $mform->setType('id', PARAM_RAW);
      $mform->addElement('text', 'name', 'Leaderboard Name');
      $mform->addRule('name', null, 'required', null, 'client');
      $mform->setType('name', PARAM_RAW);
      $mform->addElement('textarea', 'description', 'Description');
      $mform->addRule('description', null, 'required', '' , 'client');
      $mform->setType('description', PARAM_RAW);

      $mform->addElement('select', 'metric_index', 'Metric', $metricsList);
      $mform->addRule('metric_index', null, 'required', '' , 'client');
      $mform->setType('metric_index', PARAM_RAW);

      $mform->addElement('select', 'entity_index', 'For', array('players', 'teams'));
      $mform->addRule('entity_index', null, 'required', '' , 'client');
      $mform->setType('entity_index', PARAM_RAW);
      $this->add_action_buttons();
    }
}

$form = new leaderboard_add_form();

if($form->is_cancelled()) {
    redirect(new moodle_url('/local/playlyfe/leaderboard/manage.php'));
} else if ($data = $form->get_data()) {
    $leaderboard = array(
      'id' => $data->id,
      'name' => $data->name,
      'type' => 'regular',
      'description' => $data->description,
      'entity_type' => 'players',
      'scope' => array(
        'type' => 'game'
      ),
      'metric' => array(
        'id' => $metricsList[$data->metric_index],
        'type' => 'point'
      )
    );
  try {
    $pl->post('/design/versions/latest/leaderboards', array(), $leaderboard);
    redirect(new moodle_url('/local/playlyfe/leaderboard/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
