<?php
  #use Playlyfe\Sdk\Playlyfe;

  class block_playlyfe_edit_form extends block_edit_form {

      protected function specific_definition($mform) {

          // Section header title according to language file.
          $mform->addElement('header', 'configheader', 'Set Block Details');

          // A sample string variable with a default value.
          $mform->addElement('text', 'config_text', 'Title');
          $mform->setDefault('config_text', 'Playlyfe');
          $mform->setType('config_text', PARAM_RAW);

          $mform->addElement('text', 'config_title', 'Header');
          $mform->setDefault('config_title', 'Hrofile');
          $mform->setType('config_title', PARAM_MULTILANG);

          $types = array('0' => 'Profile', '1' => 'Leaderboard', '2' => 'Players');
          $mform->addElement('select', 'config_type', 'Type', $types);
          $mform->setDefault('config_type', 0);
      }
  }
