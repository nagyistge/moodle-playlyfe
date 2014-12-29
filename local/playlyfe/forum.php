<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
require_login();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$cmid = required_param('cmid', PARAM_INT);
list($forum, $cm) = get_cmid($cmid);
$course = $DB->get_record('course', array('id' => $forum->course), '*', MUST_EXIST);
$context = context_module::instance($cmid);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/playlyfe/forum.php');
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
$discussion_rule = get_rule($forum->id, 'discussion_created', '',  'The Discussion wes viewed');
$post_rule = get_rule($forum->id, 'post_created', '', 'Forum '.$forum->id.' Posted');
$view_rule = get_rule($forum->id, 'viewed', '', 'Forum '.$forum->id.' Viewed');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
$html = '';

if (array_key_exists('submit', $_POST)) {
  $did = $discussion_rule['id'];
  $pid = $post_rule['id'];
  $vid = $view_rule['id'];
  if(array_key_exists($did, $_POST['metrics'])) {
    patch_rule($discussion_rule, $_POST['metrics'][$did], $_POST['values'][$did]);
  }
  if(array_key_exists($pid, $_POST['metrics'])) {
    patch_rule($post_rule, $_POST['metrics'][$pid], $_POST['values'][$pid]);
  }
  if(array_key_exists($vid, $_POST['metrics'])) {
    patch_rule($view_rule, $_POST['metrics'][$vid], $_POST['values'][$vid]);
  }
  redirect(new moodle_url('/local/playlyfe/forum.php', array('cmid' => $cmid)));
} else {
  $name = $cm->name;
  echo $OUTPUT->header();
  $html .= "<h1> $name </h1>";
  $html .= '<form id="mform1" action="forum.php?cmid='.$cmid.'" method="post">';
  $html .= "<h2> Forum Discussion Created </h2>";
  $html .= create_rule_table($discussion_rule, $metrics);
  $html .= "<h2> Forum Post Created </h2>";
  $html .= create_rule_table($post_rule, $metrics);
  $html .= "<h2> Forum Viewed </h2>";
  $html .= create_rule_table($view_rule, $metrics);
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  echo $html;
  echo $OUTPUT->footer();
}
