<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/metric/edit.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('metrics')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

class metric_edit_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Change Metric Name');
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('text', 'name', 'Metric Name');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_RAW);
        $this->add_action_buttons();
    }
}

$form = new metric_edit_form();
if($form->is_cancelled()) {
    redirect(new moodle_url('/local/playlyfe/metric/manage.php'));
} else if ($data = $form->get_data()) {
    $pl = local_playlyfe_sdk::get_pl();
    $metric = array(
      'name' => $data->name,
      'type' => 'point',
    );
  try {
    $pl->patch('/design/versions/latest/metrics/'.$data->id, array(), $metric);
    redirect(new moodle_url('/local/playlyfe/metric/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    $toform = array();
    $toform['id'] = optional_param('id', null, PARAM_TEXT);;
    $toform['name'] = optional_param('name', null, PARAM_TEXT);;
    $form->set_data($toform);
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
