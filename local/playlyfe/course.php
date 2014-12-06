<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/course.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$PAGE->settingsnav->get('root')->get('playlyfe')->get('courses')->make_active();
global $DB;
$pl = local_playlyfe_sdk::get_pl();
$html = '';

$table = new html_table();
$table->head  = array('ID', 'Name');
$table->colclasses = array('leftalign', 'centeralign');
$table->data = array();
$table->attributes['class'] = 'admintable generaltable';
$table->id = 'manage_courses';

$courses = $DB->get_records('course', array());
foreach($courses as $course) {
  $course_link = '<a href="set_course.php?id='.$course->id.'">'.$course->fullname.'</a>';
  $table->data[] = new html_table_row(array($course->id, $course_link));
}
$html .= html_writer::table($table);
echo $OUTPUT->header();
echo '<h1> Courses </h1>';
echo $html;
echo $OUTPUT->footer();
