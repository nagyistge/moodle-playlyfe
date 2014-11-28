<script src="/cdnjs.cloudflare.com/ajax/libs/mithril/0.1.24/mithril.min.js"></script>
<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/action/add.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('actions')->get('add')->make_active();
$PAGE->navigation->clear_cache();
$PAGE->requires->jquery();
$html = '';

$events = array (
  'course_completed',
  'groups_member_added',
  'groups_member_removed',
  'role_assigned',
  'role_unassigned',
  'user_enrolled',
  'user_logout',
  'user_updated',
  'assessable_submitted',
  'quiz_attempt_submitted'
);

$requiresList = array (
  'If the player has the metric',
  'If the player is part of the team',
  'If the follwing timed condition is satisfied'
);

$ops = array (
  'eq', 'neq', 'lt', 'le', 'gt', 'ge'
);

$pl = local_playlyfe_sdk::get_pl();
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,items'));
$metricsList = array();
foreach($metrics as $metric){
  array_push($metricsList, $metric['id']);
}

$actions = $pl->get('/design/versions/latest/actions', array('fields' => 'id'));
foreach($actions as $action){
  #array_delete($events, $action['id']);
}


class metric_add_form extends moodleform {

    function definition() {
        global $events, $metricsList, $requiresList, $ops;
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Create an Action');
        $mform->addElement('select', 'events_index', 'Event', $events);
        $mform->addRule('events_index', null, 'required', '' , 'client');
        $mform->setType('events_index', PARAM_RAW);

        // $mform->addElement('select', 'requires_index', 'Requires', $requiresList);
        // $mform->setType('requires_index', PARAM_RAW);

        // $mform->addElement('select', 'requires_name', 'Having the name', $metricsList);
        // $mform->setType('requires_name', PARAM_RAW);

        // $mform->addElement('select', 'requires_op', 'Having the operation', $ops);
        // $mform->setType('requires_op', PARAM_RAW);

        // $mform->addElement('text', 'requires_value', 'Having a Value');
        // $mform->setType('requires_value', PARAM_INT);

        $this->add_action_buttons();
    }
}

$form = new metric_add_form();
if($form->is_cancelled()) {
    redirect(new moodle_url('/local/playlyfe/action/manage.php'));
} else if ($data = $form->get_data()) {
    $selected_metrics = $_POST['metrics'];
    $values = $_POST['values'];
    $verbs = $_POST['verbs'];
    $event_name = $events[$data->events_index];
    $rewards = array();
    for ($i = 0; $i < count($selected_metrics); $i++) {
      array_push($rewards, array(
        'metric' => array(
          'id' => $selected_metrics[$i],
          'type' => 'point'
        ),
        'value' => $values[$i],//(string)$data->value,
        'verb' => $verbs[$i]
      ));
    }
    $action = array(
      'id' => $event_name,
      'name' => $event_name,
      'image' => 'default-set-action',
      'requires' => (object)array(),
      'rules' => array(
        array(
          'rewards' => $rewards,
          'requires' => (object)array()
        )
      )
    );
  try {
    $pl->post('/design/versions/latest/actions', array(), $action);
    redirect(new moodle_url('/local/playlyfe/action/manage.php'));
  }
  catch(Exception $e) {
    print_object($e);
  }
} else {
    echo $OUTPUT->header();
    $form->display();
    echo '<div id="metrics" class="hidden">';
    print(json_encode($metrics));
    echo '</div>';
    echo '<button id="add">Add Reward</button>';
    echo $OUTPUT->footer();
}
?>
<script>
    function generateSelect(metrics, index) {
      var html = '<select name="metrics['+index+']">';
      for(var i=0; i<metrics.length; i++){
        html += '<option>'+metrics[i].id+'</option>';
      }
      html += '</select>';
      return html;
    }

    function createVerb(index) {
      var html = '<select name="verbs['+index+']">';
      html += '<option>'+'add'+'</option>';
      html += '<option>'+'set'+'</option>';
      html += '<option>'+'remove'+'</option>';
      html += '</select>';
      return html;
    }

    $(function() {
      var index = 0;
      var metrics = JSON.parse($('#metrics').html());
      $('#add').click(function() {
        $('#mform1').append(
          '<div id="reward'+index+'">'
          +'Reward: '+(index+1)
          +'<p>Metric:'+generateSelect(metrics, index)+' </p>'
          +'<p>Verb: '+createVerb(index)+' </p>'
          +'<p>Value: <input name="values['+index+']" type="number" value="1" required /></p></div>'
        );
        index++;
      });
    });
</script>
<?php
