<?php
defined('MOODLE_INTERNAL') || die;
if ($hassiteconfig) { // needs this condition or there is error on login page
  #require_once($CFG->dirroot.'/local/playlyfe/judgelib.php');
  $settings = new admin_settingpage('playlyfe', 'Playlyfe');
  $settings->add(new admin_setting_heading('header', 'Client','Please provide your white label client details here'));
  $settings->add(new admin_setting_configtext('playlyfe/client_id', 'Client ID', '', PARAM_RAW));
  $settings->add(new admin_setting_configtext('playlyfe/client_secret', 'Client Secret', '', PARAM_RAW));
  $settings->add(new admin_setting_configtext('playlyfe/access_token', 'Access Token', '', PARAM_RAW));
  $ADMIN->add('localplugins', $settings);
}
