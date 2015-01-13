<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/set/manage.php');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('sets')->get('manage')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

$delete = optional_param('delete', null, PARAM_TEXT);
$id = optional_param('id', null, PARAM_TEXT);
if($id and $delete) {
  $pl->delete('/design/versions/latest/metrics/'.$id, array());
}

$table = new html_table();
$table->head  = array('Image', 'ID', 'Name',  'Description', 'Badges', '', '');
$table->colclasses = array('centeralign', 'leftalign', 'leftalign', 'centeralign', 'rightalign');
$table->data  = array();
$table->attributes['class'] = 'pl-table admin-table';
$table->id = 'manage_sets';

$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,name,type,description,image,constraints.items'));
foreach($metrics as $metric) {
  if($metric['type'] == 'set') {
    $edit = '<a href="edit.php?id='.$metric['id'].'">Edit</a>';
    $delete = '<a href="manage.php?id='.$metric['id'].'&delete=true'.'">Delete</a>';
    $set_image = '<img src="../image.php?image_id='.$metric['image'].'"></img>';
    $table3 = new html_table();
    $table3->head  = array('Image', 'Name', 'Description', 'Max');
    $table3->colclasses = array('centeralign', 'leftalign', 'rightalign');
    $table3->data  = array();
    $table3->attributes['class'] = 'pl-table admin-table';
    foreach($metric['constraints']['items'] as $item){
      $item_image = '<img src="../image.php?image_id='.$item['image'].'"></img>';
      $table3->data[] = new html_table_row(array($item_image, $item['name'], $item['description'], $item['max']));
    }
    $table->data[] = new html_table_row(array($set_image, $metric['id'], $metric['name'], $metric['description'], html_writer::table($table3), $edit, $delete));
  }
}
$html .= html_writer::table($table);
echo $OUTPUT->header();
echo '<b>Sets</b><hr></hr>';
echo $html;
echo $OUTPUT->footer();
