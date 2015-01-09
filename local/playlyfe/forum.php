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
$discussion_rule = get_rule($forum->id, 'discussion_created', '',  'Forum '.$forum->id.' Discussion Created');
$post_rule = get_rule($forum->id, 'post_created', '', 'Forum '.$forum->id.' Posted');
$view_rule = get_rule($forum->id, 'viewed', '', 'Forum '.$forum->id.' Viewed');
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));

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
  echo $OUTPUT->header();
  $form = new PFORM($cm->name, 'forum.php?cmid='.$cmid);
  $form->create_separator('Forum Discussion Created', 'You can give rewards to users who start forum topics');
  $form->create_rule_table($discussion_rule, $metrics);
  $form->create_separator('Forum Post Created', 'You can give rewards to users who reply to comments and topics created by others');
  $form->create_rule_table($post_rule, $metrics);
  $form->create_separator('Forum Viewed', 'You can give rewards to users who view the forum. This reward is given only once so that the users dont abuse the system');
  $form->create_rule_table($view_rule, $metrics);
  $form->end();
  echo $OUTPUT->footer();
}
