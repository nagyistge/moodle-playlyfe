<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/eventslib.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/settings.php');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('settings')->make_active();
$PAGE->navigation->clear_cache();
$html = '';
if ($_POST) {
  $setting = array(
    'notification' => array_key_exists('notification', $_POST),
    'profile' => array_key_exists('profile', $_POST),
    'leaderboard' => array_key_exists('leaderboard', $_POST)
  );
  set_config('setting', json_encode($setting), 'playlyfe');
  redirect(new moodle_url('/local/playlyfe/setting.php'));
} else {
  echo $OUTPUT->header();
  $form = new PForm('Playlyfe Settings');
  $form->create_separator('Navigation Block');
  $setting = json_decode(get_config('playlyfe', 'setting'), true);
  $form->create_checkbox('Show Notifications', 'notification', true, $setting['notification'], false);
  $form->create_checkbox('Show Leaderboards', 'leaderboard', true, $setting['leaderboard'], false);
  $form->create_checkbox('Show Playlyfe Profile', 'profile', true, $setting['profile'], false);
  $form->end();
  echo $OUTPUT->footer();
}
