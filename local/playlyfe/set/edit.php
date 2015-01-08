<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/set/edit.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('sets')->make_active();
$PAGE->navigation->clear_cache();
$html = '';

if (array_key_exists('submit', $_POST)) {
    $id = $_POST['id'];
    $metric = $pl->get('/design/versions/latest/metrics/'.$id);
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
      if(array_key_exists($i, $metric['constraints']['items'])) {
        $item_image = $metric['constraints']['items'][$i]['image'];
      }
      if(!$item_image or $item_image === null) {
        $item_image = 'default-item';
      }
      $item = array(
        'name' => $items_names[$i],
        'max' => $items_max[$i],
        'image' => $item_image,
        'description' => $items_desc[$i],
        'hidden' => $hidden
      );
      if (strlen($_FILES['itemfile'.$i]['name']) > 0) {
        $item['image'] = $pl->upload_image($_FILES['itemfile'.$i]['tmp_name']);
      }
      array_push($items, $item);
    }
    $set = array(
      'name' => $_POST['name'],
      'type' => 'set',
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
    $pl->patch('/design/versions/latest/metrics/'.$_POST['id'], array(), $set);
    redirect(new moodle_url('/local/playlyfe/set/manage.php'));
   }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    $id = required_param('id', PARAM_TEXT);
    $metric = $pl->get('/design/versions/latest/metrics/'.$id);
    $metric_name = $metric['name'];
    echo $OUTPUT->header();
    $form = new PForm("Editing Set - $metric_name");
    $form->create_file('Image', 'uploadedfile');
    $form->create_input('Name', 'name', $metric_name);
    $form->create_hidden('id', $id);
    $form->create_input('Description', 'description', $metric['description']);
    $form->create_button('add', 'Add Items');
    $form->create_separator('Items');
    $form->end();
    foreach($metric['constraints']['items'] as $item) {
      $PAGE->requires->js_init_call('add_item', array($item));
    }
    echo $html;
    $PAGE->requires->js_init_call('init_set', array());
    echo $OUTPUT->footer();
}
