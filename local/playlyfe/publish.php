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
$simulated = false;
if (array_key_exists('submit', $_POST)) {
  try {
    $pl->post('/design/versions/latest/simulate');
    $simulated = true;
  }
  catch(Exception $e) {
    print_object($e);
  }
}
$issues = $pl->get('/design/issues', array('type' => 'metric'));
$unresolved_issues = array();
foreach ($issues as $value) {
  if (!$value['is_resolved']) {
    array_push($unresolved_issues, $value);
  }
}
if(count($unresolved_issues) > 0) {
  $html .= '<h1> You have Issues in your Game Design Please Fix Them?  Now! </h1>';
  $table = new html_table();
  $table->head = array('Reason', 'Metric', 'Apply Fix');
  $table->colclasses = array('leftalign', 'leftalign', 'centeralign');
  $table->data = array();
  $table->attributes['class'] = 'admintable generaltable';
  $table->id = 'manage_sets';
  foreach ($issues as $unresolved_issues) {
    $fix = '<a href="fix.php?id='.$value['id'].'">Fix</a>';
    $table->data[] = new html_table_row(array($value['code'], $value['vars']['metric_design']['name'], $fix));
  }
  $html .= html_writer::table($table);
}
else {
  if($simulated) {
    $html .= '<h1>All Clear!</h2>';
    $html .= '<h2>The Game has been successfully put into Simulation mode!</h2>';
  }
  else {
    $html .= '<h1> Are you Sure you Want to publish all your changes? </h1>';
    $html .= '<form action="publish.php" method="post">';
    $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
    $html .= '</form>';
  }
}

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
