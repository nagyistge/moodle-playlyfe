<?php
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/publish.php');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->navigation->clear_cache();
if (!has_capability('moodle/site:config', context_system::instance())) {
  print_error('accessdenied', 'admin');
}
$PAGE->settingsnav->get('root')->get('playlyfe')->get('publish')->make_active();
$html = '';
$simulated = false;

if (array_key_exists('submit', $_POST)) {
  try {
    $pl->post('/design/versions/latest/simulate');
    $simulated = true;
  }
  catch(Exception $e) {
    print_object($e);
  }
}

$issues = $pl->get('/design/issues', array('type' => 'metric'));
$unresolved_issues = array();
foreach ($issues as $value) {
  if (!$value['is_resolved']) {
    array_push($unresolved_issues, $value);
  }
}

$html .= '
<div id="pl-publish" class="pl-page">';

if(count($unresolved_issues) > 0) {
  $html .= '
    <h1 class="page-title">Issues in Design!</h1>
    <div class="page-section">
      <div class="section-content">
        <div class="quote alert media no-margin">
          <div class="image"><i class="icon-warning icon-4x hl-alert no-space"></i></div>
          <div class="content">
            <p>Your game cannot be published as there are some issues in
            the design of your game. Fix those issues before
            publishing the game</p>';

  $table = new html_table();
  $table->head = array('Reason', 'Metric', 'Apply Fix');
  $table->colclasses = array('leftalign', 'leftalign', 'centeralign');
  $table->data = array();
  $table->attributes['class'] = 'pl-table admin-table full-width';
  $table->id = 'manage_sets';
  foreach ($issues as $unresolved_issues) {
    $fix = '<a href="fix.php?id='.$value['id'].'">Fix</a>';
    $table->data[] = new html_table_row(array($value['code'], $value['vars']['metric_design']['name'], $fix));
  }
  $html .= html_writer::table($table);
  $html .= '
          </div>
        </div>
      </div>
    </div>';
}
else {
  if($simulated) {
    $html .= '
    <h1 class="page-title hl-prime">All Clear!</h1>
    <div class="page-section">
      <div class="section-content">
        <div class="quote prime">
          <p>Your game has been successfully published</p>
        </div>
      </div>
    </div>';
  }
  else {
    $html .= '
    <h1 class="page-title hl-prime">Publishing Changes</h1>
    <div class="page-section">
      <div class="section-content">
        <div class="quote info">
          <p>You are about to publish all changes to your courses. Do you want
          to go ahead with it?</p>
          <form action="publish.php" method="post">
            <button class="button" id="submit" type="submit" name="submit">Publish Game</button>
          </form>
        </div>
      </div>
    </div>';
  }
}

$html .= '
</div>';

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
