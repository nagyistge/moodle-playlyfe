<?php

require_once('../../config.php');
require_once('playlyfe_form.php');

global $DB;

// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);


if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_playlyfe', $courseid);
}

require_login($course);

$playlyfe = new playlyfe_form();

$playlyfe->display();
?>
