<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/metric/add.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('metrics')->get('add')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

class metric_add_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Create a Metric');
        $mform->addElement('text', 'id', 'Metric ID');
        $mform->addRule('id', null, 'required', null, 'client');
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('text', 'name', 'Metric Name');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_RAW);
        $this->add_action_buttons();
    }
}

$form = new metric_add_form();

// $submit = optional_param('submit', null, PARAM_TEXT);
// if($submit == 'Submit') {
//   $metric_id = optional_param('metric_id', null, PARAM_TEXT);
//   $metric_name = optional_param('metric_name', null, PARAM_TEXT);
//   $pl = local_playlyfe_sdk::get_pl();
//   $data = array(
//     'id' => $metric_id,
//     'name' => $metric_name,
//     'type' => 'point',
//     'image' => 'default-point-metric',
//     'constraints' => array(
//       'default' => '0',
//       'max' => 'Infinity',
//       'min' => '0'
//     )
//   );
//   try {
//     $pl->post('/design/versions/latest/metrics', array(), $data);
//     redirect(new moodle_url('/local/playlyfe/metric/manage.php'));
//   }
//   catch(Exception $e) {
//     print_object($e);
//   }
// }

if($form->is_cancelled()) {
    redirect(new moodle_url('/local/playlyfe/metric/manage.php'));
} else if ($data = $form->get_data()) {
    $pl = local_playlyfe_sdk::get_pl();
    $metric = array(
      'id' => $data->id,
      'name' => $data->name,
      'type' => 'point',
      'image' => 'default-point-metric',
      'constraints' => array(
        'default' => '0',
        'max' => 'Infinity',
        'min' => '0'
      )
    );
  try {
    $pl->post('/design/versions/latest/metrics', array(), $metric);
    redirect(new moodle_url('/local/playlyfe/metric/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
// else {
//   echo $OUTPUT->header();
//   $form->display();
//   $html .= '<form action="add.php" method="post">';
//   $html .= '<p>Metric Name: <input type="text" name="metric_name" /></p>';
//   $html .= '<p>Metric Id: <input type="text" name="metric_id" /></p>';
//   $html .= '<input type="submit" name="submit" value="Submit" />';
//   $html .= '</form>';
//   echo $html;
//   echo $OUTPUT->footer();
// }
  #require_once($CFG->dirroot.'/local/playlyfe/judgelib.php');
  // $settings = new admin_settingpage('playlyfe', 'Playlyfe');
  // $settings->add(new admin_setting_heading('header', 'Client','Please provide your white label client details here'));
  // $settings->add(new admin_setting_configtext('playlyfe/client_id', 'Client ID', '', PARAM_RAW));
  // $settings->add(new admin_setting_configtext('playlyfe/client_secret', 'Client Secret', '', PARAM_RAW));
  // $settings->add(new admin_setting_configtext('playlyfe/access_token', 'Access Token', '', PARAM_RAW));
  // echo $settings->output_html();

// <div id="admin-client_id" class="form-item clearfix">
//   <div class="form-label">
//     <label for="id_s_playlyfe_client_id">Client ID</label>
//     <span class="form-shortname">playlyfe | client_id</span>
//   </div>
//   <div class="form-setting"><div class="form-text defaultsnext"><input type="text" value="raw" name="s_playlyfe_client_id" id="id_s_playlyfe_client_id" size="30"></div><div class="form-defaultinfo">Default: raw</div></div>
//   <div class="form-description"></div>
// </div>
