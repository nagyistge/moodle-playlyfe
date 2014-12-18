<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require('classes/sdk.php');
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
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/playlyfe/reward.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/playlyfe/course.js'));
$html = '';
$action = $pl->get('/design/versions/latest/actions/course_completed');
$action2 = $pl->get('/design/versions/latest/actions/course_bonus');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));

if (array_key_exists('id', $_POST)) {
  $id = $_POST['id'];
  $id_bonus = $id.'_bonus';
  $action = patch_action('course_id', $action, $id, $_POST['metrics'][$id], $_POST['values'][$id]);
  if(array_key_exists($id_bonus, $_POST['metrics'])) {
    $action2 = patch_action('course_id', $action2, $id, $_POST['metrics'][$id_bonus], $_POST['values'][$id_bonus]);
  }
  try {
    $pl->patch('/design/versions/latest/actions/course_completed', array(), $action);
    if(array_key_exists($id_bonus, $_POST['metrics'])) {
      $pl->patch('/design/versions/latest/actions/course_bonus', array(), $action2);
    }
    set_leaderboards($_POST, $metrics, array($course), 'course'.$id.'_leaderboard');
    redirect(new moodle_url('/local/playlyfe/course.php', array('id' => $id)));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
  $leaderboards = array_merge(get_leaderboards('all_leaderboards'), get_leaderboards('course'.$id.'_leaderboard'));
  $modinfo = get_fast_modinfo($course);
  $modnames = get_module_types_names();
  $modnamesused = $modinfo->get_used_module_names();
  $mods = $modinfo->get_cms();
  $sections = $modinfo->get_section_info_all();
  $name = $course->fullname;
  $rewards = array();
  foreach($action['rules'] as $rule) {
    if ($rule['requires']['context']['rhs'] == $id) {
      $rewards = $rule['rewards'];
    }
  }
  $data = array(
    'id' => $id,
    'metrics' => $metrics,
    'leaderboard' => get_config('playlyfe', 'course'.$id),
    'rewards' => $rewards
  );
  echo $OUTPUT->header();
  $html .= "<h1> $name </h1>";
  $html .= '<form id="mform1" action="course.php" method="post">';
  $html .= '<input name="id" type="hidden" value="'.$id.'"/>';
  $html .= '<h2> Leaderboards for this Course </h2>';
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
  $html .= '</div><br>';
  $html .= "<h2> Rewards on Course Completion </h2>";
  $html .= create_reward_table($id, $id, $metrics, $action);
  $criteria = $DB->get_record('course_completion_criteria', array('course' => $id, 'criteriatype' => 2));
  // 2 for timeend criteria
  if($criteria->timeend > 0) {
    $html .= '<h2> Bonus for Early Completion Before '.date("D, d M Y H:i:s", $criteria->timeend).'</h2>';
    $html .= create_reward_table($id.'_bonus', $id, $metrics, $action2);
  }
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  echo $html;
  echo $OUTPUT->footer();
}
