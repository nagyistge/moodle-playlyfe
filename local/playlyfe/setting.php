<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/eventslib.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/settings.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('settings')->make_active();
$PAGE->navigation->clear_cache();
$html = '';
if ($_POST) {
  set_config('notification', json_encode(array_key_exists('notification', $_POST)), 'playlyfe');
  set_config('leaderboards', json_encode(array_key_exists('leaderboards', $_POST)), 'playlyfe');
  set_config('profile', json_encode(array_key_exists('profile', $_POST)), 'playlyfe');
  redirect(new moodle_url('/local/playlyfe/setting.php'));
} else {
  echo $OUTPUT->header();
  $form = new PForm('Playlyfe Settings');
  $form->create_separator('Navigation Block');
  $form->create_checkbox('Show Notifications', 'notification', true, json_decode(get_config('playlyfe', 'notification')), false);
  $form->create_checkbox('Show Leaderboards', 'leaderboards', true, json_decode(get_config('playlyfe', 'leaderboards')), false);
  $form->create_checkbox('Show Playlyfe Profile', 'profile', true, json_decode(get_config('playlyfe', 'profile')), false);
  $form->end();
  echo $OUTPUT->footer();
}
