<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/leaderboard.php');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->navigation->clear_cache();
$courses = $DB->get_records('course');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'name,id,type'));
global $CFG, $USER;
$html = '';

if(array_key_exists('course', $_GET) and array_key_exists('metric', $_GET)) {
  $course = $_GET['course'];
  $metric = $_GET['metric'];
  $page = $_GET['page'];
  $query = array(
    'player_id' => 'u'.$USER->id,
    'cycle' => 'alltime',
    'scope_id' => 'course'.$course,
    'skip' => 10*$page,
    'limit' => 10
  );
  if(array_key_exists('find_me', $_GET)) {
    $find_me = $_GET['find_me'];
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
    $html .= "<li class='leaderboard-player'>";
    $html .= '<div class="leaderboard-player-rank">'.$rank.'</div>';
    $user = $DB->get_record('user', array('id' => $list[1]));
    $html .= $OUTPUT->user_picture($user, array('size'=>100));
    $html .= '<div class="leaderboard-player-score">'.$score.'</div>';
    if($id == 'u'.$USER->id) {
      $html .= '<div class="leaderboard-player-alias selected">'.$alias.'</div></li>';
    }
    else {
      $html .= '<div class="leaderboard-player-alias">'.$alias.'</div></li>';
    }
  }
  $html .= '</ul>';
  if(count($leaderboard['data']) === 0) {
    if($page > 0) {
      $html .= '<h4>This Leaderboard Page is empty. Please Go back to a previous page</h4>';
    }
    else {
      $html .= '<h4>The leaderboard is empty</h4>';
    }
  }
  if($page >= 0 and $page < intval($leaderboard['total']/10)) {
    $_GET['page'] = $page+1;
    $url = new moodle_url('/local/playlyfe/leaderboard.php', $_GET);
    $html .= '<div class="leaderboard-button">'.html_writer::link($url, 'Next Page').'</div class="leadeboard-button">';
  }
  if($page > 0) {
    $_GET['page'] = $page-1;
    $url = new moodle_url('/local/playlyfe/leaderboard.php', $_GET);
    $html .= '<div class="leaderboard-button">'.html_writer::link($url, 'Previous Page').'</div class="leadeboard-button">';
  }
  echo $html;
  echo $OUTPUT->footer();
}
else {
  $metricsList = array();
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
  $form = new PForm('Leaderboards', 'leaderboard.php', 'get');
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
