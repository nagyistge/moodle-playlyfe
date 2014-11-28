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
$pl = local_playlyfe_sdk::get_pl();

$delete = optional_param('delete', null, PARAM_TEXT);
$id = optional_param('id', null, PARAM_TEXT);
if($id and $delete) {
  $pl->delete('/design/versions/latest/actions/'.$id, array());;
}

function operator($op){
  switch($op) {
    case 'eq': return 'Equal To';
    case 'neq': return 'Not Equal To';
    case 'lt': return 'Lesser Than';
    case 'le': return 'Lesser Than And Equal To';
    case 'gt': return 'Greatar Than';
    case 'ge': return 'Greatar Than And Equal To';
  }
}

$actions = $pl->get('/design/versions/latest/actions', array('fields' => 'id,name,rules'));

foreach($actions as $action) {
  $html .= $OUTPUT->box_start('generalbox authsui');
  $html .= '<h3>'.$action['id'].'</h3>';
  $html .= '<a href="edit.php?id='.$action['id'].'">Edit</a> | ';
  $html .= '<a href="manage.php?id='.$action['id'].'&delete=true'.'">Delete</a>';
  $table = new html_table();
  $table->head  = array('Requires', 'Rewards');
  $table->colclasses = array('leftalign', 'rightalign');
  $table->data  = array();
  $table->attributes['class'] = 'admintable generaltable';
  $table->id = $action['id'];
  foreach($action['rules'] as $rule) {
    $table2 = new html_table();
    $table2->head  = array('Metric', 'Verb', 'Value');
    $table2->colclasses = array('leftalign', 'rightalign');
    $table2->data  = array();
    $table2->attributes['class'] = 'admintable generaltable';
    foreach($rule['rewards'] as $reward) {
      if($reward['metric']['type'] == 'point') {
        $table2->data[] = new html_table_row(array($reward['metric']['id'], $reward['verb'], $reward['value']));
      }
      else {
        $table3 = new html_table();
        $table3->head  = array('Name', 'Count');
        $table3->colclasses = array('leftalign', 'rightalign');
        $table3->data  = array();
        $table3->attributes['class'] = 'admintable generaltable';
        foreach($reward['value'] as $name => $count){
          $table3->data[] = new html_table_row(array($name, $count));
        }
        $table2->data[] = new html_table_row(array($reward['metric']['id'], $reward['verb'], html_writer::table($table3)));
      }
    }
    $requires = $rule['requires'];
    $requires_text = '';
    if(isset($requires['type']) and $requires['type'] == 'metric') {
      $requires_text .= 'The player has Metric '.$requires['context']['id'];
      $requires_text .= ' and its value should be '.operator($requires['context']['operator']);
      $requires_text .= ' '.$requires['context']['value'];
    }
    $table->data[] = new html_table_row(array($requires_text, html_writer::table($table2)));
  }
  $html .= html_writer::table($table);
  $html .= $OUTPUT->box_end();
}
echo $OUTPUT->header();
echo '<h1>Actions</h1>';
echo $html;
echo $OUTPUT->footer();
#$OUTPUT->pix_icon('t/show', $txt->enable, 'moodle', array('class' => 'iconsmall')));
// if (!$DB->insert_record('block_simplehtml', $fromform)) {
//     print_error('inserterror', 'block_simplehtml');
// }
print_object(get_config('playlyfe', 'actions'));
