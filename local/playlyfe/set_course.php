<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/set_course.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$PAGE->settingsnav->get('root')->get('playlyfe')->get('course')->make_active();
global $DB;
$pl = local_playlyfe_sdk::get_pl();
$html = '';

$id = required_param('id', PARAM_TEXT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$html .= '<p><big>Create a Leaderboard Based On Metric:         </big><select>';
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type'));
foreach($metrics as $metric) {
  if($metric['type'] == 'point') {
    $html .= '<option>'.$metric['id'].'</option>';
  }
}
$html .= '</select></p>';
echo $OUTPUT->header();
echo '<h1> Playlyfe Course Settings </h1>';
echo $html;
print_object($course);
echo $OUTPUT->footer();
