<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/metric/edit.php');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('metrics')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

define("UPLOAD_DIR", "/images/");

if (array_key_exists('id', $_POST)) {
  $id = $_POST['id'];
  $metric = $pl->get('/design/versions/latest/metrics/'.$id, array());
  unset($metric['id']);
  $metric['name'] = $_POST['name'];
  $metric['description'] = $_POST['description'];
  try {
    if (strlen($_FILES['uploadedfile']['name']) > 0) {
      $myFile = $_FILES['uploadedfile']['tmp_name'];
      $metric['image'] = $pl->upload_image($myFile);
    }
    $pl->patch('/design/versions/latest/metrics/'.$_POST['id'], array(), $metric);
    redirect(new moodle_url('/local/playlyfe/metric/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    $id = required_param('id', PARAM_TEXT);
    $metric = $pl->get('/design/versions/latest/metrics/'.$id, array());
    echo $OUTPUT->header();
    $form = new PForm('Editing Metric - '.$metric['name']);
    $form->create_file('Image', 'uploadedfile');
    $form->create_input('Name', 'name', $metric['name']);
    $form->create_hidden('id', $id);
    $form->create_input('Description', 'description', $metric['description']);
    $form->end();
    echo $OUTPUT->footer();
}
