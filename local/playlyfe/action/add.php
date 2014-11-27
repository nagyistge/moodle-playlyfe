<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/action/add.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('actions')->get('add')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

$events = array (
  'course_created',
  'course_completed',
  'course_deleted',
  'course_updated',
  'groups_member_added',
  'groups_member_removed',
  'role_assigned',
  'role_unassigned',
  'user_enrolled',
  'user_logout',
  'user_updated',
  'assessable_submitted',
  'quiz_attempt_submitted'
);

$pl = local_playlyfe_sdk::get_pl();
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id'));
$metricsList = array();
foreach($metrics as $metric){
  array_push($metricsList, $metric['id']);
}

class metric_add_form extends moodleform {

    function definition() {
        global $events, $metricsList;
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Create an Action');
        $mform->addElement('text', 'id', 'Action ID');
        $mform->addRule('id', null, 'required', null, 'client');
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('text', 'name', 'Action Name');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_RAW);
        $mform->addElement('select', 'events_index', 'Event', $events);
        $mform->addRule('events_index', null, 'required', '' , 'client');
        $mform->setType('events_index', PARAM_RAW);

        $mform->addElement('select', 'metric_index', 'Metric', $metricsList);
        $mform->addRule('metric_index', null, 'required', '' , 'client');
        $mform->setType('metric_index', PARAM_RAW);

        $mform->addElement('text', 'value', 'Value');
        $mform->addRule('value', null, 'required', '' , 'client');
        $mform->setType('value', PARAM_INT);

        $mform->addElement('textarea', 'requires', 'Requires');
        $mform->addRule('requires', null, 'required', '' , 'client');
        $mform->setType('requires', PARAM_RAW);
        $this->add_action_buttons();
    }
}

$form = new metric_add_form();
if($form->is_cancelled()) {
    redirect(new moodle_url('/local/playlyfe/action/manage.php'));
} else if ($data = $form->get_data()) {
    print_object($data);
    $pl = local_playlyfe_sdk::get_pl();
    $event_mapping = json_decode(get_config('playlyfe', 'event_mapping'), true);
    $event_mapping[$events[$data->events_index]] = $data->id;
    set_config('event_mapping', json_encode($event_mapping), 'playlyfe');
    $rewards = array();
    $reward = array(
      'metric' => array(
        'id' => $metricsList[$data->metric_index],
        'type' => 'point'
      ),
      'value' => (string)$data->value,
      'verb' => 'set'
    );
    #(object)array()

    array_push($rewards, $reward);
    #if($data->requires)
    $set = array(
      'id' => $data->id,
      'name' => $data->name,
      'image' => 'default-set-action',
      'requires' => (object)array(),
      'rules' => array(
        array(
          'rewards' => $rewards,
          'requires' => (object)array()
        )
      )
    );
  try {
    $pl->post('/design/versions/latest/actions', array(), $set);
    redirect(new moodle_url('/local/playlyfe/action/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
