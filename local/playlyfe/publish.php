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

class client_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $this->add_action_buttons(false);
    }
}

$form = new client_form();

if ($data = $form->get_data()) {
  $pl->post('/design/versions/latest/simulate');
  redirect(new moodle_url('/local/playlyfe/client.php'));
} else {
  echo $OUTPUT->header();
  echo '<h1> Are you Sure you Want to publish all your changes? </h1>';
  $form->display();
  echo $OUTPUT->footer();
}
