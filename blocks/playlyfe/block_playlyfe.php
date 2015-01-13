<?php
class block_playlyfe extends block_base {

  public function init() {
    $this->title = get_string('pluginname', 'block_playlyfe');
  }

  public function get_content() {
    global $USER, $OUTPUT, $DB, $PAGE;
    if ($this->content !== null) {
      return $this->content;
    }
    $this->content = new stdClass;
    $html = '';
    $pl = get_pl();
    try {
      $profile = $pl->get('/runtime/player', array('player_id' => 'u'.$USER->id));
      $html .= '<h6>Username</h6>';
      $html .= $profile['alias'];
      $html .= '<h6>Scores</h6>';
      if(count($profile['scores']) == 0){
        $html .= 'You Have no scores';
      }
      else {
        $html .= '<ul class="list-unstyled profile-score-list">';
        $leaderboards = array();
        if($PAGE->course) {
          $leaderboards = get_leaderboards('course'.$PAGE->course->id.'_leaderboard');
        }
        foreach($profile['scores'] as $score) {
          $score_name = $score['metric']['name'];
          $score_id = $score['metric']['id'];
          $score_type = $score['metric']['type'];
          $score_value = $score['value'];
          $html .= '<li class="score-list-item score-point">';
          $html .= '<h5 class="score-name ellipsis ng-binding">'.$score_name.'</h5>';
          $html .= '<div class="score-icon text-center"><img src="/local/playlyfe/image_def.php?metric='.$score_id.'&size=medium"></img></div>';
          if($score_type === 'point') {
            $leaderboard = null;
            if(in_array($score_id, $leaderboards)) {
              try {
                $leaderboard = $pl->get('/runtime/leaderboards/'.$score_id, array(
                  'player_id' => 'u'.$USER->id,
                  'cycle' => 'alltime',
                  'scope_id' => 'course'.$PAGE->course->id,
                  'ranking' => 'relative',
                  'entity_id' => 'u'.$USER->id,
                  'radius' => 0
                ));
                // mtrace(json_encode(($leaderboard)));
              }
              catch(Exception $e) {
                if($e->name == 'player_not_found') {
                }
                else {
                  //mtrace(json_encode(($e)));
                }
              }
            }
            $html .= '<div class="score-value large">'.$score_value.'</div>';
            if(!is_null($leaderboard)) {
              $url = new moodle_url('/local/playlyfe/leaderboard.php', array(
                'course' => $PAGE->course->id,
                'metric' => $score_id,
                'page'=> 0,
                'find_me' => true
              ));
              $html .= '<div class="score-value small">'.html_writer::link($url, 'Rank'.$leaderboard['data'][0]['rank']).'</div>';
            }
            $html .= '</li>';
          }
          else {
            foreach($score['value'] as $value) {
              $html .= '<div class="score-icon text-center"><img src="image_def.php?metric='.$score_id.'&item='.$value['name'].'"></img></div>';
              $html .= '<div class="score-value small">'.$value['name'].'</div>';
              if($value['count'] > 0) {
                $html .= 'x'.$value['count'];
              }
            }
            $html .= '</li>';
          }
        }
      }
    }
    catch (Exception $e) {
    }
    $this->content->text = $html;
    $url = new moodle_url('/local/playlyfe/profile.php');
    $this->content->footer = html_writer::link($url, 'View Profile');
    return $this->content;
  }

  public function specialization() {
    if (!empty($this->config->title)) {
      $this->title = $this->config->title;
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
}
