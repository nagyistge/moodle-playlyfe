<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
require_login();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$context = context_course::instance($course->id);
$PAGE->set_course($course);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/playlyfe/course.php');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
$criteria = $DB->get_record('course_completion_criteria', array('course' => $id, 'criteriatype' => 2));
$completed_rule = get_rule($id, 'completed', 'course', 'Course '.$course->shortname. ' Completed');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'name,id,type,constraints'));

if (array_key_exists('submit', $_POST)) {
  patch_rule($completed_rule, $_POST);
  if($criteria and $criteria->timeend > 0) {
    $bonus_rule = get_rule($id, 'bonus', 'course', 'Course '.$course->shortname. ' Bonus');
    patch_rule($bonus_rule, $_POST);
  }
  redirect(new moodle_url('/local/playlyfe/course.php', array('id' => $id)));
} else {
  echo $OUTPUT->header();
  //$html .= '<ul class="course-list list-unstyled">';
  if($course->enablecompletion) {
    $form = new PForm($course->fullname, 'course.php?id='.$id);
    $form->create_separator('Course Completion Rewards', 'Give rewards to users who complete this course');
    $form->create_rule_table($completed_rule, $metrics);
    // 2 for timeend criteria
    if($criteria and $criteria->timeend > 0) {
      $bonus_rule = get_rule($id, 'bonus', 'course', 'Course '.$course->shortname. ' Bonus');
      $form->create_separator('Early Bird Bonus', 'Give rewards to users who complete this course before the deadline'.date("D, d M Y H:i:s", $criteria->timeend));
      $form->create_rule_table($bonus_rule, $metrics);
    }
    $form->end();
  }
  else {
    echo 'This course does\'nt have course completion enabled. Please Enable Course Completion in the the Course Settings Page to add Gamification to this Course';
  }
  echo $OUTPUT->footer();
}
