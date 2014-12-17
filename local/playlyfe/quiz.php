<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
require_login();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$cmid = required_param('cmid', PARAM_INT);
list($quiz, $cm) = get_module_from_cmid($cmid);
$course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
$context = context_module::instance($cmid);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/playlyfe/course.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/playlyfe/reward.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/playlyfe/quiz.js'));
$pl = local_playlyfe_sdk::get_pl();
$action = $pl->get('/design/versions/latest/actions/quiz_completed');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
$html = '';

if (array_key_exists('id', $_POST)) {
  $id = $_POST['id'];
  $action = patch_action($action, $metrics, $_POST, 'quiz_id');
  try {
    $pl->patch('/design/versions/latest/actions/quiz_completed', array(), $action);
  }
  catch(Exception $e) {
    print_object($e);
  }
  if(array_key_exists('leaderboard_metric', $_POST)) {
    $leaderboard_metric = $_POST['leaderboard_metric'];
    set_config('quiz'.$id, $leaderboard_metric, 'playlyfe');
    $pl->post('/admin/leaderboards/'.$leaderboard_metric.'/quiz'.$id, array());
  }
  redirect(new moodle_url('/local/playlyfe/quiz.php', array('cmid' => $id)));
} else {
  $rewards = array();
  foreach($action['rules'] as $rule) {
    if ($rule['requires']['context']['rhs'] == $cmid) {
      $rewards = $rule['rewards'];
    }
  }
  $data = array(
    'metrics' => $metrics,
    'leaderboard' => get_config('playlyfe', 'quiz'.$cmid),
    'rewards' => $rewards
  );
  $name = $cm->name;
  echo $OUTPUT->header();
  $html .= "<h1> $name </h1>";
  $html .= '<form id="mform1" action="quiz.php" method="post">';
  $html .= '<input name="id" type="hidden" value="'.$cmid.'"/>';
  $html .= '<input name="cmid" type="hidden" value="'.$cmid.'"/>';
  $html .= '<h2> Enable Leaderboard </h2>';
  $html .= '<div id="leaderboard">';
  $html .= '<input id="leaderboard_enable" name="leadeboard" type="checkbox" />';
  $html .= '</div>';
  $html .= "<h2> Rewards on Quiz Completion </h2>";
  $html .= '<table id="reward" class="admintable generaltable">';
  $html .= '<thead>';
  $html .= '<tr>';
  $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Metric</th>';
  $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Value</th>';
  $html .= '</tr>';
  $html .= '</thead>';
  $html .= '<tbody>';
  $html .= '</tbody>';
  $html .= '</table>';
  $html .= '<p><button type="button" id="add">Add Reward</button></p>';
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  echo $html;
  $PAGE->requires->js_init_call('setup', array($data));
  echo $OUTPUT->footer();
}
