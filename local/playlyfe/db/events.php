<?php

# moodle 2.5 events vs moodle 2.6 events
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

$handlers = array(
  'user_created' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'user_created_handler',
    'schedule'         => 'instant'
  ),

  'user_deleted' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'user_deleted_handler',
    'schedule'         => 'instant'
  ),

  'course_completed' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'course_completed_handler',
    'schedule'         => 'instant'
  ),

  'activity_completion_changed' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'activity_completion_changed_handler',
    'schedule'         => 'instant'
  ),

  'user_enrolled' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'user_enrolled_handler',
    'schedule'         => 'instant'
  ),

  'user_logout' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'user_logout_handler',
    'schedule'         => 'instant'
  ),

  'quiz_attempt_started' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'pl_quiz_attempt_started_handler',
    'schedule'         => 'instant'
  ),

  'quiz_attempt_submitted' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'pl_quiz_attempt_submitted_handler',
    'schedule'         => 'instant'
  ),

  'forum_discussion_created' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'forum_discussion_created_handler',
    'schedule'         => 'instant'
  ),

  'forum_post_created' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'forum_post_created_handler',
    'schedule'         => 'instant'
  ),

  'forum_viewed' => array(
    'handlerfile'      => '/local/playlyfe/lib.php',
    'handlerfunction'  => 'forum_viewed_handler',
    'schedule'         => 'instant'
  )
);
