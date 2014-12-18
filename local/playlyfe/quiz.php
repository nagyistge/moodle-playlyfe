<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
require_login();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$cmid = required_param('cmid', PARAM_INT);
list($quiz, $cm) = get_cmid($cmid);
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
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/playlyfe/reward.js'));
$action = $pl->get('/design/versions/latest/actions/quiz_completed');
$action2 = $pl->get('/design/versions/latest/actions/quiz_bonus');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
$html = '';

if (array_key_exists('id', $_POST)) {
  $id = $_POST['id'];
  $id_bonus = $id.'_bonus';
  $action = patch_action('quiz_id', $action, $id, $_POST['metrics'][$id], $_POST['values'][$id]);
  if(array_key_exists($id_bonus, $_POST['metrics'])) {
    $action2 = patch_action('quiz_id', $action2, $id, $_POST['metrics'][$id_bonus], $_POST['values'][$id_bonus]);
  }
  try {
    $pl->patch('/design/versions/latest/actions/quiz_completed', array(), $action);
    if(array_key_exists($id_bonus, $_POST['metrics'])) {
      $pl->patch('/design/versions/latest/actions/quiz_bonus', array(), $action2);
    }
    redirect(new moodle_url('/local/playlyfe/quiz.php', array('cmid' => $id)));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
  $name = $cm->name;
  echo $OUTPUT->header();
  $html .= "<h1> $name </h1>";
  $html .= '<form id="mform1" action="quiz.php" method="post">';
  $html .= '<input name="id" type="hidden" value="'.$cmid.'"/>';
  $html .= '<input name="cmid" type="hidden" value="'.$cmid.'"/>';
  $html .= "<h2> Rewards on Quiz Completion </h2>";
  $html .= create_reward_table($cmid, $cmid, $metrics, $action);
  if($quiz->timeclose > 0 or $quiz->timelimit >0) {
    $html .= '<h2> Bonus for Early Completion Before '.date("D, d M Y H:i:s", $quiz->timeclose).'</h2>';
    $html .= create_reward_table($cmid.'_bonus', $cmid, $metrics, $action2);
  }
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  echo $html;
  echo $OUTPUT->footer();
}
