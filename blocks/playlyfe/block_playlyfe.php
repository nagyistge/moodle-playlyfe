<?php

require_once('classes/sdk.php');

class block_playlyfe extends block_base {

    public function init() {
      $this->title = get_string('pluginname', 'block_playlyfe');
    }

    public function get_content() {
      if ($this->content !== null) {
        return $this->content;
      }
      $pl = block_playlyfe_sdk::get_pl();
      $this->content =  new stdClass;
      $html = '';
      switch ($this->config->type) {
        case 0:
          $profile = $pl->get('/player', array('player_id' => 'student1'));
          $html = $profile['alias'];
          $html = $html.'<h6>Scores</h6><ul>';
          foreach($profile['scores'] as $score) {
            $score_id = $score['metric']['id'];
            $score_type = $score['metric']['type'];;
            if($score_type == 'point') {
              $score_value = $score['value'];
              $html = $html."<li>$score_id : $score_value</li>";
            }
          }
          $html .= '</ul>';
          break;
        case 1:
          $leaderboard = $pl->get('/leaderboards/game_leaderboard', array('player_id' => 'student1', 'cycle' => 'alltime'));
          $html = '<ul>';
          foreach($leaderboard['data'] as $player) {
            $score = $player['score'];
            $alias = $player['player']['alias'] or 'Null';
            $rank = $player['rank'];
            $html = $html ."<li class='list-group-item'>$rank: $alias: $score</li>";
          }
          $html .= "</ul>";
          break;
        case 2:
          $players = $pl->get('/players', array('player_id' => 'student1'));
          $html = '<ul>';
          foreach($players['data'] as $value){
            $id = $value['id'];
            $html .= "<li class='list-group-item'><h6>$id</h6></li>";
          }
          $html = $html ."</ul>";
          break;
      }
      $this->content->text = $html;
      #$this->content->footer = 'Footer here...';
      global $COURSE;
      $url = new moodle_url('/blocks/playlyfe/view.php', array('blockid' => $this->instance->id, 'courseid' => $COURSE->id));
      $this->content->footer = html_writer::link($url, 'Add Page');
      return $this->content;
  }

  public function specialization() {
    if (!empty($this->config->title)) {
      $this->title = $this->config->title;
    } else {
      $this->config->title = 'Playlyfe';
    }
    if (empty($this->config->type)) {
      $this->config->type = 0;
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
