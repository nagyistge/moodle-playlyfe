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
$PAGE->settingsnav->get('root')->get('playlyfe')->get('course')->make_active();
global $DB;
$pl = local_playlyfe_sdk::get_pl();
$html = '';

$table = new html_table();
$table->head  = array('ID', 'Name', 'Enable Leaderboard', 'Metric', 'Actions');
$table->colclasses = array('leftalign', 'centeralign', 'centeralign', 'centeralign', 'centeralign');
$table->data = array();
$table->attributes['class'] = 'admintable generaltable';
$table->id = 'manage_courses';

$courses = $DB->get_records('course', array());
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,name'));
$metricsList = array();
foreach($metrics as $metric){
  array_push($metricsList, $metric['id']);
}

foreach($courses as $course) {
  $course_link = '<a href="set_course.php?id='.$course->id.'">'.$course->fullname.'</a>';
  $table->data[] = new html_table_row(array($course->id, $course_link, '<input type="checkbox" checked=false>', '', ''));
  // $pl->post('/admin/leaderboards/'.$metric['id'].'/cld'.$course->id, array());
  // print_object($course);
  // var html = '<select name="metrics['+index+']">';
      // for(var i=0; i<metrics.length; i++){
      //   html += '<option>'+metrics[i].id+'</option>';
      // }
      // html += '</select>';
}
$html .= html_writer::table($table);


$course = $DB->get_record('course', array('id' => '14'), '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);
$modnames = get_module_types_names();
$modnamesplural = get_module_types_names(true);
$modnamesused = $modinfo->get_used_module_names();
$mods = $modinfo->get_cms();
$sections = $modinfo->get_section_info_all();

print_object($modnamesused);
// print_object($sections);

$res = $pl->get('/design/images', array('album' => 'playlyfe'));
print_object($res);

echo $OUTPUT->header();
echo '<h1> Courses </h1>';
echo $html;
echo $OUTPUT->footer();
