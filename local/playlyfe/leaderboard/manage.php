<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/set/manage.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('leaderboards')->get('manage')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

$pl = local_playlyfe_sdk::get_pl();

$delete = optional_param('delete', null, PARAM_TEXT);
$id = optional_param('id', null, PARAM_TEXT);
if($id and $delete) {
  $pl->delete('/design/versions/latest/leaderboards/'.$id, array());
}

$html .= $OUTPUT->box_start('generalbox authsui');
$table = new html_table();
$table->head  = array('Name', 'ID', 'Description', 'For', 'Metric', '');
$table->colclasses = array('leftalign', 'leftalign', 'centeralign', 'rightalign');
$table->data  = array();
$table->attributes['class'] = 'admintable generaltable';
$table->id = 'manage_sets';

$leaderboards = $pl->get('/design/versions/latest/leaderboards', array('fields' => 'id,name,description,entity_type,metric.id'));
foreach($leaderboards as $leaderboard) {
  $delete = '<a href="manage.php?id='.$leaderboard['id'].'&delete=true'.'">Delete</a>';
  $table->data[] = new html_table_row(array($leaderboard['name'], $leaderboard['id'], $leaderboard['description'], $leaderboard['entity_type'], $leaderboard['metric']['id'], $delete));
}
$html .= html_writer::table($table);
$html .= $OUTPUT->box_end();
echo $OUTPUT->header();
echo '<h1>Leaderboards</h1>';
echo $html;
echo $OUTPUT->footer();
