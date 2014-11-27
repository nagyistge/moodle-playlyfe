<?php
$settings->add(new admin_setting_heading('header', 'Client','Please provide your white label client details here'));
$settings->add(new admin_setting_configtext('playlyfe/client_id', 'Client ID', '', PARAM_RAW));
$settings->add(new admin_setting_configtext('playlyfe/client_secret', 'Client Secret', '', PARAM_RAW));
$settings->add(new admin_setting_configtext('playlyfe/access_token', 'Access Token', '', PARAM_RAW));
$settings->add(new admin_setting_configtext('playlyfe/refresh_token', 'Refresh Token', '', PARAM_RAW));
$settings->add(new admin_setting_configtext('playlyfe/expires_at', 'Expires At', '', PARAM_RAW));
$settings->add(new admin_setting_heading('metrics', 'Metrics',''));
