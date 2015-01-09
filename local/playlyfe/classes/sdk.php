<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/vendor/autoload.php');
use Playlyfe\Sdk\Playlyfe;

$client_id = get_config('playlyfe', 'client_id');
$client_secret = get_config('playlyfe', 'client_secret');
if (!$client_id or !$client_secret) {
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

function set_leaderboards($post, $metrics, $course, $key) {
  global $pl;
  if(!array_key_exists('leaderboards', $post)) {
    $post['leaderboards'] = array();
  }
  set_config($key, json_encode($post['leaderboards']), 'playlyfe');
  foreach($metrics as $metric) {
    if($metric['type'] == 'point') {
      $metric_id = $metric['id'];
      if(in_array($metric_id, $post['leaderboards'])) {
        $pl->post('/admin/leaderboards/'.$metric_id.'/course'.$course->id, array());
      }
      else {
        $pl->delete('/admin/leaderboards/'.$metric_id.'/course'.$course->id, array());
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
        'rules' => array(),
        'variables' => array(
          array(
            'name' => 'score',
            'type' => 'int',
            'required' => false
          )
        )
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

function patch_rule($rule, $metrics, $values, $requires = array()) {
  global $pl;
  $id = $rule['id'];
  unset($rule['id']);
  unset($rule['name']);
  $rule['rules'] = array(
    array(
      'rewards' => create_reward($metrics, $values),
      'requires' => (object)$requires
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
      'scope_id' => $scope_id,
      'ranking' => 'relative',
      'entity_id' => 'u'.$USER->id
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
    $html = json_encode($e);
  }
  return $html;
}

function calculate_data($userid) {
  $buffer = get_buffer($userid);
  $data = array(
    'events' => array(),
    'leaderboards' => array()
  );
  $leaderboads = array();
  $rule_id = '';
  foreach($buffer as $events) {
    if(count($events) > 0 and array_key_exists('0', $events['local'])) {
      $event = $events['local'][0];
      if($event['event'] == 'custom_rule') {
        array_push($data['events'], $event);
        $rule_id = $event['rule']['id'];
        $rule_id = explode('_', $rule_id);
        $text = '';
        if(in_array('course', $rule_id)) {
          $leaderboard_ids = get_leaderboards('course'.$rule_id[1].'_leaderboard');
          if(count($leaderboard_ids) > 0) {
            foreach($leaderboard_ids as $leaderboard_id) {
              $text .= create_leaderboard($leaderboard_id, 'course'.$rule_id[1]);
            }
          }
        }
        array_push($data['leaderboards'], $text);
      }
    }
  }
  set_buffer($userid, array());
  return $data;
}

function add_to_attempts() {

}

function has_finished_rule($userid, $id) {
  $data = get($userid.'_data');
  if(!in_array($id, $data)) {
    array_push($data, $id);
    set($userid.'_data', $data);
    return false;
  }
  else {
    return true;
  }
}

class PForm {
  public $html;
  public $requiredHtml = '<img class="req" title="Required field" alt="Required field" src="http://127.0.0.1:3000/theme/image.php/standard/core/1420616075/req">';

  function __construct($title, $path='') {
    $this->html .= '<form class="mform" enctype="multipart/form-data" action="'.$path.'" method="post">';
    $this->html .= '<fieldset class="clearfix"><legend class="ftoggler">'.$title.'</legend><div class="advancedbutton"></div><div class="fcontainer clearfix">';
  }

  public function create_file($title, $name) {
    $this->html .= '<div class="fitem required fitem_ftext">';
    $this->html .= '<div class="fitemtitle"><label>'.$title.'</label></div>';
    $this->html .= '<div class="felement ftext"><input name="'.$name.'" type="file"></div></div>';
  }

  public function create_input($title, $name, $value='', $type='text', $required=true) {
    $this->html .= '<div class="fitem';
    if($required) {
      $this->html .= ' required';
    }
    $this->html .= ' fitem_ftext">';
    $this->html .= '<div class="fitemtitle"><label>'.$title;
    if($required) {
      $this->html .= '<img class="req" title="Required field" alt="Required field" src="http://127.0.0.1:3000/theme/image.php/standard/core/1420616075/req">';
    }
    $this->html .= '</label></div>';
    $this->html .= '<div class="felement ftext"><input name="'.$name.'" type="'.$type.'" value="'.$value.'" required></div></div>';
  }

  public function create_button($id, $text) {
    $this->html .= '<button id="'.$id.'" type="button">'.$text.'</button><br>';
  }

  public function create_hidden($name, $value) {
    $this->html .= '<input name="'.$name.'" type="hidden" value="'.$value.'">';
  }

  public function create_checkbox($title, $name, $value, $checked = true, $required = false) {
    $this->html .= '<div class="fitem';
    if($required) {
      $this->html .= ' required';
    }
    $this->html .= ' fitem_ftext">';
    $this->html .= '<div class="fitemtitle"><label>'.$title.'</label>';
    if($required) {
      $this->html .= $this->requiredHtml;
    }
    $this->html .= '</div>';
    $check_text = '';
    if($checked === true) {
      $check_text = 'checked';
    }
    $this->html .= '<div class="felement ftext"><input value="'.$value.'" name="'.$name.'" type="checkbox" '.$check_text.'></div></div>';
  }

  public function create_separator($title='', $text='') {
    $this->html .= '<h3>'.$title.'</h3>';
    $this->html .= '<hr></hr>';
    $this->html .= '<p>'.$text.'</p>';
  }

  public function create_rule_table($rule, $metrics) {
    global $PAGE;
    $id = $rule['id'];
    //$this->html .= '<div id="treward_'.$id.'" class="generaltable">';
    $this->html .= '<table id="treward_'.$id.'" class="generaltable">'; //admintable
    $this->html .= '<thead>';
    $this->html .= '<tr>';
    $this->html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Metric</th>';
    $this->html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Value</th>';
    $this->html .= '</tr>';
    $this->html .= '</thead>';
    $this->html .= '<tbody>';
    $this->html .= '</tbody>';
    $this->html .= '</table>';
    $this->html .= '<button type="button" id="add_'.$id.'">Add Reward</button>';
    //$this->html .= '</div>';
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
  }

  public function create_leaderboard_table($metrics, $leaderboards) {
    $this->html .= '<table class="generaltable">';
    $this->html .= '<thead>';
    $this->html .= '<tr>';
    $this->html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Metric</th>';
    $this->html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Leaderboard</th>';
    $this->html .= '</tr>';
    $this->html .= '</thead>';
    $this->html .= '<tbody>';
    foreach ($metrics as $metric) {
      if($metric['type'] === 'point') {
        $this->html .= '<tr>';
        $this->html .= '<td>'.$metric['name'].'</td>';
        $this->html .= '<td class="pl-leaderboard-checkbox">';
        $check_text = '';
        if(in_array($metric['id'], $leaderboards)){
          $check_text = 'checked';
        }
        $this->html .= '<input value="'.$metric['id'].'" name="leaderboards[]" type="checkbox" '.$check_text.'>';
        $this->html .= '</td>';
        $this->html .= '</tr>';
      }
    }
    $this->html .= '</tbody>';
    $this->html .= '</table>';
  }

  function create_select($name, $options, $selected='') {
    $this->html .= '<select name="'.$name.'" id="'.$name.'">';
    foreach($options as $option => $value) {
      if($selected === $option) {
        $this->html .= '<option value="'.$value.'" selected>'.$option.'</option>';
      }
      else {
        $this->html .= '<option value="'.$value.'">'.$option.'</option>';
      }
    }
    $this->html .= '</select>';
  }

  function create_condition_operator($condition) {
    $this->html .= '<select name="condition_operator" id="condition_operator">';
    if($condition and $condition['operator'] === 'gt') {
      $this->html .= '<option value="gt">greater</option>';
    }
    else {
      $this->html .= '<option value="gt" selected>greater</option>';
    }
    if($condition and $condition['operator'] === 'lt') {
      $this->html .= '<option value="lt" selected>lesser</option>';
    }
    else {
      $this->html .= '<option value="lt">lesser</option>';
    }
    if($condition and $condition['operator'] === 'eq') {
      $this->html .= '<option value="eq" selected>equal</option>';
    }
    else {
      $this->html .= '<option value="eq">equal</option>';
    }
    $this->html .= '</select>';
  }

  public function create_conditions($rule) {
    $this->html .= '<h6>Conditions</h6>';
    $condition = null;
    if(array_key_exists('context', $rule['rules'][0]['requires'])) {
      $condition = $rule['rules'][0]['requires']['context'];
    }
    $selected = 'none';
    if($condition) {
      $selected = 'score';
    }
    $this->create_select('condition_type', array('none' => 'none', 'score' => 'score'), $selected);
    $this->create_condition_operator($condition);
    $this->create_input('Than', 'condition_value', $condition['rhs'], 'number', false);
  }

  public function end() {
    $this->html .= '<div id="extra"></div>';
    $this->html .= '<div class="fitem fitem_actionbuttons fitem_fgroup"><div class="felement fgroup"><input type="submit" name="submit" value="Submit" /></div></div>';
    $this->html .= '</div></fieldset></form>';
    echo $this->html;
  }
}
