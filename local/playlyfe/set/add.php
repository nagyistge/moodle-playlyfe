<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/set/add.php');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('sets')->get('add')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

if (array_key_exists('id', $_POST)) {
    $items_names = $_POST['items_names'];
    $items_desc = $_POST['items_desc'];
    $items_max = $_POST['items_max'];
    $items_hidden = $_POST['items_hidden'];
    $items = array();
    for ($i = 0; $i < count($items_names); $i++) {
      if($items_hidden[$i] === 'on'){
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
    $form = new PForm('Set');
    $form->create_file('Image', 'uploadedfile');
    $form->create_input('Name', 'name');
    $form->create_input('ID', 'id');
    $form->create_input('Description', 'description');
    $form->create_button('add', 'Add Items');
    $form->create_separator('Items');
    $form->end();
    echo $html;
    $PAGE->requires->js_init_call('init_set', array());
    $PAGE->requires->js_init_call('add_item', array());
    echo $OUTPUT->footer();
}
