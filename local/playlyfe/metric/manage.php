<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/metric/manage.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('metrics')->get('manage')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

$delete = optional_param('delete', null, PARAM_TEXT);
$id = optional_param('id', null, PARAM_TEXT);
if($id and $delete) {
  $pl->delete('/design/versions/latest/metrics/'.$id, array());
  $pl->delete('/design/versions/latest/leaderboards/'.$id, array());
}

$table = new html_table();
$table->head  = array('Image', 'ID', 'Name', 'Description', '', '');
$table->colclasses = array('leftalign', 'centeralign', 'rightalign', 'rightalign', 'rightalign');
$table->data  = array();
$table->attributes['class'] = 'admintable generaltable';
$table->id = 'manage_metrics';

$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,name,type,description,image'));
foreach($metrics as $metric) {
  if($metric['type'] == 'point') {
    $edit = '<a href="edit.php?id='.$metric['id'].'">Edit</a>';
    $delete = '<a href="manage.php?id='.$metric['id'].'&delete=true'.'">Delete</a>';
    $item_image = '<img src="../image.php?image_id='.$metric['image'].'"></img>';
    $table->data[] = new html_table_row(array($item_image, $metric['id'], $metric['name'], $metric['description'], $edit, $delete));
    // $pl->post('/admin/leaderboards/'.$metric['id'].'/course1', array());
    // $pl->post('/runtime/actions/aaa/play', array('player_id' => 'u2'), array(
    //   'scopes' => array(
    //     array(
    //       'entity_id' => 'u2',
    //       'id' => 'erer/course1'
    //     )
    //   )
    // ));
  }
}
$html .= html_writer::table($table);
echo $OUTPUT->header();
echo '<h1>Metrics</h1>';
echo $html;
echo $OUTPUT->footer();
