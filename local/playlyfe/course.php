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
$completed_rule = get_rule($id, 'completed');
$bonus_rule = get_rule($id, 'bonus');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
$html = '';

if (array_key_exists('submit', $_POST)) {
  $cid = $completed_rule['id'];
  $bid = $bonus_rule['id'];
  if(array_key_exists($cid, $_POST['metrics'])) {
    patch_rule($completed_rule, $_POST['metrics'][$cid], $_POST['values'][$cid]);
  }
  if(array_key_exists($bid, $_POST['metrics'])) {
    patch_rule($bonus_rule, $_POST['metrics'][$bid], $_POST['values'][$bid]);
  }
  set_leaderboards($_POST, $metrics, array($course), 'course'.$id.'_leaderboard');
  redirect(new moodle_url('/local/playlyfe/course.php', array('id' => $id)));
} else {
  $leaderboards = array_merge(get_leaderboards('all_leaderboards'), get_leaderboards('course'.$id.'_leaderboard'));
  // $modinfo = get_fast_modinfo($course);
  // $modnames = get_module_types_names();
  // $modnamesused = $modinfo->get_used_module_names();
  // $mods = $modinfo->get_cms();
  // $sections = $modinfo->get_section_info_all();
  // $name = $course->fullname;
  echo $OUTPUT->header();
  $name = $course->shortname;
  $html .= "<h1> $name </h1>";
  $html .= '<form action="course.php?id='.$id.'" method="post">';
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
  $html .= create_rule_table($completed_rule, $metrics);
  $criteria = $DB->get_record('course_completion_criteria', array('course' => $id, 'criteriatype' => 2));
  // 2 for timeend criteria
  if($criteria and $criteria->timeend > 0) {
    $html .= '<h2> Bonus for Early Completion Before '.date("D, d M Y H:i:s", $criteria->timeend).'</h2>';
    $html .= create_rule_table($bonus_rule, $metrics);
  }
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  echo $html;
  complete_course(15);
  echo $OUTPUT->footer();
}
