<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/set/add.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('sets')->get('add')->make_active();
$PAGE->navigation->clear_cache();
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/playlyfe/reward.js'));
$html = '';

if (array_key_exists('id', $_POST)) {
    $items_names = $_POST['items_names'];
    $items_desc = $_POST['items_desc'];
    $items_max = $_POST['items_max'];
    $items_hidden = $_POST['items_hidden'];
    $items = array();
    for ($i = 0; $i < count($items_names); $i++) {
      if($items_hidden[$i] == 'on'){
        $hidden = true;
      }
      else {
        $hidden = false;
      }
      if (strlen($_FILES['itemfile'.$i]['name']) > 0) {
        $item_image = $pl->upload_image($_FILES['itemfile'.$i]['tmp_name']);
      }
      else {
        $item_image = 'default-item';
      }
      array_push($items, array(
        'name' => $items_names[$i],
        'max' => $items_max[$i],
        'image' => $item_image,
        'description' => $items_desc[$i],
        'hidden' => $hidden
      ));
    }
    $set = array(
      'id' => $_POST['id'],
      'name' => $_POST['name'],
      'type' => 'set',
      'image' => 'default-set-metric',
      'description' => $_POST['description'],
      'constraints' => array(
        'items' => $items,
        'max_items' => 'Infinity'
      )
    );
    try {
      if (strlen($_FILES['uploadedfile']['name']) > 0) {
        $set['image'] = $pl->upload_image($_FILES['uploadedfile']['tmp_name']);
      }
      $pl->post('/design/versions/latest/metrics', array(), $set);
      redirect(new moodle_url('/local/playlyfe/set/manage.php'));
    }
    catch(Exception $e) {
      print_object($e);
    }
} else {
    echo $OUTPUT->header();
    $html .= '<h1> Create a new Set </h1>';
    $html .= '<form id="mform1" enctype="multipart/form-data" action="add.php" method="post">';
    $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="500000000" />'; //500kb is 500000
    $html .= '<p>Metric Image: <input type="file" name="uploadedfile" /></p>';
    $html .= '<p>Metric Name: <input type="text" name="name" required/></p>';
    $html .= '<p>Metric Id: <input type="text" name="id" required/></p>';
    $html .= '<p>Metric Description: <input type="text" name="description" required/></p>';
    $html .= '<div id="extra"></div>';
    $html .= '<button id="add" type="button">Add Items</button><br>';
    $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
    $html .= '</form>';
    echo $html;
    $PAGE->requires->js_init_call('init_set', array());
    $PAGE->requires->js_init_call('add_item', array());
    echo $OUTPUT->footer();
}
