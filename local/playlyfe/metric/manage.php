<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/metric/manage.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('metrics')->get('manage')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

class metric_list_form extends moodleform {

    function definition() {

        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Metrics');
    }
}

$html .= $OUTPUT->box_start('generalbox authsui');
$table = new html_table();
$table->head  = array('Name', 'ID', '');
$table->colclasses = array('leftalign', 'leftalign', 'leftalign');
$table->data  = array();
$table->attributes['class'] = 'admintable generaltable';
$table->id = 'manage_metrics';

$pl = local_playlyfe_sdk::get_pl();
$metrics = $pl->get('/design/versions/latest/metrics', array());
foreach($metrics as $metric) {
  $edit = '<a href="edit.php?name='.$metric['name'].'&id='.$metric['id'].'">Edit</a>';
  $table->data[] = new html_table_row(array($metric['name'], $metric['id'], $edit));
}
$html .= html_writer::table($table);
$html .= $OUTPUT->box_end();
echo $OUTPUT->header();
$form = new metric_list_form();
$form->display();
echo $html;
echo $OUTPUT->footer();
