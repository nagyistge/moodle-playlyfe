<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require('classes/sdk.php');
require_login();
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
  print_error('accessdenied', 'admin');
}
$id = required_param('id', PARAM_TEXT);
$issue = $pl->get('/design/issues/'.$id);
$response = $pl->post('/design/issues/'.$id, array(), array('apply' => $issue['apply']));
if($response['is_resolved']) {
  redirect(new moodle_url('/local/playlyfe/publish.php'));
}
else {
  print_object($response);
}
