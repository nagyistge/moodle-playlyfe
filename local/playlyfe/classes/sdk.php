<?php

require_once('/var/www/html/vendor/autoload.php');
use Playlyfe\Sdk\Playlyfe;

$client_id = get_config('playlyfe', 'client_id');
$client_secret = get_config('playlyfe', 'client_secret');
 if ($client_id === null or $client_secret === null) {
  throw new Exception('Please set your client_id and client_secret in the Playlyfe Plugin Settings Page');
}

try {
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
}
catch(Exception $e) {
  echo 'Please Check your Client ID and Client Secret. They seem to be incorrect. And make sure you are using a Whitelabel Client';
}


function get_pl() {
  global $client_id, $client_secret;
  return new Playlyfe(array(
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
}

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
    // if(is_array($var_name)) {
    //   $requires = array();
    //   foreach($var_name as $key => $value) {
    //     $requires[$key] = $value;
    //   }
    // }
    // else {
      $requires = array(
        'type' => 'var',
        'context' => array(
          'lhs' => '$vars.'.$var_name,
          'operator' => 'eq',
          'rhs' => (string)$id
        )
      );
    // }
    array_push($action['rules'], array(
      'requires' => $requires,
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
  $data = json_decode(get_config('playlyfe', $key));
  if(!is_array($data)) {
    $data = array();
  }
  return $data;
}

function get($key) {
  $data = json_decode(get_config('playlyfe', $key), true);
  if(!is_array($data)) {
    $data = array();
  }
  return $data;
}

function set($key, $value = array()) {
  set_config($key, json_encode($value), 'playlyfe');
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

function create_reward($metrics, $values) {
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
  return $rewards;
}


function create_rule_table($rule, $metrics) {
  global $PAGE;
  $id = $rule['id'];
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
  if(count($rule['rules']) > 0) {
    $rewards = $rule['rules'][0]['rewards'];
  } else {
    $rewards = array();
  }
  $data = array(
    'id' => $id,
    'metrics' => $metrics,
    'rewards' => $rewards
  );
  $PAGE->requires->js_init_call('init_table', array($data));
  $PAGE->requires->js_init_call('add_handler', array($data));
  return $html;
}

function get_rule($id, $event, $context = '', $name) {
  global $pl, $PAGE;
  if ($PAGE->context->contextlevel == 50) { //CONTEXT_COURSE
    $context = 'course';
  }
  if ($PAGE->context->contextlevel == 70) { //CONTEXT_MODULE
    $context = $PAGE->activityname;
  }
  if(!$name) {
    $name = $context.'_'.$id.'_'.$event;
  }
  try {
    return $pl->get('/design/versions/latest/rules/'.$context.'_'.$id.'_'.$event);
  }
  catch(Exception $e) {
    if($e->name == 'rule_not_found') {
      $rule = array(
        'id' => $context.'_'.$id.'_'.$event,
        'name' => $name,
        'type' => 'custom',
        'rules' => array()
      );
      try {
        return $pl->post('/design/versions/latest/rules', array(), $rule);
      }
      catch(Exception $e) {
        print_object($e);
      }
    }
    else {
      print_object($e);
    }
  }
}

function patch_rule($rule, $metrics, $values) {
  global $pl;
  $id = $rule['id'];
  unset($rule['id']);
  unset($rule['name']);
  $rule['rules'] = array(
    array(
      'rewards' => create_reward($metrics, $values),
      'requires' => (object)array()
    )
  );
  try {
    return $pl->patch('/design/versions/latest/rules/'.$id, array(), $rule);
  }
  catch(Exception $e) {
    print_object($e);
  }
}

function get_buffer($userid) {
  return json_decode(get_config('playlyfe', 'u'.$userid.'_buffer'), true);
}

function set_buffer($userid, $data) {
  set_config('u'.$userid.'_buffer', json_encode($data), 'playlyfe');
}

function add_to_buffer($userid, $events) {
  $buffer = get_buffer($userid);
  array_push($buffer, $events);
  set_buffer($userid, $buffer);
}
// Activity Stream in a Course Level, Team Activity within course
// Course Progress
// Skill Level
// Commenting
// Forums
// Grades

function create_leaderboard($id, $scope_id) {
  global $USER, $DB, $OUTPUT;
  $pl = get_pl();
  $html = '';
  try {
    $leaderboard = $pl->get('/runtime/leaderboards/'.$id, array(
      'player_id' => 'u'.$USER->id,
      'cycle' => 'alltime',
      'scope_id' => $scope_id
    ));
    $html .= '<h3> Leaderboards for '.$id.' </h3><ul>';
    foreach($leaderboard['data'] as $player) {
      $score = $player['score'];
      $id = $player['player']['id'];
      $alias = $player['player']['alias'] or 'Null';
      $rank = $player['rank'];
      $list = explode('u', $id);
      $user = $DB->get_record('user', array('id' => $list[1]));
      $html .= "<li class='list-group-item'>";
      $html .= $OUTPUT->user_picture($user, array('size'=>50));
      $html .= "<b>$rank $alias $score</b></li>";
    }
    $html .= '</ul>';
  }
  catch(Exception $e) {
    //print_object($e);
  }
  return $html;
}
