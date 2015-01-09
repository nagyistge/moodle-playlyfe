<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/leaderboard.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->navigation->clear_cache();
$courses = $DB->get_records('course');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'name,id,type'));
global $CFG, $USER;
$html = '';

if(array_key_exists('course', $_POST) and array_key_exists('metric', $_POST)) {
  $course = $_POST['course'];
  $metric = $_POST['metric'];
  $page = $_POST['page'];
  $query = array(
    'player_id' => 'u'.$USER->id,
    'cycle' => 'alltime',
    'scope_id' => 'course'.$course,
    'skip' => 10*$page,
    'limit' => 10
  );
  if(array_key_exists('find_me', $_POST)) {
    $find_me = $_POST['find_me'];
    $query['ranking'] = 'relative';
    $query['entity_id'] = 'u'.$USER->id;
    $query['radius'] = 10;
  }
  try {
    $leaderboard = $pl->get('/runtime/leaderboards/'.$metric, $query);
  }
  catch(Exception $e) {
  }
  echo $OUTPUT->header();
  $course = $DB->get_record('course', array('id' => $course));
  $html .= '<h1> Leaderboards for '.$metric.' in course '.$course->fullname.' Page - '.($page+1).'</h1>';
  $html .= '<hr></hr><ul class="leaderboard-list">';
  foreach($leaderboard['data'] as $player) {
    $score = $player['score'];
    $id = $player['player']['id'];
    $alias = $player['player']['alias'] or 'Null';
    $rank = $player['rank'];
    $list = explode('u', $id);
    if($rank < 10) {
      $rank = '0'.$rank;
    }
    if($id === 'u'.$USER->id) {
      $html .= "<li class='leaderboard-player leaderboard-player-selected'>";
      $html .= '<div class="leaderboard-player-rank">'.$rank.'</div>';
    }
    else {
      $html .= "<li class='leaderboard-player'>";
      $html .= '<div class="leaderboard-player-rank">'.$rank.'</div>';
    }
    $user = $DB->get_record('user', array('id' => '2'));
    $html .= $OUTPUT->user_picture($user, array('size'=>75));
    //$user = $DB->get_record('user', array('id' => $list[1]));
    //$html .= $OUTPUT->user_picture($user, array('size'=>100));
    $html .= '<div class="leaderboard-player-score">'.$score.'</div>';
    $html .= '<div class="leaderboard-player-alias">'.$alias.'</div></li>';
  }
  if(count($leaderboard['data']) === 0) {
    $html .= 'The leaderboard is empty';
  }
  $html .= '</ul>';
  echo $html;
  echo $OUTPUT->footer();
}
else {
  $metricsList = array();
  $leaderboards = array();
  if($PAGE->course) {
    $leaderboards = get_leaderboards('course'.$PAGE->course->id.'_leaderboard');
  }
  $arr = array();
  foreach ($courses as $course) {
    $arr[$course->shortname] = $course->id;
  }
  foreach($metrics as  $metric) {
    if($metric['type'] === 'point') {
      $metricsList[$metric['name']] = $metric['id'];
    }
  }
  echo $OUTPUT->header();
  $form = new PFORM('Leaderboards', 'leaderboard.php');
  $form->create_separator('Course','Please select the course in which you would like to see the leaderboards');
  $form->create_select('course', $arr);
  $form->create_separator('Metric','Please select the metric for which you would like to view the leaderboard within the course');
  $form->create_select('metric', $metricsList);
  $form->create_separator('Options','Please select the options');
  $form->create_input('Page', 'page', '0', 'number', false);
  $form->create_checkbox('Find Me','find_me', true, false, false);
  $form->end();
  echo $OUTPUT->footer();
}
