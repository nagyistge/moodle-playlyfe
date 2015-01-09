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
$submit_rule = get_rule($quiz->id, 'submitted', '', 'Quiz Submitted');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));

if (array_key_exists('submit', $_POST)) {
  $cid = $submit_rule['id'];
  $requires = array();
  if(array_key_exists('metrics', $_POST)) {
    if(array_key_exists($cid, $_POST['metrics'])) {
      if($_POST['condition_type'] === 'score' and $_POST['condition_value']) {
        $requires = array(
          'type' => 'var',
          'context' => array (
            'lhs' => '$vars.score',
            'operator' => $_POST['condition_operator'],
            'rhs' => $_POST['condition_value']
          )
        );
      }
      patch_rule($submit_rule, $_POST['metrics'][$cid], $_POST['values'][$cid], $requires);
    }
    if($quiz->timeclose > 0 or $quiz->timelimit > 0) {
      $bonus_rule = get_rule($quiz->id, 'bonus', '', 'Quiz Bonus');
      $bid = $bonus_rule['id'];
      if(array_key_exists($bid, $_POST['metrics'])) {
        patch_rule($bonus_rule, $_POST['metrics'][$bid], $_POST['values'][$bid]);
      }
      else {
        patch_rule($bonus_rule, array(), array());
      }
    }
  }
  else {
    patch_rule($submit_rule, array(), array(), array());
  }
  redirect(new moodle_url('/local/playlyfe/quiz.php', array('cmid' => $cmid)));
} else {
  echo $OUTPUT->header();
  $form = new PFORM($cm->name, 'quiz.php?cmid='.$cmid);
  $form->create_separator('Rewards on Quiz Completion', 'Give rewards when the user completes this quiz. Additionally add conditions to give the reward only when the user gets a particular score');
  $form->create_conditions($submit_rule);
  $form->create_rule_table($submit_rule, $metrics);
  $PAGE->requires->js_init_call('init_conditions', array());
  if($quiz->timeclose > 0 or $quiz->timelimit > 0) {
    $bonus_rule = get_rule($quiz->id, 'bonus', '', 'Quiz Bonus');
    $form->create_separator('Reward for Early Completion Before '.date("D, d M Y H:i:s", $quiz->timeclose));
    $form->create_rule_table($bonus_rule, $metrics);
  }
  $form->end();
  echo $OUTPUT->footer();
}
