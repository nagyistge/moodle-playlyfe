<?php
require_once("{$CFG->libdir}/formslib.php");

class playlyfe_form extends moodleform {

    function definition() {

        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Form');
    }
}
