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

function get_hue ($leaderboard, $rank) {
  $sort = 'descending';
  $hue = $sort === 'descending' ? 80 : 0;
  $mult = $sort === 'descending' ? -1 : +1;
  $diff = 80 / $leaderboard['total'];
  return $hue + $mult * $diff * ($rank - 1);
}

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
    $leaderboard = array('data' => array(), 'total' => 0);
  }
  echo $OUTPUT->header();
  $course = $DB->get_record('course', array('id' => $course));
  $html .= '
  <div id="pl-leaderboard" class="pl-page">
    <h1 class="page-title"></h1>
    <h5 class="page-subtitle">Page: '.($page + 1).'</h5>
    <div class="page-section">
      <div class="section-content">
        <ol class="pl-leaderboard list-unstyled">';

  foreach($leaderboard['data'] as $player) {
    $score = $player['score'];
    $id = $player['player']['id'];
    $alias = $player['player']['alias'] or 'Null';
    $rank = $player['rank'];
    $list = explode('u', $id);
    if($rank < 10) {
      $rank = '0'.$rank;
    }
    $user = $DB->get_record('user', array('id' => $list[1]));
    $hue = get_hue($leaderboard, $rank);

    $html .= '
          <li class="leaderboard-item">
            <div class="leaderboard-item-content">
              <div class="leaderboard-column markers">
                '.($rank === 1 ? '<i class="ldr-mark mark-leader"></i>' : '').'
                '.($USER->id === $id ? '<i class="ldr-mark mark-self" title="This is You!"></i>' : '').'
                '.$rank.'
              </div><!--
            Players
           --><div class="ldr-avatar leaderboard-column as-button" title="View '.$alias.'&#39;s Profile">
                '.$OUTPUT->user_picture($user, array('size'=>100)).'
                </div><!--
            Player Name
           --><div class="ldr-name leaderboard-column skew ellipsis as-button">
                <div class="skew reverse">'.$alias.'</div>
              </div><!--
            Score
           --><div class="ldr-score leaderboard-column skew" style="background-color: hsl('.$hue.', 60%, 55%)">
                <div class="skew reverse">'.$score.'</div>
              </div>
            </div>
          </li>';
  }
  $html .= '
        </ol>';

  if(count($leaderboard['data']) === 0) {
    $html .= '<div class="placeholder-content">This leaderboard '.($page > 0 ? 'page ': '').'is empty.</div>';
  }
  if($page >= 0 and $page < intval($leaderboard['total']/10)) {
    $_GET['page'] = $page + 1;
    $url = new moodle_url('/local/playlyfe/leaderboard.php', $_GET);
    $html .= '<div class="leaderboard-button">'.html_writer::link($url, 'Next Page').'</div>';
  }
  if($page > 0) {
    $_GET['page'] = $page - 1;
    $url = new moodle_url('/local/playlyfe/leaderboard.php', $_GET);
    $html .= '<div class="leaderboard-button">'.html_writer::link($url, 'Previous Page').'</div>';
  }
  $html .= '
      </div>
    </div>
  </div>';
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
  $form = new PForm('View Leaderboard', 'leaderboard.php', 'get', 'View Leaderboard');
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
