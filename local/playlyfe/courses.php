<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require('classes/sdk.php');
require_login();
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
  print_error('accessdenied', 'admin');
}
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/playlyfe/courses.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
$PAGE->settingsnav->get('root')->get('playlyfe')->get('courses')->make_active();
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/playlyfe/reward.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/playlyfe/courses.js'));
$html = '';
$action = $pl->get('/design/versions/latest/actions/course_completed');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
$courses = $DB->get_records('course', array());

if (array_key_exists('submit', $_POST)) {
  $rules = array();
  foreach($_POST['metrics'] as $key => $value) {
    create_rules('course_id', $rules, $key, $value, $_POST['values'][$key]);
  }
  $action['rules'] = $rules;
  $action['requires'] = (object)array();
  unset($action['id']);
  unset($action['_errors']);
  try {
    $pl->patch('/design/versions/latest/actions/course_completed', array(), $action);
    set_leaderboards($_POST, $metrics, $courses, 'all_leaderboards');
    redirect(new moodle_url('/local/playlyfe/courses.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
  $leaderboards = get_leaderboards('all_leaderboards');
  $data = array(
    'metrics' => $metrics
  );
  echo $OUTPUT->header();
  $html .= '<h1> Courses </h1>';
  $html .= '<h2> Select Leaderboards For All Courses </h2>';
  $html .= 'Please Select The Metrics For the leaderboards which you would like across all courses<br>';
  $html .= '<form id="mform1" action="courses.php" method="post">';
  $html .= '<div>';
  foreach ($metrics as $metric) {
    if($metric['type'] === 'point') {
      if(in_array($metric['id'], $leaderboards)) {
        $html .= '<input type="checkbox" value="'.$metric['id'].'" name="leaderboards[]" checked />'.$metric['id'].'<br>';
      }
      else {
        $html .= '<input type="checkbox" value="'.$metric['id'].'" name="leaderboards[]" />'.$metric['id'].'<br>';
      }
    }
  }
  $html .= '</div><br><br>';
  $html .= '<h2> Add Rewards for your courses on Completion </h2>';
  foreach($courses as $course) {
    $html .= '<h2><a href="course.php?id='.$course->id.'">'.$course->id.'.'.$course->fullname.'</a></h2>';
    $html .= create_reward_table($course->id, $course->id, $metrics, $action);
  }
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  echo $html;
  $PAGE->requires->js_init_call('setup', array($data));
  echo $OUTPUT->footer();
}
