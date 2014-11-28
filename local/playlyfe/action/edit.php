<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/set/edit.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('sets')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

$pl = local_playlyfe_sdk::get_pl();
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id'));
$metricsList = array();
foreach($metrics as $metric){
  array_push($metricsList, $metric['id']);
}

class set_edit_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Change Set Name');
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('text', 'name', 'Set Name');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_RAW);

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

$form = new set_edit_form();
if($form->is_cancelled()) {
    redirect(new moodle_url('/local/playlyfe/action/manage.php'));
} else if ($data = $form->get_data()) {
    $pl = local_playlyfe_sdk::get_pl();
  try {
    $pl->patch('/design/versions/latest/actions/'.$data->id, array(), $action);
    redirect(new moodle_url('/local/playlyfe/action/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    $id = optional_param('id', null, PARAM_TEXT);
    $action = $pl->get('/design/versions/latest/actions/'.$id);
    $form->set_data($action);
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
