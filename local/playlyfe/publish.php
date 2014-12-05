<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/publish.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$PAGE->settingsnav->get('root')->get('playlyfe')->get('publish')->make_active();
$pl = local_playlyfe_sdk::get_pl();
$html = '';
$action_names = array(
  'course_completed',
  'user_enrolled',
  'user_logout',
  'assessable_submitted',
  'quiz_attempt_submitted'
);

if (array_key_exists('submit', $_POST)) {
  $actions = $pl->get('/design/versions/latest/actions', array('fields' => 'id'));
  $actions_list = array();
  foreach($actions as $action) {
    array_push($actions_list, $action['id']);
  }
  if(!in_array('course_completed', $actions_list)) {
    $action = array(
      'id' => 'course_completed',
      'name' => 'course_completed',
      'image' => 'default-set-action',
      'requires' => (object)array(),
      'rules' => array(),
      'variables' => array(
        array(
          'name' => 'course_id',
          'type' => 'string',
          'default' => '',
          'required' => true
        )
      )
    );
    try {
      $pl->post('/design/versions/latest/actions', array(), $action);
      $pl->post('/design/versions/latest/simulate');
    }
    catch(Exception $e) {
      print_object($e);
    }
  }
  redirect(new moodle_url('/local/playlyfe/client.php'));
} else {
  $issues = $pl->get('/design/issues');
  echo $OUTPUT->header();
  $html .= '<h1> Are you Sure you Want to publish all your changes? </h1>';
  $html .= '<form action="publish.php" method="post">';
  //foreach($issues as $issue)
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  print_object($issues);
  echo $html;
  echo $OUTPUT->footer();
}
