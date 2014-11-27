<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/form/select.php');
require_once(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/action/manage.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('actions')->get('manage')->make_active();
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

$delete = optional_param('delete', null, PARAM_TEXT);
$id = optional_param('id', null, PARAM_TEXT);
if($id and $delete) {
  $pl->delete('/design/versions/latest/actions/'.$id, array());;
}
$html .= $OUTPUT->box_start('generalbox authsui');
$table = new html_table();
$table->head  = array('Name', 'ID', 'Event', 'Metric/Set', 'Value', '', '');
$table->colclasses = array('leftalign', 'centeralign', 'rightalign');
$table->data  = array();
$table->attributes['class'] = 'admintable generaltable';
$table->id = 'manage_actions';

$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,name,type'));

$event_mapping = json_decode(get_config('playlyfe', 'event_mapping'), true);
foreach($event_mapping as $event_name => $value){
  $key = array_search($event_name, $events);
  if($key!==false){
    unset($events[$key]);
  }
}

$select = new MoodleQuickForm_select('Select', 'Select', $events);

$actions = $pl->get('/design/versions/latest/actions', array('fields' => 'id,name,rules'));

foreach($actions as $action) {
  $metric = $action['rules'][0]['rewards'][0]['metric']['id'];
  $verb = $action['rules'][0]['rewards'][0]['verb'];
  $value = $action['rules'][0]['rewards'][0]['value'];
  $edit = '<a href="edit.php?name='.$action['name'].'&id='.$action['id'].'">Edit</a>';
  $delete = '<a href="manage.php?id='.$action['id'].'&delete=true'.'">Delete</a>';
  $added = false;
  foreach($event_mapping as $event_name => $action_id){
    if($action_id == $action['id']){
      $table->data[] = new html_table_row(array($action['name'], $action['id'], $event_name, $metric, $value, $edit, $delete));
      $added = true;
      break;
    }
  }
  if(!$added){
    $table->data[] = new html_table_row(array($action['name'], $action['id'], $select->toHtml(), $metric, $value, $edit, $delete));
  }
}
$html .= html_writer::table($table);
$html .= $OUTPUT->box_end();
echo $OUTPUT->header();
echo '<h1>Actions</h1>';
echo $html;
echo $OUTPUT->footer();
#$OUTPUT->pix_icon('t/show', $txt->enable, 'moodle', array('class' => 'iconsmall')));
// if (!$DB->insert_record('block_simplehtml', $fromform)) {
//     print_error('inserterror', 'block_simplehtml');
// }
print_object(get_config('playlyfe', 'event_mapping'));
