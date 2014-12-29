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
$PAGE->set_url('/local/playlyfe/quiz.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
if($CFG->version <= 2012120311.00) {
  $PAGE->requires->js(new moodle_url('http://code.jquery.com/jquery-1.11.2.min.js'));
}
else {
  $PAGE->requires->jquery();
}
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/playlyfe/reward.js'));
$submit_rule = get_rule($quiz->id, 'submitted', '', 'Quiz Submitted');
$bonus_rule = get_rule($quiz->id, 'bonus', '', 'Quiz Bonus');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
$html = '';

if (array_key_exists('submit', $_POST)) {
  $cid = $submit_rule['id'];
  $bid = $bonus_rule['id'];
  if(array_key_exists($cid, $_POST['metrics'])) {
    patch_rule($submit_rule, $_POST['metrics'][$cid], $_POST['values'][$cid]);
  }
  if(array_key_exists($bid, $_POST['metrics'])) {
    patch_rule($bonus_rule, $_POST['metrics'][$bid], $_POST['values'][$bid]);
  }
  redirect(new moodle_url('/local/playlyfe/quiz.php', array('cmid' => $cmid)));
} else {
  $name = $cm->name;
  echo $OUTPUT->header();
  $html .= "<h1> $name </h1>";
  $html .= '<form action="quiz.php?cmid='.$cmid.'" method="post">';
  $html .= "<h2> Rewards on Quiz Completion </h2>";
  $html .= create_rule_table($submit_rule, $metrics);
  if($quiz->timeclose > 0 or $quiz->timelimit > 0) {
    $html .= '<h2> Bonus for Early Completion Before '.date("D, d M Y H:i:s", $quiz->timeclose).'</h2>';
    $html .= create_rule_table($bonus_rule, $metrics);
  }
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  echo $html;
  echo $OUTPUT->footer();
}
