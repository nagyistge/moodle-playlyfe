<?php

# moodle 2.5 events vs moodle 2.8 events
#
# course_completed -> core\event\course_completed
# groups_member_added -> core\event\group_member_added
# groups_member_removed -> core\event\group_member_removed
# role_assigned -> core\event\role_assigned
# role_unassigned -> core\event\role_unassigned
# user_created -> core\event\user_created
# user_deleted -> core\event\user_deleted
# user_enrolled -> core\event\user_enrolment_created
# user_unenrolled -> core\event\user_enrolment_deleted
# user_logout -> core\event\user_loggedout
# user_updated -> core\event\user_updated
# assessable_submitted -> mod_assign\event\assessable_submitted
# quiz_attempt_submitted -> mod_quiz\event\attempt_submitted
# workshop_viewed -> mod_workshop\event\course_module_viewed

$event_names = array (
  'course_completed',
  'groups_member_added',
  'groups_member_removed',
  'role_assigned',
  'role_unassigned',
  'user_enrolled',
  'user_logout',
  'user_updated',
  'assessable_submitted',
  'quiz_attempt_submitted'
);

$event_handlers = array();
foreach($event_names as $event_name) {
  $event_handlers[$event_name] = array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => $event_name.'_handler',
    'schedule'         => 'instant'
  );
}

$handlers = $event_handlers;
