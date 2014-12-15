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
$metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'id,type,constraints'));

if (array_key_exists('id', $_POST)) {
  $id = $_POST['id'];
  $i = 0;
  $rewards = array();
  if(array_key_exists('metrics', $_POST)) {
    foreach($_POST['metrics'] as $metric) {
      $type = 'point';
      foreach($metrics as $pl_metric) {
        if($pl_metric['id'] == $metric) {
          $type = $pl_metric['type'];
          break;
        }
      }
      $verb = $_POST['verbs'][$i];
      if(array_key_exists($i, $_POST['badges'])) {
        $value = array();
        $value[$_POST['badges'][$i]] = $_POST['values'][$i];
      }
      else {
        $value = $_POST['values'][$i];
      }
      $reward = array(
        'metric' => array(
          'id' => $metric,
          'type' => $type
        ),
        'verb' => $verb,
        'value' => $value
      );
      $i++;
      array_push($rewards, $reward);
    }
  }
  unset($action['id']);
  unset($action['_errors']);
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
    $pl->post('/admin/leaderboards/'.$leaderboard_metric.'/course'.$id, array());
  }
  $course['rewards'] = $rewards;
  redirect(new moodle_url('/local/playlyfe/course.php'));
} else {
  $id = required_param('id', PARAM_TEXT);
  $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
  $modinfo = get_fast_modinfo($course);
  $modnames = get_module_types_names();
  $modnamesplural = get_module_types_names(true);
  $modnamesused = $modinfo->get_used_module_names();
  $mods = $modinfo->get_cms();
  $sections = $modinfo->get_section_info_all();
  //print_object($modnamesused);
  //print_object($sections);

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
  echo $OUTPUT->header();
  $html .= "<h1> Edit Course - $name </h1>";
  $html .= '<form id="mform1" action="set_course.php" method="post">';
  $html .= '<input name="id" type="hidden" value="'.$id.'"/>';
  $html .= '<h2> Enable Leaderboard </h2>';
  $html .= '<div id="leaderboard">';
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
  function selectItem(metric, index, reward) {
    var html = '<select style="margin-right: 20px;float: left;" id="badges_'+index+'" name="badges['+index+']">';
    //html += '<span style="float: left">Badge:  </span>';
    for(var j=0; j < metric.constraints.items.length; j++) {
      var item = metric.constraints.items[j].name;
      if(reward !== undefined && metric.id === reward.metric.id) {
        html += '<option selected="selected">'+item+'</option>';
      }
      else {
        html += '<option>'+item+'</option>';
      }
    }
    html += '</select>';
    return html;
  }

  function selectMetric(metrics, index, reward) {
    var html = '<select id="metrics_'+index+'" name="metrics['+index+']">';
    for(var i=0; i<metrics.length; i++){
      if(reward !== undefined && metrics[i].id === reward.metric.id) {
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
    close_button = '<a style="float:right" id="close'+index+'">remove</a>';
    if(reward !== undefined) {
      var value = reward.value;
      if(reward.metric.type === 'set') {
        value = reward.value[Object.keys(reward.value)[0]];
      }
      html += '<td><div id="col'+index+'"><input name="values['+index+']" type="number" value="'+value+'" required />'+close_button+'</div></td>';
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

  function addSet(metrics, i, reward) {
    (function(i) {
      if(reward !== undefined && reward.metric.type === 'set') {
        for(var k=0; k<metrics.length; k++) {
          if (metrics[k].id === reward.metric.id) {
            $('#col'+i).prepend(selectItem(metrics[k], i, reward));
            break;
          }
        }
      }
      $('#metrics_'+i).change(function(event) {
        var value = $(this).find("option:selected").val();
        for(var k=0; k<metrics.length; k++) {
          if (metrics[k].id === value) {
            $('#badges_'+i).remove();
            if(metrics[k].type === 'set') {
              $('#col'+i).prepend(selectItem(metrics[k], i));
            }
          }
       }
      });
    })(i);
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
        addSet(metrics, i, rewards[i]);
        (function(i) {
          $('#close'+i).click(function() {
            $('#row'+i).remove();
          });
        })(i);
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
      addSet(metrics, index);
      (function(i) {
        $('#close'+i).click(function() {
          $('#row'+i).remove();
        });
      })(index);
      index++;
    });
  });
</script>
