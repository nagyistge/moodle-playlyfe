<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/client.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('client')->make_active();

#admin_externalpage_setup('foo');
$settings = new admin_settingpage('playlyfe', 'Playlyfe');
$settings->add(new admin_setting_heading('header', 'Client','Please provide your white label client details here'));
$settings->add(new admin_setting_configtext('playlyfe/client_id', 'Client ID', '', PARAM_RAW));
$settings->add(new admin_setting_configtext('playlyfe/client_secret', 'Client Secret', '', PARAM_RAW));

echo $OUTPUT->header();
echo $settings->output_html();
echo '<div class="form-buttons"><input type="submit" value="Save changes" class="form-submit"></div>';
echo $OUTPUT->footer();
