<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/set_course.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
$PAGE->requires->jquery();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
global $USER, $DB;
$pl = local_playlyfe_sdk::get_pl();
$html = '';

$action = $pl->get('/design/versions/latest/actions/course_completed');

if (array_key_exists('id', $_POST)) {
  $id = $_POST['id'];
  $i = 0;
  $course = array('id' => $id, 'leaderboard' => null);
  $rewards = array();
  if(array_key_exists('metrics', $_POST)) {
    foreach($_POST['metrics'] as $metric) {
      $reward = array(
        'metric' => array(
          'id' => $metric,
          'type' => 'point'
        ),
        'verb' => $_POST['verbs'][$i],
        'value' => $_POST['values'][$i]
      );
      $i++;
      array_push($rewards, $reward);
    }
  }
  unset($action['id']);
  $action['requires'] = (object)array();
  $has_course = false;
  $new_rules = array();
  foreach($action['rules'] as $rule) {
    if ($rule['requires']['context']['rhs'] == '"'.$id.'"') {
      $has_course = true;
      $rule['rewards'] = $rewards;
    }
    array_push($new_rules, array(
      'requires' => $rule['requires'],
      'rewards' => $rule['rewards']
    ));
  }
  $action['rules'] = $new_rules;
  if(!$has_course) {
    array_push($action['rules'], array(
      'requires' => array(
        'type' => 'var',
        'context' => array(
          'lhs' => '$vars.course_id',
          'operator' => 'eq',
          'rhs' => '"'.$id.'"'
        )
      ),
      'rewards' => $rewards
    ));
  }
  $pl->patch('/design/versions/latest/actions/course_completed', array(), $action);
  if(array_key_exists('leaderboard_metric', $_POST)) {
    $leaderboard_metric = $_POST['leaderboard_metric'];
    set_config('course'.$id, $leaderboard_metric, 'playlyfe');
    $course['leaderboard'] = $leaderboard_metric;
    $pl->post('/admin/leaderboards/'.$leaderboard_metric.'/course'.$id, array());
    $pl->post('/runtime/actions/course_completed/play', array('player_id' => 'u2'), array(
      'scopes' => array(
        array(
          'id' => $leaderboard_metric.'/'.'course'.$id,
          'entity_id' => 'u'.$USER->id
        )
      ),
      'variables' => array(
        'course_id' => $id
      )
    ));
  }
  $course['rewards'] = $rewards;
  redirect(new moodle_url('/local/playlyfe/course.php'));
} else {
  $id = required_param('id', PARAM_TEXT);

  $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
  $name = $course->fullname;
  $rewards = array();

  foreach($action['rules'] as $rule) {
    if ($rule['requires']['context']['rhs'] == '"'.$id.'"') {
      $rewards = $rule['rewards'];
    }
  }
  $course_data = array(
    'leaderboard' => get_config('playlyfe', 'course'.$id),
    'rewards' => $rewards
  );
  $metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));
  echo $OUTPUT->header();
  $html .= "<h1> Edit Course - $name </h1>";
  $html .= '<form id="mform1" action="set_course.php" method="post">';
  $html .= '<input name="id" type="hidden" value="'.$id.'"/>';
  $html .= '<div id="leaderboard" class="box generalbox authsui">';
  $html .= '<h2> Enable Leaderboard </h2>';
  $html .= '<input id="leaderboard_enable" name="leadeboard" type="checkbox" />';
  $html .= '</div>';
  $html .= "<h2> Rewards on Course Completion </h2>";
  $html .= '<table id="treward" class="admintable generaltable">';
  $html .= '<thead>';
  $html .= '<tr>';
  $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Metric</th>';
  $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Verb</th>';
  $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Value</th>';
  $html .= '</tr>';
  $html .= '</thead>';
  $html .= '<tbody>';
  $html .= '</tbody>';
  $html .= '</table>';
  $html .= '<input id="submit" type="submit" name="submit" value="Submit" />';
  $html .= '</form>';
  $html .= '<div id="metrics" class="hidden" style="display:none">';
  $html .= json_encode($metrics);
  $html .= '</div>';
  $html .= '<div id="course_data" class="hidden" style="display:none">';
  $html .= json_encode($course_data);
  $html .= '</div>';
  $html .= '<button id="add">Add Reward</button>';
  echo $html;
  echo $OUTPUT->footer();
}
?>
<script>
  function selectBadges(metrics, index) {
  }
  function selectMetric(metrics, index, reward) {
    var html = '<select id="metrics_'+index+'" name="metrics['+index+']">';
    for(var i=0; i<metrics.length; i++){
      if(reward !== undefined && metrics[i].id === reward.metric) {
        html += '<option selected="selected">'+metrics[i].id+'</option>';
      }
      else {
        html += '<option>'+metrics[i].id+'</option>';
      }
    }
    html += '</select>';
    return html;
  }

  function selectVerb(index, reward) {
    var verbs = ['set', 'add', 'remove'];
    var html = '<select name="verbs['+index+']">';
    for(var i=0;i<verbs.length;i++) {
      if(reward !== undefined && reward.verb === verbs[i]) {
        html += '<option selected="selected">'+verbs[i]+'</option>';
      }
      else {
       html += '<option>'+verbs[i]+'</option>';
      }
    }
    html += '</select>';
    return html;
  }

  function addReward(metrics, index, reward) {
    var html = '<tr id="row'+index+'" class="r'+index+' centeralign">';
    html += '<td>'+selectMetric(metrics, index, reward)+'</td>';
    html += '<td>'+selectVerb(index, reward)+'</td>';
    //var close_button = '';
    close_button = '<a id="close'+index+'">remove</a>';
    $('#close'+index).click(function(){
      console.log('remove');
      $('#row'+i).remove();
    });
    if(reward !== undefined) {
      html += '<td><div id="col'+index+'"><input name="values['+index+']" type="number" value="'+reward.value+'" required />'+close_button+'</div></td>';
    }
    else {
      html += '<td><div id="col'+index+'"><input name="values['+index+']" type="number" value="1" required />'+close_button+'</div></td>';
    }
    html += '</tr>';
    return html;
  }

  function selectLeaderboard(metrics, leaderboard) {
    var html = '<div id="leaderboard_metric"><b>Leaderboard for the Metric:</b><select name="leaderboard_metric">';
    for(var i=0; i<metrics.length; i++){
      if(metrics[i].type === 'point') {
        if(leaderboard !== null && metrics[i].id === leaderboard) {
          html += '<option selected="selected">'+metrics[i].id+'</option>';
        }
        else {
          html += '<option>'+metrics[i].id+'</option>';
        }
      }
    }
    html += '</select></div>';
    return html;
  }

  $(function() {
    var index = 0;
    var metrics = JSON.parse($('#metrics').html());
    var course_data = JSON.parse($('#course_data').html());
    if(course_data !== null) {
      var leaderboard = course_data.leaderboard;
      var rewards = course_data.rewards;
    }
    else {
      leaderboard = null;
      rewards = null;
    }

    if(leaderboard !== null) {
      $('#leaderboard').append(selectLeaderboard(metrics, leaderboard));
      $('#leaderboard_enable').prop('checked', true);
    }
    if(rewards !== null) {
      for(var i=0; i<rewards.length; i++) {
        $('#treward tbody').append(addReward(metrics, i, rewards[i]));
        (function(i) {
          $('#metrics_'+i).change(function(event) {
            var my_index = i;
            var value = $(this).find("option:selected").val();
            console.log('CHANGED', my_index);
            //$('#row'+i).remove();
            $('#col'+my_index).append('');
          });
        })(i);
          for(var i =0; i<metrics.length;i++) {
            if (metrics[i].id === value) {
              if(metrics[i].type === 'set') {
                //
              }
              else {
                //('set_'+my_index).remove();
              }
            }
          }
        index++;
      }
    }
    $('#leaderboard_enable').click(function() {
      if(this.checked === true) {
        $('#leaderboard').append(selectLeaderboard(metrics, leaderboard));
      }
      else {
        $('#leaderboard_metric').remove();
      }
    });
    $('#add').click(function() {
      $('#treward tbody').append(addReward(metrics, index));
      index++;
    });
  });
</script>
