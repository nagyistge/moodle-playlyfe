<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/notification.php');
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->navigation->clear_cache();
$html = '<h1> Your Notifications </h1><hr></hr>';
$count = 1;
$ids = array();
if($_GET) {
  $page = $_GET['page'];
}
else {
  $page = 0;
}
$date = date('Y-m-d',  time() - ($page+1)*(24 * 60 * 60));
print_object($date);
//, 'start' => '2015-01-01'
$notifications = $pl->get('/runtime/notifications', array('player_id' => 'u'.$USER->id));
if(!is_null($notifications)) {
  $notifications['data'] = array_reverse($notifications['data']);
  foreach($notifications['data'] as $notification) {
    if ($notification['seen'] == false) {
      array_push($ids, $notification['id']);
    }
    if ($notification['event'] == 'custom_rule') {
      foreach($notification['changes'] as $change) {
        $title = $notification['rule']['name'];
        $date = new DateTime($notification['timestamp']);
        $html .= '<p>'.display_change($change, $title).' on '.$date->format('Y-m-d H:i:s').'</p>';
      }
    }
  }
}
else {
  $html .= '<h4>You have no new Notifications</h4>';
}

// if($notifications and $page >= 0 and $page < intval($notifications['total']/10)) {
//   $url = new moodle_url('/local/playlyfe/notification.php', array('page' => $page+1));
//   $html .= '<div class="leaderboard-button">'.html_writer::link($url, 'Older Entries').'</div class="leadeboard-button">';
// }

if(count($ids) > 0) {
  try {
    $pl->post('/runtime/notifications', array('player_id' => 'u'.$USER->id), array('ids' => $ids));
  }
  catch(Exception $e) {
    print_object($e);
  }
}
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
