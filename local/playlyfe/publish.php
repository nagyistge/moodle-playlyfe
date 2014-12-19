<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
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
$html = '';

if (array_key_exists('submit', $_POST)) {
  try {
    $pl->post('/design/versions/latest/simulate');
  }
  catch(Exception $e) {
    print_object($e);
  }
}
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
