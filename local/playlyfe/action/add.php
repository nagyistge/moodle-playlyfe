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
//$PAGE->requires->js(new moodle_url('http://cdnjs.cloudflare.com/ajax/libs/mithril/0.1.26/mithril.min.js'));
$html = '';
global $DB;

$events = array(
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

if (array_key_exists('id', $_POST)) {
  //   $selected_metrics = $_POST['metrics'];
  //   $values = $_POST['values'];
  //   $verbs = $_POST['verbs'];
    $event_name = $_POST['id'];
  //   $rewards = array();
  //   for ($i = 0; $i < count($selected_metrics); $i++) {
  //     array_push($rewards, array(
  //       'metric' => array(
  //         'id' => $selected_metrics[$i],
  //         'type' => 'point'
  //       ),
  //       'value' => $values[$i],
  //       'verb' => $verbs[$i]
  //     ));
  //   }
  //   $action = array(
  //     'id' => $event_name,
  //     'name' => $event_name,
  //     'image' => 'default-set-action',
  //     'requires' => (object)array(),
  //     'rules' => array(
  //       array(
  //         'rewards' => $rewards,
  //         'requires' => (object)array()
  //       )
  //     )
  //   );
  // try {
  //   $pl->post('/design/versions/latest/actions', array(), $action);
  //   redirect(new moodle_url('/local/playlyfe/action/manage.php'));
  // }
  // catch(Exception $e) {
  //   print_object($e);
  // }
} else {
    $courses = $DB->get_records('course', array());
    $coursesList = array();
    foreach($courses as $course) {
      array_push($coursesList, array('id' => $course->id,'name' => $course->fullname));
    }
    echo $OUTPUT->header();
    $metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,items'));
    $actions = $pl->get('/design/versions/latest/actions', array('fields' => 'id'));
    $actionsList = array();
    foreach($actions as $action) {
      array_push($actionsList, $action['id']);
    }
    $html .= '<h1> Create a new Action </h1>';
    $html .= '<form id="mform1" enctype="multipart/form-data" action="add.php" method="post">';
    $html .= '<select name="id">';
    foreach($events as $event) {
      if (!in_array($event, $actionsList)) {
        $html .= '<option>'.$event.'</option>';
      }
    }
    $html .= '</select>';
    $html .= '<div id="extra"></div>';
    $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
    $html .= '</form>';
    echo '<div id="metrics" class="hidden" style="display:none">';
    print(json_encode($metrics));
    echo '</div>';
    echo '<div id="courses" class="hidden" style="display:none">';
    print(json_encode($coursesList));
    echo '</div>';
    $html .= '<button id="add">Add Rule</button>';
    echo $html;
    echo $OUTPUT->footer();
}
?>
<script>
    // var app = {};
    // app.view = function(ctrl) {
    //   return [
    //     m("button", {onclick: ctrl.rotate}, "Rotate links")
    //   ];
    // };
    // m.module(document.getElementById("extra"), app);

    function selectCourse(courses, index) {
      var html = '<select name="courses['+index+']">';
      for(var i=0; i<courses.length; i++){
        html += '<option>'+courses[i].name+'</option>';
      }
      html += '</select>';
      return html;
    }

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
      var courses = JSON.parse($('#courses').html());
      $('#add').click(function() {
        $('#extra').append(
          '<div class="box generalbox authsui" id="rule'+index+'">'
          +'Rule: '+(index+1)
          +'<table class="admintable generaltable" id="aaa">'
          +'<thead>'
          +'<tr>'
          +'<th class="header c0 leftalign" style="" scope="col">Requires</th>'
          +'<th class="header c1 lastcol rightalign" style="" scope="col">Rewards</th>'
          +'</tr>'
          +'</thead>'
          +'<tbody>'
          +'<tr class="r0">'
          +'<td>'
          +'Course:'+selectCourse(courses, index)
          +'</td>'
          +'<td>'
          +'Metric:'+generateSelect(metrics, index)
          +'Verb: '+createVerb(index)
          +'Value: <input name="values['+index+']" type="number" value="1" required />'
          +'</td>'
          +'</tr>'
          +'</tbody>'
          +'</table>'
          +'</div>'
        );
        index++;
      });
    });
</script>
