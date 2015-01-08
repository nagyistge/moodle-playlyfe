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
$html = '';
$course_groups = get('course_groups');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
$courses = $DB->get_records('course');

// $courses_completed = $DB->get_records('course_completions', array('userid' => $USER->id));

if (array_key_exists('submit', $_POST)) {
  print_object($_POST);
  $index = 1;
  foreach($_POST['courses'] as $courses) {
    if(array_key_exists($index, $_POST['metrics'])) {
      $rule = get_rule($index, 'completed', 'course_group', 'Course Group Completed');
      patch_rule($rule, $_POST['metrics'][$index], $_POST['values'][$index]);
      set('course_group_'.$index, $courses);
    }
    $index++;
  }
  redirect(new moodle_url('/local/playlyfe/course_group.php'));
} else {
  $rules = $pl->get('/design/versions/latest/rules', array('fields' => 'id,name,rules'));
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
    $html .= '<h2> Please Select the courses which have to be completed and the rewards for completion of all of them </h2>';
    $html .= '<form action="course_group.php" method="post">';
    $html .= '<div id="course_group">';
    $index = 1;
    foreach($rules as $rule) {
      if(strpos($rule['name'], 'Group') !== false) {
        $courses = array();
        $sc = get('course_group_'.$index);
        foreach($sc as $course_id) {
          $course = $DB->get_record('course', array('id' => $course_id));
          array_push($courses, array('name' => $course->shortname, 'id' => $course->id, 'selected' => true ));
        }
        $data = array(
          'courses' => array_merge($courses, $arr),
          'metrics' => $metrics,
          'rewards' => $rule['rules']['0']['rewards']
        );
        $PAGE->requires->js_init_call('add_course_group', array($data));
        //$html .= "<h2> Rewards on Course Group Completion </h2>";
        //$html .= create_rule_table($rule , $metrics);
        $index++;
      }
    }
    $html .= '</div><br>';
    $html .= '<button id="add" type="button">Add</button><br>';
    $PAGE->requires->js_init_call('handle_course_group_add', array(array('courses' => $arr, 'metrics' => $metrics)));
    $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
    $html .= '</form>';
  }
  echo $OUTPUT->header();
  echo $html;
  echo $OUTPUT->footer();
}
