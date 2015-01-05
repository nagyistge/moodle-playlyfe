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
      $item = array(
        'name' => $items_names[$i],
        'max' => $items_max[$i],
        'image' => $metric['constraints']['items'][$i]['image'],
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
    $html .= "<h1> Editing Set - $metric_name </h1>";
    $html .= '<form id="mform1" enctype="multipart/form-data" action="edit.php" method="post">';
    $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="500000000" />'; //500kb is 500000
    $html .= '<p>Metric Image: <input type="file" name="uploadedfile" /></p>';
    $html .= '<p>Metric Name: <input type="text" name="name" value="'.$metric_name.'"/></p>';
    $html .= '<input type="hidden" name="id" value="'.$id.'"/>';
    $html .= '<p>Metric Description: <input type="text" name="description" value="'.$metric['description'].'"/></p>';
    $index = 0;
    foreach($metric['constraints']['items'] as $item) {
      $html .= $OUTPUT->box_start('generalbox authsui');
      $html .= '<div id="item'.$index.'">';
      $html .= 'Badge '.($index+1);
      $html .= '<p>Name: <input name="items_names['.$index.']" type="text" value="'.$item['name'].'"required /></p>';
      $html .= '<p>Description: <input name="items_desc['.$index.']" type="text" value="'.$item['description'].'"required /></p>';
      $html .= '<p>Max: <input name="items_max['.$index.']" type="number" value="1" value="'.$item['max'].'"required /></p>';
      $html .= '<p>Hidden: <input name="items_hidden['.$index.']" type="checkbox" checked /></p></div>';
      $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="500000000" />';
      $html .= '<p>Badge Image: <input type="file" name="itemfile'.$index.'" /></p>';
      $html .= $OUTPUT->box_end();
      $index++;
    }
    $html .= '<div id="extra"></div>';
    $html .= '<button type="button" id="add">Add Items</button><br>';
    $html .= '<input type="submit" name="submit" value="Submit" />';
    $html .= '</form>';
    echo $html;
    $PAGE->requires->js_init_call('init_set', array($index));
    echo $OUTPUT->footer();
}
