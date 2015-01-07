<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/metric/add.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('metrics')->get('add')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

if (array_key_exists('id', $_POST)) {
    $metric = array(
      'id' => $_POST['id'],
      'name' => $_POST['name'],
      'type' => 'point',
      'image' => 'default-point-metric',
      'description' => $_POST['description'],
      'constraints' => array(
        'default' => '0',
        'max' => 'Infinity',
        'min' => '0'
      )
    );
    $leaderboard = array(
      'id' => $_POST['id'],
      'name' => $_POST['name'],
      'type' => 'regular',
      'description' => '',
      'entity_type' => 'players',
      'scope' => array(
        'type' => 'custom'
      ),
      'metric' => array(
        'id' => $_POST['id'],
        'type' => 'point'
      )
    );
  try {
    if (strlen($_FILES['uploadedfile']['name']) > 0) {
       $metric['image'] = $pl->upload_image($_FILES['uploadedfile']['tmp_name']);
    }
    $pl->post('/design/versions/latest/metrics', array(), $metric);
    $pl->post('/design/versions/latest/leaderboards', array(), $leaderboard);
    redirect(new moodle_url('/local/playlyfe/metric/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    if (array_key_exists('id', $_GET)) {
      $metric = $pl->get('/design/versions/latest/metrics/'.$_GET['id'], array());
    }
    echo $OUTPUT->header();
    $form = new PForm('Metric');
    $form->create_file('Image', 'uploadedfile');
    $form->create_input('Name', 'name');
    $form->create_input('ID', 'id');
    $form->create_input('Description', 'description');
    $form->end();
    echo $OUTPUT->footer();
}
