<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/notification.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->navigation->clear_cache();
$html = '<h1> Your Notifications </h1>';
$notifications = $pl->get('/runtime/notifications', array('player_id' => 'u'.$USER->id));
$notifications['data'] = array_reverse($notifications['data']);
$count = 1;
$ids = array();

global $DB;

function display_change($change, $course_name) {
  global $count;
  $text = $count.'';
  $metric= $change['metric'];
  $delta = $change['delta'];
  $text .= '<img src="image_def.php?metric='.$metric['id'].'&size=large"></img>';
  if ($metric['type'] == 'point') {
    $value = $delta['new'] - $delta['old'];
  }
  else {
    foreach($delta as $key => $value) {
      $value = ($value['new'] - $value['old']).' x '.$key;
      $value .= '     <img src="image_def.php?metric='.$metric['id'].'&size=medium&item='.$key.'"></img>    ';
    }
  }
  $text .= 'You have gained '.$value.' '.$metric['name'].' '.$course_name;
  $count++;
  return $text;
}
foreach($notifications['data'] as $notification) {
  if ($notification['seen'] == false) {
    array_push($ids, $notification['id']);
  }
  if ($notification['event'] == 'custom_rule') {
    foreach($notification['changes'] as $change) {
      $title = $notification['rule']['name'];
      $html .= '<p>'.display_change($change, $title).' on '.date(strtotime($notification['timestamp'])).'</p>';
    }
  }
}

if(count($ids) > 0) {
  try {
    $pl->post('/runtime/notifications', array('player_id' => 'u'.$USER->id), array('ids' => $ids));
  }
  catch(Exception $e) {
    print_object($e);
  }
}
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
