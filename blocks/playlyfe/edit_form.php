<?php
require_once('classes/sdk.php');
class block_playlyfe_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $PAGE, $COURSE;
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', 'Set Block Details');

        // A sample string variable with a default value.
        $mform->addElement('text', 'config_title', 'Title');
        $mform->setDefault('config_title', 'Title');
        $mform->setType('config_title', PARAM_TEXT);
    }
}
