<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
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
$html = '';
$pl = local_playlyfe_sdk::get_pl();

class metric_add_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Create a Set');
        $mform->addElement('text', 'id', 'Set ID');
        $mform->addRule('id', null, 'required', null, 'client');
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('text', 'name', 'Set Name');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_RAW);
        $this->add_action_buttons();
    }
}

$form = new metric_add_form();
if($form->is_cancelled()) {
    redirect(new moodle_url('/local/playlyfe/set/manage.php'));
} else if ($data = $form->get_data()) {
    $items_names = $_POST['items_names'];
    $items_desc = $_POST['items_desc'];
    $items_max = $_POST['items_max'];
    $items = array();
    for ($i = 0; $i < count($items_names); $i++) {
      array_push($items, array(
        'name' => $items_names[$i],
        'max' => $items_max[$i],
        'image' => '',
        'description' => $items_desc[$i]
      ));
    }
    $set = array(
      'id' => $data->id,
      'name' => $data->name,
      'type' => 'set',
      'image' => 'default-set-metric',
      'constraints' => array(
        'items' => $items,
        'max_items' => 'Infinity'
      )
    );
    print_object($set);
    try {
      $pl->post('/design/versions/latest/metrics', array(), $set);
      redirect(new moodle_url('/local/playlyfe/set/manage.php'));
    }
    catch(Exception $e) {
      print_object($e);
    }
} else {
    echo $OUTPUT->header();
    $form->display();
    echo '<button id="add">Add Badges</button>';
}
?>
<script>
    function remove(index) {
      $('#item'+index).remove();
    }
    $(function() {
      var index = 0;
      $('#add').click(function() {
        $('#mform1').append(
          '<div id="item'+index+'">'
          +'Badge '+(index+1)+'<button onclick=remove('+index+')>delete</button>'
          +'<p>Name: <input name="items_names['+index+']" type="text" required /></p>'
          +'<p>Description: <input name="items_desc['+index+']" type="text" required /></p>'
          +'<p>Max: <input name="items_max['+index+']" type="number" value="1" required /></p></div>'
        );
        index++;
      });
    });
</script>
<?php
  echo '';
  echo $OUTPUT->footer();
  echo '';

