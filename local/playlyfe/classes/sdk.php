<?php

require_once('/var/www/html/vendor/autoload.php');
use Playlyfe\Sdk\Playlyfe;

class local_playlyfe_sdk {

    public static function get_pl() {
      $client_id = get_config('playlyfe', 'client_id');
      $client_secret = get_config('playlyfe', 'client_secret');
      if ($client_id === null or $client_secret === null) {
        throw new Exception('Please set your client_id and client_secret in the Playlyfe Plugin Settings Page');
      }
      return new Playlyfe(
        array(
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
        )
      );
    }
}

function patch_action($action, $metrics, $post, $var_name) {
  $id = $post['id'];
  $i = 0;
  $rewards = array();
  if(array_key_exists('metrics', $post)) {
    foreach($post['metrics'] as $metric) {
      $metric_id = $metric;
      $type = 'point';
      $value = $post['values'][$i];
      $split_value = explode(':', $metric);
      if(count($split_value) > 1) {
        $metric_id = $split_value[0];
        $type = 'set';
        $value = array();
        $value[$split_value[1]] = $post['values'][$i];
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
  }
  unset($action['id']);
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
          'rhs' => $id
        )
      ),
      'rewards' => $rewards
    ));
  }
  return $action;
}

function get_module_from_cmid($cmid) {
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
