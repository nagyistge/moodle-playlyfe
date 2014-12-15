<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/eventslib.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/profile.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->navigation->clear_cache();
$html = '';

global $CFG, $USER;

$pl = local_playlyfe_sdk::get_pl();

$profile = $pl->get('/runtime/player', array('player_id' => 'u'.$USER->id, 'detailed' => 'true'));
$html .= '<h1>Alias: '.$profile['alias'].'</h1>';
$html = $html.'<h1>Scores</h1>';
if(count($profile['scores']) == 0){
  $html .= '</ul> You Have no scores';
}
else {
  foreach($profile['scores'] as $score) {
    $score_id = $score['metric']['id'];
    $score_type = $score['metric']['type'];
    $html .= '<h3><img src="image_def.php?metric='.$score_id.'"></img>';
    if($score_type == 'point') {
      $score_value = $score['value'];
      $html .= "$score_id <h2>$score_value</h2>";
    }
    else if($score_type == 'set') {
      $html .= "$score_id</h3>";
      $table = new html_table();
      $table->head  = array('Image', 'Name', 'Description');
      $table->colclasses = array('leftalign', 'leftalign');
      $table->data  = array();
      $table->attributes['class'] = 'admintable generaltable';
      $table->id = 'profile_sets';

      $table2 = new html_table();
      $table2->head  = array('Image', 'Name', 'Description', 'Count');
      $table2->colclasses = array('centeralign' ,'leftalign', 'leftalign', 'centeralign');
      $table2->data  = array();
      $table2->attributes['class'] = 'admintable generaltable';
      $table2->id = 'profile_sets';

      foreach($score['value'] as $value) {
        $item_name = $value['name'];
        $item_image = '<img src="image_def.php?metric='.$score_id.'&item='.$item_name.'"></img>';
        if($value['count'] == 0){
          $table->data[] = new html_table_row(array($item_image, $item_name, $value['description']));
        }
        else {
          $table2->data[] = new html_table_row(array($item_image, $item_name, $value['description'], $value['count']));
        }
      }
      if(count($table->data) > 0){
        $html .= '<h2> To be Achieved </h2>';
        $html .= html_writer::table($table);
      }
      if(count($table2->data) > 0){
        $html .= '<h2> Achieved </h2>';
        $html .= html_writer::table($table2);
      }
    }
  }
}
echo $OUTPUT->header();
echo '<div class="userprofile">';
echo '<h1>Profile Page<h1>';
echo '<div class="userprofilebox clearfix"><div class="profilepicture">';
echo $OUTPUT->user_picture($USER, array('size'=>100));
echo '</div>';
echo '</div>';
echo '</div>';
echo $html;
echo $OUTPUT->footer();
// $event_data = new stdClass();
// $event_data->id = 14;
// $event_data->course = 14;
// $event_data->userid = 14;
// events_trigger('course_completed', $event_data);
