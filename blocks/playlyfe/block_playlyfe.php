<?php
class block_playlyfe extends block_base {

  public function init() {
    $this->title = get_string('pluginname', 'block_playlyfe');
  }

  public function get_content() {
    global $USER, $OUTPUT, $DB;
    if ($this->content !== null) {
      return $this->content;
    }
    $this->content =  new stdClass;
    $html = '';
    $pl = get_pl();
    // try {
      switch ($this->config->type) {
        case 0:
          $profile = $pl->get('/runtime/player', array('player_id' => 'u'.$USER->id));
          $html = '<h6>Username</h6>';
          $html .= $profile['alias'];
          $html = $html.'<h6>Scores</h6><ul>';
          if(count($profile['scores']) == 0){
            $html .= '</ul> You Have no scores';
          }
          else {
            foreach($profile['scores'] as $score) {
              $score_name = $score['metric']['name'];
              $score_id = $score['metric']['id'];
              $score_type = $score['metric']['type'];
              $score_value = $score['value'];
              if($score_type === 'point') {
                $html .= '<img src="/local/playlyfe/image_def.php?metric='.$score_id.'&size=small"></img>';
                $html .= "<li>$score_name $score_value</li>";
              }
              else {
                $html .= "<li>$score_name</br>";
                $html .= '<img src="/local/playlyfe/image_def.php?metric='.$score_id.'&size=small"></img>';
                foreach($score_value as $value){
                  $name = $value['name'];
                  $count = $value['count'];
                  $html .= '<br>';
                  $html .= '     <img src="/local/playlyfe/image_def.php?metric='.$score_id.'&size=medium&item='.$count.'"></img>    ';
                  $html .= '  '.$name.' x '.$count;
                }
              }
            }
            $html .= '</ul>';
          }
          break;
        case 1:
          if(!is_null($this->config->metric) and $this->config->scope) {
            $data = json_decode(get_config('playlyfe', 'course'.$this->config->scope.'_leaderboard'));
            $id = $data[$this->config->metric];
            $leaderboard = $pl->get('/runtime/leaderboards/'.$id, array(
              'player_id' => 'u'.$USER->id,
              'cycle' => 'alltime',
              'scope_id' => 'course'.$this->config->scope,
              //'ranking' => 'relative',
              //'entity_id' => 'u'.$USER->id
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
          else {
            $html = 'Please Configure This Block';
          }
          break;
        case 2:
          $players = $pl->get('/runtime/players', array('player_id' => 'u'.$USER->id));
          $html .= '<ul>';
          foreach($players['data'] as $value){
            $id = $value['id'];
            $alias = $value['alias'];
            $html .= '<li class="list-group-item">';
            $user = $DB->get_record('user', array('id' => explode('u', $id)[1]));
            $html .= $OUTPUT->user_picture($user, array('size'=>100));
            $html .= '<h6>'.$alias.'</h6></li>';
          }
          $html .= '</ul>';
          break;
      }
    // }
    // catch(Exception $e) {
    //   $html =  'Something Went Wrong';
    // }
    $this->content->text = $html;
    // global $COURSE;
    // $url = new moodle_url('/blocks/playlyfe/view.php', array('blockid' => $this->instance->id, 'courseid' => $COURSE->id));
    #$this->content->footer = html_writer::link($url, 'Add Page');
    return $this->content;
  }

  public function specialization() {
    if (!empty($this->config->title)) {
      $this->title = $this->config->title;
    } else {
      //$this->config->title = 'Playlyfe';
    }
    if (empty($this->config->type)) {
      //$this->config->type = 0;
    }
  }

  public function has_config() {
    return true;
  }

  public function applicable_formats() {
    return array(
      'all' => true
      // 'admin' => false,
      // 'site-index' => true,
      // 'course-view' => true,
      // 'mod' => false,
      // 'my' => true
    );
  }

  public function instance_allow_multiple() {
    return true;
  }

  public function cron() {
    mtrace( "Hey, my cron script is running" );
    return true;
  }

  // public function hide_header() {
  //   return true;
  // }
  //
  //
  //
  // public function html_attributes() {
  //     $attributes = parent::html_attributes(); // Get default values
  //     $attributes['class'] .= ' block_'. $this->name(); // Append our class to class attribute
  //     return $attributes;
  // }

}
