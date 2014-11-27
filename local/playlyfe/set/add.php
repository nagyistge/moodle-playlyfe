<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/set/add.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('sets')->get('add')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

class metric_add_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Create a Set');
        $mform->addElement('text', 'id', 'Set ID');
        $mform->addRule('id', null, 'required', null, 'client');
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('text', 'name', 'Set Name');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_RAW);
        $mform->addElement('text', 'items', 'Badges', 'Comma separated values');
        $mform->addRule('items', null, 'required', '' , 'client');
        $mform->setType('items', PARAM_RAW);
        $this->add_action_buttons();
    }
}

$form = new metric_add_form();
if($form->is_cancelled()) {
    redirect(new moodle_url('/local/playlyfe/set/manage.php'));
} else if ($data = $form->get_data()) {
    $pl = local_playlyfe_sdk::get_pl();
    $items = array();
    $items_string = explode(',', trim($data->items));
    foreach($items_string as $item) {
      array_push($items, array(
        'name' => $item,
        'max' => '1',
        'image' => '',
        'description' => ''
      ));
    }
    $set = array(
      'id' => $data->id,
      'name' => $data->name,
      'type' => 'set',
      'image' => 'default-set-metric',
      'constraints' => array(
        'items' => $items,
        'max_items' => 'Infinity'
      )
    );
  try {
    $pl->post('/design/versions/latest/metrics', array(), $set);
    redirect(new moodle_url('/local/playlyfe/set/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
