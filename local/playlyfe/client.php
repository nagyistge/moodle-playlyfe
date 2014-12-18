<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once('/var/www/html/vendor/autoload.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/client.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$PAGE->settingsnav->get('root')->get('playlyfe')->get('client')->make_active();

use Playlyfe\Sdk\Playlyfe;

class client_form extends moodleform {

    function definition() {

        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Client');
        $mform->addElement('text', 'id', 'Client ID');
        $mform->addRule('id', null, 'required', null, 'client');
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('text', 'secret', 'Client Secret');
        $mform->addRule('secret', null, 'required', null, 'client');
        $mform->setType('secret', PARAM_RAW);
        $this->add_action_buttons();
    }
}
$form = new client_form();

if($form->is_cancelled()) {
  redirect(new moodle_url('/local/playlyfe/client.php'));
} else if ($data = $form->get_data()) {
  $client_id = $data->id;
  $client_secret = $data->secret;
  set_config('client_id', $client_id, 'playlyfe');
  set_config('client_secret', $client_secret, 'playlyfe');
  set_config('access_token', null, 'playlyfe');
  $pl = new Playlyfe(array(
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'type' => 'client',
    'store' => function($token) {
      set_config('access_token', $token['access_token'], 'playlyfe');
      set_config('expires_at', $token['expires_at'], 'playlyfe');
    },
    'load' => function() {
      $access_token = array(
        'access_token' => get_config('playlyfe', 'access_token'),
        'expires_at' => get_config('playlyfe', 'expires_at')
      );
      return $access_token;
    }
  ));
  try {
    $pl->post('/design/versions/latest/actions', array(), array(
      'id' => 'course_completed',
      'name' => 'course_completed',
      'rules' => array(),
      'requires' => (object)array(),
      'variables' => array(
        array(
          'name' => 'course_id',
          'type' => 'int',
          'required' => true
        )
      )
    ));
    $pl->post('/design/versions/latest/actions', array(), array(
      'id' => 'course_group_completed',
      'name' => 'course_group_completed',
      'rules' => array(),
      'requires' => (object)array(),
      'variables' => array(
        array(
          'name' => 'course_group_id',
          'type' => 'int',
          'required' => true
        )
      )
    ));
    $pl->post('/design/versions/latest/actions', array(), array(
      'id' => 'quiz_completed',
      'name' => 'quiz_completed',
      'rules' => array(),
      'requires' => (object)array(),
      'variables' => array(
        array(
          'name' => 'quiz_id',
          'type' => 'int',
          'required' => true
        )
      )
    ));
    $pl->post('/design/versions/latest/actions', array(), array(
      'id' => 'quiz_bonus',
      'name' => 'quiz_bonus',
      'rules' => array(),
      'requires' => (object)array(),
      'variables' => array(
        array(
          'name' => 'quiz_id',
          'type' => 'int',
          'required' => true
        )
      )
    ));
    $pl->post('/design/versions/latest/actions', array(), array(
      'id' => 'course_bonus',
      'name' => 'course_bonus',
      'rules' => array(),
      'requires' => (object)array(),
      'variables' => array(
        array(
          'name' => 'course_id',
          'type' => 'int',
          'required' => true
        )
      )
    ));
    redirect(new moodle_url('/local/playlyfe/client.php'));
  }
  catch(Exception $e) {
    if($e->name == 'action_exists') {
      redirect(new moodle_url('/local/playlyfe/client.php'));
    }
    else {
      print_object($e);
    }
  }
} else {
  $toform = array();
  $toform['id'] = get_config('playlyfe', 'client_id');
  $toform['secret'] = get_config('playlyfe', 'client_secret');
  $form->set_data($toform);
  echo $OUTPUT->header();
  $form->display();
  echo $OUTPUT->footer();
}
