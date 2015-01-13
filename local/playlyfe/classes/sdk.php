<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/vendor/autoload.php');
use Playlyfe\Sdk\Playlyfe;

$client_id = get_config('playlyfe', 'client_id');
$client_secret = get_config('playlyfe', 'client_secret');
if (!$client_id or !$client_secret) {
  echo('Please set your client_id and client_secret in the Playlyfe Plugin Settings Page');
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

function create_requires($id, $post) {
  $requires = array();
  if(array_key_exists('condition_type', $post)) {
    if(count($post['condition_type'][$id]) === 1) {
      $requires = array(
        'type' => 'var',
        'context' => array (
          'lhs' => '$vars.score',
          'operator' => $post['condition_operator'][$id][0],
          'rhs' => $post['condition_value'][$id][0]
        )
      );
    }
    else {
      $expression = array();
      $index = 0;
      foreach ($post['condition_type'][$id] as $value) {
        array_push($expression, array(
          'type' => 'var',
          'context' => array (
            'lhs' => '$vars.score',
            'operator' => $post['condition_operator'][$id][$index],
            'rhs' => $post['condition_value'][$id][$index]
          )
        ));
        $index++;
      }
      $requires = array(
        'type' => 'and',
        'expression' => $expression
      );
    }
  }
  return $requires;
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

function patch_rule($rule, $post) {
  global $pl;
  $id = $rule['id'];
  unset($rule['id']);
  unset($rule['name']);
  if(array_key_exists('metrics', $post) and array_key_exists($id, $post['metrics'])) {
    $rule['rules'] = array(
      array(
        'rewards' => create_reward($post['metrics'][$id], $post['values'][$id]),
        'requires' => (object) create_requires($id, $post)
      )
    );
    try {
      // print_object($rule);
      $pl->patch('/design/versions/latest/rules/'.$id, array(), $rule);
    }
    catch(Exception $e) {
      print_object($e);
    }
  }
  else {
    $rule['rules'] = array();
    try {
      $pl->patch('/design/versions/latest/rules/'.$id, array(), $rule);
    }
    catch(Exception $e) {
      print_object($e);
    }
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

function create_leaderboard($id, $name, $scope_id) {
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
    $html .= '<div class="leaderboard-header">';
    $html .= '<h4> Leaderboards for '.$name.' </h4><hr></hr></div><ul class="leaderboard-list">';
    $html .= '<table class="leadeboard-table">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Rank</th>';
    $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Avatar</th>';
    $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">User</th>';
    $html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Score</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    // foreach ($metrics as $metric) {
    //   if($metric['type'] === 'point') {
    //     $html .= '<tr>';
    //     $html .= '<td>'.$metric['name'].'</td>';
    //     $html .= '<td class="pl-leaderboard-checkbox">';
    //     $check_text = '';
    //     if(in_array($metric['id'], $leaderboards)){
    //       $check_text = 'checked';
    //     }
    //     $html .= '<input value="'.$metric['id'].'" name="leaderboards[]" type="checkbox" '.$check_text.'>';
    //     $html .= '</td>';
    //     $html .= '</tr>';
    //   }
    // }
    foreach($leaderboard['data'] as $player) {
      $html .= '<tr>';
      $html .= '<td><b>'.$player['rank'].'</b></td>';
      $html .= '<td>';
      $user = $DB->get_record('user', array('id' => '2'));
      $html .= $OUTPUT->user_picture($user, array('size'=>50));
      $html .= '</td>';
      $html .= '<td><b>'.$player['player']['alias'].'</b></td>';
      $html .= '<td><b>'.$player['score'].'</b></td>';
      // $score = $player['score'];
      // $id = $player['player']['id'];
      // $alias = $player['player']['alias'] or 'Null';
      // $rank = $player['rank'];
      // $list = explode('u', $id);
      // if($rank < 10) {
      //   $rank = '0'.$rank;
      // }
      // if($id === 'u'.$USER->id) {
      //   $html .= "<li class='fb-leaderboard-player fb-leaderboard-player-selected'>";
      //   $html .= '<div class="fb-leaderboard-player-rank">'.$rank.'</div>';
      // }
      // else {
      //   $html .= "<li class='fb-leaderboard-player'>";
      //   $html .= '<div class="fb-leaderboard-player-rank">'.$rank.'</div>';
      // }
      // $user = $DB->get_record('user', array('id' => '2'));
      // $html .= $OUTPUT->user_picture($user, array('size'=>75));
      // //$user = $DB->get_record('user', array('id' => $list[1]));
      // //$html .= $OUTPUT->user_picture($user, array('size'=>100));
      // $html .= '<div class="fb-leaderboard-player-score">'.$score.'</div>';
      // $html .= '<div class="fb-leaderboard-player-alias">'.$alias.'</div></li>';
    }
    $html .= '</tbody>';
    $html .= '</table>';
    if(count($leaderboard['data']) === 0) {
      $html .= 'The leaderboard is empty';
    }
    // $html .= '</ul>';
  }
  catch(Exception $e) {
    if($e->name === 'player_not_found') {
    }
    else {
      //mtrace(json_encode($e));
    }
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
    $pl = get_pl();
    try {
      $metrics = $pl->get('/design/versions/latest/metrics', array('fields' => 'name,id,type'));
      $metricsList = array();
      foreach ($metrics as $metric) {
        if($metric['type'] === 'point') {
          array_push($metricsList, $metric);
        }
      }
      if(count($events) > 0 and array_key_exists('0', $events['local'])) {
        $event = $events['local'][0];
        if($event['event'] == 'custom_rule') {
          array_push($data['events'], $event);
          $rule_id = $event['rule']['id'];
          $rule_id = explode('_', $rule_id);
          $text = '';
          if(in_array('course', $rule_id)) {
            foreach($metricsList as $metric) {
              $text .= create_leaderboard($metric['id'], $metric['name'], 'course'.$rule_id[1]);
            }
          }
          array_push($data['leaderboards'], $text);
        }
      }
    }
    catch (Exception $e) {
    }
  }
  set_buffer($userid, array());
  return $data;
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

function display_change($change, $course_name, $date) {
  global $count;
  $text = '';
  $metric= $change['metric'];
  $delta = $change['delta'];
  if ($metric['type'] == 'point') {
    $value = $delta['new'] - $delta['old'];
  }
  else {
    foreach($delta as $key => $value) {
      $value = ($value['new'] - $value['old']).' x '.$key;
      $value .= '     <img src="image_def.php?metric='.$metric['id'].'&size=medium&item='.$key.'"></img>    ';
    }
  }

  $text .= '
    <div class="log-item">
      <div class="log-content media">
        <div class="image avatar"><img src="image_def.php?metric='.$metric['id'].'&size=medium"></img></div>
        <div class="content">
          <span class="log-actor">You</span> have gained
          <span class="score-value">'.$value.'</span>
          <span class="score-metric">'.$metric['name'].'</span>
          in the course <span class="log-target">'.$course_name.'</span>
        </div>
      </div>
      <div class="log-footer" title="'.$date->format('Y-m-d H:i:s').'">
        <time class="log-timestamp" datetime="'.$date->format('Y-m-d H:i:s').'">'.$date->format('D, jS M').'</time>
      </div>
    </div>';
  $count++;
  return $text;
}

function formatDateAgo($value) {
  $time = strtotime($value);
  $d = new \DateTime($value);

  $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  $months = ['Janvier', 'Février', 'Mars', 'Avril',' Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

  if ($time > strtotime('-2 minutes'))
  {
      return 'a few seconds ago';
  }
  elseif ($time > strtotime('-30 minutes'))
  {
      return floor((strtotime('now') - $time)/60) . ' minutes ago';
  }
  elseif ($time > strtotime('today'))
  {
      return $d->format('G:i');
  }
  elseif ($time > strtotime('yesterday'))
  {
      return 'Hier, ' . $d->format('G:i');
  }
  elseif ($time > strtotime('this week'))
  {
      return $weekDays[$d->format('N') - 1] . ', ' . $d->format('G:i');
  }
  else
  {
      return $d->format('j') . ' ' . $months[$d->format('n') - 1] . ', ' . $d->format('G:i');
  }
}

class PForm {
  public $html;
  public $requiredHtml = '<img class="req" title="Required field" alt="Required field" src="http://127.0.0.1:3000/theme/image.php/standard/core/1420616075/req">';

  function __construct($title, $path='', $method='post') {
    $this->html .= '<form class="mform" enctype="multipart/form-data" action="'.$path.'" method="'.$method.'">';
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

  public function create_condition_table($rule) {
    global $PAGE;
    $this->html .= '<table id="tcondition_'.$rule['id'].'" class="generaltable">';
    $this->html .= '<thead>';
    $this->html .= '<tr>';
    $this->html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Type</th>';
    $this->html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Operator</th>';
    $this->html .= '<th class="header c1 lastcol centeralign" style="" scope="col">Value</th>';
    $this->html .= '</tr>';
    $this->html .= '</thead>';
    $this->html .= '<tbody>';
    $this->html .= '</tbody>';
    $this->html .= '</table>';
    $this->html .= '<button type="button" id="add_condition_'.$rule['id'].'">Add Condition</button>';
    $PAGE->requires->js_init_call('init_condition_table', array($rule));
  }

  public function end() {
    $this->html .= '<div id="extra"></div>';
    $this->html .= '<div class="fitem fitem_actionbuttons fitem_fgroup"><div class="felement fgroup"><input type="submit" name="submit" value="Submit" /></div></div>';
    $this->html .= '</div></fieldset></form>';
    echo $this->html;
  }
  public function getFinalContents() {
    $this->html .= '<div id="extra"></div>';
    $this->html .= '<div class="fitem fitem_actionbuttons fitem_fgroup"><div class="felement fgroup"><input type="submit" name="submit" value="Submit" /></div></div>';
    $this->html .= '</div></fieldset></form>';
    return $this->html;
  }
}
