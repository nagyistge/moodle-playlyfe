<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/notification.php');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->navigation->clear_cache();
$html = '';
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

$html .= '
<div id="pl-notifications" class="profile pl-page">
  <h1 class="page-title">Your Notifications</h1>
  <div class="page-section">
    <div class="section-content">
';

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
        $html .= display_change($change, $title, $date);
      }
    }
  }
}
else {
  $html .= '<div class="placeholder-content empty-content">You have no new notifications.</div>';
}

$html .= '
    </div>
  </div>
</div>'; // </#pl-notifications

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
