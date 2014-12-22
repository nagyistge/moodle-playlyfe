<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require('classes/sdk.php');
require_login();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$context = context_system::instance();
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
$html = '';
$course_groups = get('course_groups');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
$course_group_rule = get_rule('group', 'completed', 'course');
$courses = $DB->get_records('course');

if (array_key_exists('submit', $_POST)) {
  print_object($_POST);
  foreach($_POST['courses'] as $courses) {
  }
  //redirect(new moodle_url('/local/playlyfe/course.php', array('id' => $id)));
} else {
  $html .= '<h2> Please Select the courses which have to be completed and the rewards for it </h2>';
  $html .= '<form action="course_group.php" method="post">';
  $html .= '<div id="course_group"></div>';
  //$html .= create_course_group($course_groups, $courses)
  $html .= '<button id="add" type="button">Add</button><br>';
  $arr = array();
  foreach($courses as $course) {
    if($course->enablecompletion) {
      array_push($arr, array('name' => $course->shortname, 'id' => $course->id));
    }
  }
  $data = array(
    'courses' => $arr,
    'metrics' => $metrics
  );
  $PAGE->requires->js_init_call('show_course_group', array($data));
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  echo $OUTPUT->header();
  echo $html;
  echo $OUTPUT->footer();
  //complete_course(15);
}
