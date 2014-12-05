<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('course');
require_login();
$PAGE->set_url('/local/playlyfe/feedback.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('course-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
global $DB, $USER;
$pl = local_playlyfe_sdk::get_pl();
$html = '';

$id = required_param('id', PARAM_TEXT);

//$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$reward = new stdClass();
$reward->course = 4;
$reward->metric = 'point';
$reward->verb = 'set';
$reward->value = '20';
print_object($reward);
//$DB->insert_record_raw('local_playlyfe', $reward);

$leaderboard = $pl->get('/runtime/leaderboards/erer', array('player_id' => 'u'.$USER->id, 'cycle' => 'alltime', 'scope_id' => 'course1'));
$html .= '<h3> Leaderboards </h3>';
$html .= '<ul>';
foreach($leaderboard['data'] as $player) {
  $score = $player['score'];
  $id = $player['player']['id'];
  $alias = $player['player']['alias'] or 'Null';
  $rank = $player['rank'];
  $list = explode('u', $id);
  $user = $DB->get_record('user', array('id' => $list[1]));
  $html .= $OUTPUT->user_picture($user, array('size'=>100));
  $html .= "<li class='list-group-item'>$rank: $alias: $score</li>";
}
$html .= "</ul>";
echo $OUTPUT->header();
echo "<h1> You Completed Course - $course->fullname </h1>";
echo $html;
echo $OUTPUT->footer();
