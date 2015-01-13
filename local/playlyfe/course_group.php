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
$PAGE->settingsnav->get('root')->get('playlyfe')->get('course_group')->make_active();
$PAGE->navigation->clear_cache();
$html = '';
$course_groups = get('course_groups');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
$courses = $DB->get_records('course');

if (array_key_exists('submit', $_POST)) {
  print_object($_POST);
  $arr = array();
  if(array_key_exists('courses', $_POST)) {
    foreach($_POST['courses'] as $index => $courses) {
      $rule = get_rule($index, 'completed', 'course_group', 'Course Group '.$index.' Completed');
      patch_rule($rule, $_POST);
      $arr[$index] = $courses;
    }
    set('course_group', $arr);
  }
  redirect(new moodle_url('/local/playlyfe/course_group.php'));
} else {
  $arr = array();
  foreach($courses as $course) {
    if($course->enablecompletion) {
      array_push($arr, array('name' => $course->shortname, 'id' => $course->id));
    }
  }
  if(count($arr) === 0) {
    $html .= 'You dont have any courses with course completion enabled. Please add couse completion to your courses';
  }
  else {
    $course_group = get('course_group');
    $form = new PForm('Course Group Completion');
    if(empty($course_group)) {
      $form->html .= 'Please Select the courses you would like to group together to reward the user for completion of them';
    }
    $form->html .= '<div id="course_group"></div>';
    foreach($course_group as $index => $cg) {
      $rule = get_rule($index, 'completed', 'course_group', 'Course Group '.$index.' Completed');
      $courses = array();
      foreach($cg as $course_id) {
        $course = $DB->get_record('course', array('id' => $course_id));
        array_push($courses, array('name' => $course->shortname, 'id' => $course->id, 'selected' => true ));
      }
      foreach ($arr as $course) {
        if(!in_array($course['id'], $cg)) {
          array_push($courses, $course);
        }
      }
      $data = array(
        'courses' => $courses,
        'metrics' => $metrics,
        'rewards' => $rule['rules']['0']['rewards']
      );
      $PAGE->requires->js_init_call('add_course_group', array($data));
    }
    $PAGE->requires->js_init_call('handle_course_group_add', array(array('courses' => $arr, 'metrics' => $metrics)));
  }
  echo $OUTPUT->header();
  $form->create_button('add', 'Add');
  $form->end();
  echo $html;
  echo $OUTPUT->footer();
}
