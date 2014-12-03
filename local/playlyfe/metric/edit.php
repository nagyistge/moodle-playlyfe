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
$pl = local_playlyfe_sdk::get_pl();

if (array_key_exists('id', $_POST)) {
  $metric = array(
    'name' => $_POST['name'],
    'description' => $_POST['description'],
    'type' => 'point'
  );
  try {
    //if(!is_null($_FILES['uploadedfile']['name'])) {
    //  $image = $pl->post('/design/images', array());
    //  $metric['image'] = $image['id'];
    //}
    $pl->patch('/design/versions/latest/metrics/'.$_POST['id'], array(), $metric);
    redirect(new moodle_url('/local/playlyfe/metric/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    $id = required_param('id', PARAM_TEXT);
    $metric = $pl->get('/design/versions/latest/metrics/'.$id, array());
    $metric_name = $metric['name'];
    echo $OUTPUT->header();
    $html .= "<h1> Editing Metric - $metric_name </h1>";
    $html .= '<form enctype="multipart/form-data" action="edit.php" method="post">';
    $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="500000000" />'; //500kb is 500000
    $html .= '<p>Metric Image: <input type="file" name="uploadedfile" /></p>';
    $html .= '<p>Metric Name: <input type="text" name="name" value="'.$metric_name.'"/></p>';
    $html .= '<input type="hidden" name="id" value="'.$id.'"/>';
    $html .= '<p>Metric Description: <input type="text" name="description" value="'.$metric['description'].'"/></p>';
    $html .= '<input type="submit" name="submit" value="Submit" />';
    $html .= '</form>';
    echo $html;
    echo $OUTPUT->footer();
}
