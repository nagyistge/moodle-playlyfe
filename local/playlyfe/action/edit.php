<?php
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require(dirname(dirname(__FILE__)).'/classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/action/edit.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->settingsnav->get('root')->get('playlyfe')->get('actions')->get('add')->make_active();
$PAGE->navigation->clear_cache();
$PAGE->requires->jquery();
$html = '';
global $DB;

$pl = local_playlyfe_sdk::get_pl();

if (array_key_exists('id', $_POST)) {
    $event_name = $_POST['id'];
} else {
    $id = required_param('id', PARAM_TEXT);
    $action = $pl->get('/design/versions/latest/actions/'.$id);
    $courses = $DB->get_records('course', array());
    $coursesList = array();
    foreach($courses as $course) {
      array_push($coursesList, array('id' => $course->id,'name' => $course->fullname));
    }
    $metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
    echo $OUTPUT->header();
    $html .= "<h1> Editing Action - $id </h1>";
    $html .= '<form id="mform1" action="edit.php" method="post">';
    $html .= '<input name="id" type="hidden" value="'.$id.'"/>';
    $html .= '<div id="extra"></div>';
    $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
    $html .= '</form>';
    echo '<div id="rules" class="hidden" style="display:none">';
    print(json_encode($action['rules']));
    echo '</div>';
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
    function selectCourse(courses, index) {
      var html = '<select name="courses['+index+']" id="courses_'+index+'">';
      for(var i=0; i<courses.length; i++){
        html += '<option>'+courses[i].name+'</option>';
      }
      html += '</select>';
      return html;
    }

    function selectMetric(metrics, index) {
      var html = '<select name="metrics['+index+']" id="metrics_'+index+'">';
      for(var i=0; i<metrics.length; i++) {
        html += '<option>'+metrics[i].id+'</option>';
      }
      html += '</select>';
      return html;
    }

    function selectSet(metric, index) {
      var html = '<select name="items['+index+']" id="items_'+index+'">';
      for(var i=0; i<metric.constraints.items.length; i++){
        html += '<option>'+metric.constraints.items[i].name+'</option>';
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

    function addEvent(metrics, index) {
      $('#metrics_'+index).change(function(event) {
          var value = $(this).find("option:selected").val();
          for(var i =0; i<metrics.length;i++) {
            if (metrics[i].id === value) {
              console.log(metrics[i]);
              if(metrics[i].type === 'set') {
                console.log(index);
                $('set_'+index).append('Set:'+selectSet(metrics[i], index));
              }
              else {
                //('set_'+index).remove();
              }
            }
          }
        });
    }

    $(function() {
      var index = 0;
      var metrics = JSON.parse($('#metrics').html());
      var courses = JSON.parse($('#courses').html());
      var rules = JSON.parse($('#rules').html());
      for(; index < rules.length; index++) {
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
          +'JSON'+JSON.stringify(rules[index].requires)+JSON.stringify(rules[index].rewards)
          +'</td>'
          +'<td>'
          +'Metric:'+selectMetric(metrics, index)
          +'Verb: '+createVerb(index)
          +'<div id="set_'+index+'"></div>'
          +'Value: <input id="values_'+index+'" name="values['+index+']" type="number" value="1" required />'
          +'</td>'
          +'</tr>'
          +'</tbody>'
          +'</table>'
          +'</div>'
        )
        addEvent(metrics, index);
      }
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
          +'Metric:'+selectMetric(metrics, index)
          +'Verb: '+createVerb(index)
          +'Value: <input name="values['+index+']" type="number" value="1" required />'
          +'</td>'
          +'</tr>'
          +'</tbody>'
          +'</table>'
          +'</div>'
        );
        addEvent(metrics, index);
        index++;
      });
    });
</script>
