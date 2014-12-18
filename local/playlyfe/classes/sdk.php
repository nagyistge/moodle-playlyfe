<?php

require_once('/var/www/html/vendor/autoload.php');
use Playlyfe\Sdk\Playlyfe;

$client_id = get_config('playlyfe', 'client_id');
$client_secret = get_config('playlyfe', 'client_secret');
 if ($client_id === null or $client_secret === null) {
  throw new Exception('Please set your client_id and client_secret in the Playlyfe Plugin Settings Page');
}

$pl = new Playlyfe(array(
  'client_id' => $client_id,
  'client_secret' => $client_secret,
  'type' => 'client',
  'store' => function($token) {
    set_config('access_token', $token['access_token'], 'playlyfe');
    set_config('expires_at', $token['expires_at'], 'playlyfe');
  },
  'load' => function() {
    $access_token = array(
      'access_token' => get_config('playlyfe', 'access_token'),
      'expires_at' => get_config('playlyfe', 'expires_at')
    );
    return $access_token;
  }
));

function create_rules($var_name, &$rules, $id, $metrics, $values) {
  $i = 0;
  $rule = array(
    'rewards' => array(),
    'requires' => array(
      'type' => 'var',
      'context' => array(
        'lhs' => '$vars.'.$var_name,
        'operator' => 'eq',
        'rhs' => (string)$id
      )
    )
  );
  foreach($metrics as $metric) {
    $metric_id = $metric;
    $type = 'point';
    $value = $values[$i];
    $split_value = explode(':', $metric);
    if(count($split_value) > 1) {
      $metric_id = $split_value[0];
      $type = 'set';
      $value = array();
      $value[$split_value[1]] = $values[$i];
    }
    $reward = array(
      'metric' => array(
        'id' => $metric_id,
        'type' => $type
      ),
      'verb' => 'add',
      'value' => $value
    );
    $i++;
    array_push($rule['rewards'], $reward);
  }
  array_push($rules, $rule);
}

function patch_action($var_name, $action, $id, $metrics, $values) {
  $i = 0;
  $rewards = array();
  foreach($metrics as $metric) {
    $metric_id = $metric;
    $type = 'point';
    $value = $values[$i];
    $split_value = explode(':', $metric);
    if(count($split_value) > 1) {
      $metric_id = $split_value[0];
      $type = 'set';
      $value = array();
      $value[$split_value[1]] = $values[$i];
    }
    $reward = array(
      'metric' => array(
        'id' => $metric_id,
        'type' => $type
      ),
      'verb' => 'add',
      'value' => $value
    );
    $i++;
    array_push($rewards, $reward);
  }
  unset($action['id']);
  unset($action['name']);
  unset($action['_errors']);
  $action['requires'] = (object)array();
  $has_course = false;
  $new_rules = array();
  foreach($action['rules'] as $rule) {
    if ($rule['requires']['context']['rhs'] == $id) {
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
          'lhs' => '$vars.'.$var_name,
          'operator' => 'eq',
          'rhs' => (string)$id
        )
      ),
      'rewards' => $rewards
    ));
  }
  return $action;
}

function get_cmid($cmid) {
  global $CFG, $DB;
  if (!$cmrec = $DB->get_record_sql("SELECT cm.*, md.name as modname
                             FROM {course_modules} cm,
                                  {modules} md
                             WHERE cm.id = ? AND
                                   md.id = cm.module", array($cmid))){
      print_error('invalidcoursemodule');
  } elseif (!$modrec =$DB->get_record($cmrec->modname, array('id' => $cmrec->instance))) {
      print_error('invalidcoursemodule');
  }
  $modrec->instance = $modrec->id;
  $modrec->cmid = $cmrec->id;
  $cmrec->name = $modrec->name;

  return array($modrec, $cmrec);
}

function set_leaderboards($post, $metrics, $courses, $key) {
  global $pl;
  if(!array_key_exists('leaderboards', $post)) {
    $post['leaderboards'] = array();
  }
  set_config($key, json_encode($post['leaderboards']), 'playlyfe');
  foreach($metrics as $metric) {
    if($metric['type'] == 'point') {
      $metric_id = $metric['id'];
      foreach($courses as $course) {
        if(in_array($metric_id, $post['leaderboards'])) {
          $pl->post('/admin/leaderboards/'.$metric_id.'/course'.$course->id, array());
        }
        else {
          $pl->delete('/admin/leaderboards/'.$metric_id.'/course'.$course->id, array());
        }
      }
    }
  }
}


function get_leaderboards($key) {
  $leaderboards = json_decode(get_config('playlyfe', $key));
  if(!is_array($leaderboards)) {
    $leaderboards = array();
  }
  return $leaderboards;
}

function create_reward_table($id, $var_id, $metrics, $action) {
  global $PAGE;
  $html = '<table id="treward_'.$id.'" class="generaltable">'; //admintable
  $html .= '<thead>';
  $html .= '<tr>';
  $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Metric</th>';
  $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Value</th>';
  $html .= '</tr>';
  $html .= '</thead>';
  $html .= '<tbody>';
  $html .= '</tbody>';
  $html .= '</table>';
  $html .= '<p><button type="button" id="add_'.$id.'">Add Reward</button></p>';
  $data = array(
    'id' => $id,
    'metrics' => $metrics,
    'rewards' => array()
  );
  foreach($action['rules'] as $rule) {
    if ($rule['requires']['context']['rhs'] == $var_id) {
      $data['rewards'] = $rule['rewards'];
    }
  }
  $PAGE->requires->js_init_call('init_table', array($data));
  $PAGE->requires->js_init_call('add_handler', array($data));
  return $html;
}
