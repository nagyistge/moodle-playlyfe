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
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
$submit_rule = get_rule($quiz->id, 'submitted', '', 'Quiz '.$quiz->name.' Submitted');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));

if (array_key_exists('submit', $_POST)) {
  $post = $_POST;
  if(array_key_exists('metrics', $post)) {
    $rules = array();
    $length = count($post['metrics']);
    for($i=0;$i<$length;$i++){
      $key= $submit_rule['id'].'_'.$i;
      $metrics = $post['metrics'][$key];
      $values = $post['values'][$key];
      $condition_types = array();
      if(array_key_exists('condition_types', $post) and array_key_exists($key, $post['condition_types'])) {
          $condition_types = $post['condition_types'][$key];
          $condition_operators = $post['condition_operators'][$key];
          $condition_values = $post['condition_values'][$key];
      }
      array_push($rules, array(
        'rewards' => create_reward($metrics, $values),
        'requires' => (object)create_requires($condition_types, $condition_operators, $condition_values)
      ));
    }
    $submit_rule['rules'] = $rules;
  }
  $id = $submit_rule['id'];
  unset($submit_rule['id']);
  try {
    $pl->patch('/design/versions/latest/rules/'.$id, array(), $submit_rule);
  }
  catch(Exception $e) {
    print_object($e);
  }
  if($quiz->timeclose > 0 or $quiz->timelimit > 0) {
    $bonus_rule = get_rule($quiz->id, 'bonus', '', 'Quiz Bonus');
    patch_rule($bonus_rule, $_POST);
  }
  redirect(new moodle_url('/local/playlyfe/quiz.php', array('cmid' => $cmid)));
} else {
  echo $OUTPUT->header();
  $form = new PForm($cm->name, 'quiz.php?cmid='.$cmid);
  $form->create_separator('Rewards on Quiz Completion', 'Give rewards when the user completes this quiz. Additionally add conditions to give the reward only when the user gets a particular score in the quiz');
  $form->create_rule_with_condition_table($submit_rule, $metrics);
  // if($quiz->timeclose > 0 or $quiz->timelimit > 0) {
  //   $bonus_rule = get_rule($quiz->id, 'bonus', '', 'Quiz Bonus');
  //   $form->create_separator('Reward for Early Completion Before '.date("D, d M Y H:i:s", $quiz->timeclose));
  //   $form->create_rule_table($bonus_rule, $metrics);
  // }
  $form->end();
  echo $OUTPUT->footer();
}
