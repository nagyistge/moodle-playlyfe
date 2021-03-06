<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/eventslib.php');
require_once('classes/sdk.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
require_login();
$PAGE->set_url('/local/playlyfe/profile.php');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_cacheable(false);
$PAGE->navigation->clear_cache();
$html = '';
$profile = $pl->get('/runtime/player', array('player_id' => 'u'.$USER->id, 'detailed' => 'true'));
global $CFG, $USER;

/**
 * Used to compare two activities/resources based on order on course page
 *
 * @param array $a array of event information
 * @param array $b array of event information
 * @return <0, 0 or >0 depending on order of activities/resources on course page
 */
function compare_events($a, $b) {
    if ($a['section'] != $b['section']) {
        return $a['section'] - $b['section'];
    } else {
        return $a['position'] - $b['position'];
    }
}

/**
 * Checks if a variable has a value and returns a default value if it doesn't
 *
 * @param mixed $var The variable to check
 * @param mixed $def Default value if $var is not set
 * @return string
 */
function default_value(&$var, $def = null) {
    return isset($var)?$var:$def;
}


/**
 * Provides information about monitorable modules
 *
 * @return array
 */
function monitorable_modules() {
    global $DB;

    return array(
        'assign' => array(
            'defaultTime' => 'duedate',
            'actions' => array(
                'submitted'    => "SELECT id
                                     FROM {assign_submission}
                                    WHERE assignment = :eventid
                                      AND userid = :userid
                                      AND status = 'submitted'",
                'marked'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assign'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND (g.finalgrade IS NOT NULL OR g.excluded <> 0)",
                'passed'       => "SELECT g.finalgrade, i.gradepass
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assign'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND (g.finalgrade IS NOT NULL OR g.excluded <> 0)",
            ),
            'defaultAction' => 'submitted'
        ),
        'assignment' => array(
            'defaultTime' => 'timedue',
            'actions' => array(
                'submitted'    => "SELECT id
                                     FROM {assignment_submissions}
                                    WHERE assignment = :eventid
                                      AND userid = :userid
                                      AND (
                                          numfiles >= 1
                                          OR {$DB->sql_compare_text('data2')} <> ''
                                      )",
                'marked'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assignment'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND (g.finalgrade IS NOT NULL OR g.excluded <> 0)",
                'passed'       => "SELECT g.finalgrade, i.gradepass
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assignment'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND (g.finalgrade IS NOT NULL OR g.excluded <> 0)",
            ),
            'defaultAction' => 'submitted'
        ),
        'bigbluebuttonbn' => array(
            'defaultTime' => 'timedue',
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'bigbluebuttonbn'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_bigbluebuttonbn'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'recordingsbn' => array(
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'recordingsbn'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_recordingsbn'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'book' => array(
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'book'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_book'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'certificate' => array(
            'actions' => array(
                'awarded'      => "SELECT id
                                     FROM {certificate_issues}
                                    WHERE certificateid = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'awarded'
        ),
        'chat' => array(
            'actions' => array(
                'posted_to'    => "SELECT id
                                     FROM {chat_messages}
                                    WHERE chatid = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'posted_to'
        ),
        'choice' => array(
            'defaultTime' => 'timeclose',
            'actions' => array(
                'answered'     => "SELECT id
                                     FROM {choice_answers}
                                    WHERE choiceid = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'answered'
        ),
        'data' => array(
            'defaultTime' => 'timeviewto',
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'data'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_data'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'feedback' => array(
            'defaultTime' => 'timeclose',
            'actions' => array(
                'responded_to' => "SELECT id
                                     FROM {feedback_completed}
                                    WHERE feedback = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'responded_to'
        ),
        'resource' => array(  // AKA file.
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'resource'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_resource'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'flashcardtrainer' => array(
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'flashcardtrainer'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_flashcardtrainer'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'folder' => array(
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'folder'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_folder'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'forum' => array(
            'defaultTime' => 'assesstimefinish',
            'actions' => array(
                'posted_to'    => "SELECT id
                                     FROM {forum_posts}
                                    WHERE userid = :userid AND discussion IN (
                                          SELECT id
                                            FROM {forum_discussions}
                                           WHERE forum = :eventid
                                    )"
            ),
            'defaultAction' => 'posted_to'
        ),
        'glossary' => array(
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'glossary'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_glossary'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'hotpot' => array(
            'defaultTime' => 'timeclose',
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {hotpot_attempts}
                                    WHERE hotpotid = :eventid
                                      AND userid = :userid",
                'finished'     => "SELECT id
                                     FROM {hotpot_attempts}
                                    WHERE hotpotid = :eventid
                                      AND userid = :userid
                                      AND timefinish <> 0",
            ),
            'defaultAction' => 'finished'
        ),
        'hsuforum' => array(
            'defaultTime' => 'assesstimefinish',
            'actions' => array(
                'posted_to'    => "SELECT id
                                     FROM {hsuforum_posts}
                                    WHERE userid = :userid AND discussion IN (
                                          SELECT id
                                            FROM {hsuforum_discussions}
                                           WHERE forum = :eventid
                                    )"
            ),
            'defaultAction' => 'posted_to'
        ),
        'imscp' => array(
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'imscp'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_imscp'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'journal' => array(
            'actions' => array(
                'posted_to'    => "SELECT id
                                     FROM {journal_entries}
                                    WHERE journal = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'posted_to'
        ),
        'lesson' => array(
            'defaultTime' => 'deadline',
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {lesson_attempts}
                                    WHERE lessonid = :eventid
                                      AND userid = :userid
                                UNION ALL
                                   SELECT id
                                     FROM {lesson_branch}
                                    WHERE lessonid = :eventid1
                                      AND userid = :userid1",
                'graded'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'lesson'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND (g.finalgrade IS NOT NULL OR g.excluded <> 0)",
            ),
            'defaultAction' => 'attempted'
        ),
        'page' => array(
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'page'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_page'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'questionnaire' => array(
            'defaultTime' => 'closedate',
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {questionnaire_attempts}
                                    WHERE qid = :eventid
                                      AND userid = :userid",
                'finished'     => "SELECT id
                                     FROM {questionnaire_response}
                                    WHERE complete = 'y'
                                      AND username = :userid
                                      AND survey_id = :eventid",
            ),
            'defaultAction' => 'finished'
        ),
        'quiz' => array(
            'defaultTime' => 'timeclose',
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {quiz_attempts}
                                    WHERE quiz = :eventid
                                      AND userid = :userid",
                'finished'     => "SELECT id
                                     FROM {quiz_attempts}
                                    WHERE quiz = :eventid
                                      AND userid = :userid
                                      AND timefinish <> 0",
                'graded'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'quiz'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND (g.finalgrade IS NOT NULL OR g.excluded <> 0)",
                'passed'       => "SELECT g.finalgrade, i.gradepass
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'quiz'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND (g.finalgrade IS NOT NULL OR g.excluded <> 0)",
            ),
            'defaultAction' => 'finished'
        ),
        'scorm' => array(
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {scorm_scoes_track}
                                    WHERE scormid = :eventid
                                      AND userid = :userid",
                'completed'    => "SELECT id
                                     FROM {scorm_scoes_track}
                                    WHERE scormid = :eventid
                                      AND userid = :userid
                                      AND element = 'cmi.core.lesson_status'
                                      AND {$DB->sql_compare_text('value')} = 'completed'",
                'passedscorm'  => "SELECT id
                                     FROM {scorm_scoes_track}
                                    WHERE scormid = :eventid
                                      AND userid = :userid
                                      AND element = 'cmi.core.lesson_status'
                                      AND {$DB->sql_compare_text('value')} = 'passed'"
            ),
            'defaultAction' => 'attempted'
        ),
        'turnitintool' => array(
            'defaultTime' => 'defaultdtdue',
            'actions' => array(
                'submitted'    => "SELECT id
                                     FROM {turnitintool_submissions}
                                    WHERE turnitintoolid = :eventid
                                      AND userid = :userid
                                      AND submission_score IS NOT NULL"
            ),
            'defaultAction' => 'submitted'
        ),
        'url' => array(
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'url'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_url'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'wiki' => array(
            'actions' => array(
                'viewed' => array (
                    'logstore_legacy'     => "SELECT id
                                                FROM {log}
                                               WHERE course = :courseid
                                                 AND module = 'wiki'
                                                 AND action = 'view'
                                                 AND cmid = :cmid
                                                 AND userid = :userid",
                    'sql_internal_reader' => "SELECT id
                                                FROM {log}
                                               WHERE courseid = :courseid
                                                 AND component = 'mod_wiki'
                                                 AND action = 'viewed'
                                                 AND objectid = :eventid
                                                 AND userid = :userid",
                ),
            ),
            'defaultAction' => 'viewed'
        ),
        'workshop' => array(
            'defaultTime' => 'assessmentend',
            'actions' => array(
                'submitted'    => "SELECT id
                                     FROM {workshop_submissions}
                                    WHERE workshopid = :eventid
                                      AND authorid = :userid",
                'assessed'     => "SELECT s.id
                                     FROM {workshop_assessments} a, {workshop_submissions} s
                                    WHERE s.workshopid = :eventid
                                      AND s.id = a.submissionid
                                      AND a.reviewerid = :userid
                                      AND a.grade IS NOT NULL",
                'graded'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'workshop'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND (g.finalgrade IS NOT NULL OR g.excluded <> 0)",
            ),
            'defaultAction' => 'submitted'
        ),
    );
}

/**
 * Filters the modules list to those installed in Moodle instance and used in current course
 *
 * @return array
 */
function modules_in_use($course) {
    global $DB;

    $dbmanager = $DB->get_manager(); // Used to check if tables exist.
    $modules = monitorable_modules();
    $modulesinuse = array();

    foreach ($modules as $module => $details) {
        if (
            $dbmanager->table_exists($module) &&
            $DB->record_exists($module, array('course' => $course))
        ) {
            $modulesinuse[$module] = $details;
        }
    }
    return $modulesinuse;
}

/**
 * Gets the course context, allowing for old and new Moodle instances.
 *
 * @param int $courseid The course ID
 * @return stdClass The context object
 */
function course_context($courseid) {
    if (class_exists('context_course')) {
        return context_course::instance($courseid);
    } else {
        return get_context_instance(CONTEXT_COURSE, $courseid);
    }
}

/**
 * Checked if a user has attempted/viewed/etc. an activity/resource
 *
 * @param array    $modules  The modules used in the course
 * @param stdClass $config   The blocks configuration settings
 * @param array    $events   The possible events that can occur for modules
 * @param int      $userid   The user's id
 * @param int      $instance The instance of the block
 * @return array   an describing the user's attempts based on module+instance identifiers
 */
function progress_attempts($modules, $events, $userid, $course) {
    global $DB;
    $attempts = array();
    $modernlogging = false;
    $cachingused = false;

    // Get readers for 2.7 onwards.
    if (function_exists('get_log_manager')) {
        $modernlogging = true;
        $logmanager = get_log_manager();
        $readers = $logmanager->get_readers();
        $numreaders = count($readers);
    }

    foreach ($events as $event) {
        $module = $modules[$event['type']];
        $uniqueid = $event['type'].$event['id'];
        $parameters = array('courseid' => $course, 'courseid1' => $course,
                            'userid' => $userid, 'userid1' => $userid,
                            'eventid' => $event['id'], 'eventid1' => $event['id'],
                            'cmid' => $event['cm']->id, 'cmid1' => $event['cm']->id,
                      );
        // print_object($module['actions']);
        // Check for passing grades as unattempted, passed or failed
        if (isset($module['actions']['passed'])) {
            $query = $module['actions']['passed'];
            $graderesult = $DB->get_record_sql($query, $parameters);
            if ($graderesult === false || $graderesult->finalgrade === null) {
                $attempts[$uniqueid] = false;
            } else {
                $attempts[$uniqueid] = $graderesult->finalgrade >= $graderesult->gradepass ? true : 'failed';
            }
        }

        // Checked view actions in the log table/store/cache.
        // else if (isset($config->{'action_'.$uniqueid}) && $config->{'action_'.$uniqueid} == 'viewed') {
        //     $attempts[$uniqueid] = false;

        //     // Check if the value is cached.
        //     if ($cachingused && array_key_exists($uniqueid, $cachedlogviews) && $cachedlogviews[$uniqueid]) {
        //         $attempts[$uniqueid] = true;
        //     }

        //     // Check in the logs.
        //     else {
        //         if ($modernlogging) {
        //             foreach ($readers as $logstore => $reader) {
        //                 if ($reader instanceof logstore_legacy\log\store) {
        //                     $query = $module['actions']['viewed']['logstore_legacy'];
        //                 }
        //                 else if ($reader instanceof \core\log\sql_internal_reader) {
        //                     $logtable = '{'.$reader->get_internal_log_table_name().'}';
        //                     $query = preg_replace('/\{log\}/', $logtable, $module['actions']['viewed']['sql_internal_reader']);
        //                 }
        //                 $attempts[$uniqueid] = $DB->record_exists_sql($query, $parameters) ? true : false;
        //                 if ($attempts[$uniqueid]) {
        //                     $cachedlogviews[$uniqueid] = true;
        //                     $cachedlogsupdated = true;
        //                     break;
        //                 }
        //             }
        //         } else {
        //             $query = $module['actions']['viewed']['logstore_legacy'];
        //             $attempts[$uniqueid] = $DB->record_exists_sql($query, $parameters) ? true : false;
        //             if ($cachingused && $attempts[$uniqueid]) {
        //                 $cachedlogviews[$uniqueid] = true;
        //                 $cachedlogsupdated = true;
        //             }
        //         }
        //     }
        // } else {

        //     // If activity completion is used, check completions table.
        //     if (isset($config->{'action_'.$uniqueid}) && $config->{'action_'.$uniqueid} == 'activity_completion') {
        //         $query = 'SELECT id
        //                     FROM {course_modules_completion}
        //                    WHERE userid = :userid
        //                      AND coursemoduleid = :cmid
        //                      AND completionstate >= 1';
        //     }

        //     // Determine the set action and develop a query.
        //     else {
        //         $action = isset($config->{'action_'.$uniqueid})?
        //                   $config->{'action_'.$uniqueid}:
        //                   $module['defaultAction'];
        //         $query = $module['actions'][$action];
        //     }

        //      // Check if the user has attempted the module.
        //     $attempts[$uniqueid] = $DB->record_exists_sql($query, $parameters) ? true : false;
        // }
    }

    // Update log cache if new values were added.
    if ($cachingused && $cachedlogsupdated) {
        $cachedlogs->set($userid, $cachedlogviews);
    }

    return $attempts;
}

/**
 * Gathers the course section and activity/resource information for ordering
 *
 * @return array section information
 */
function course_sections($course) {
    global $DB;

    $sections = $DB->get_records('course_sections', array('course' => $course), 'section', 'id,section,name,sequence');
    foreach ($sections as $key => $section) {
        if ($section->sequence != '') {
            $sections[$key]->sequence = explode(',', $section->sequence);
        }
        else {
            $sections[$key]->sequence = null;
        }
    }

    return $sections;
}

function event_information($modules, $course, $userid = 0) {
    global $DB, $USER;
    $events = array();
    $numevents = 0;
    $numeventsconfigured = 0;
    if ($userid === 0) {
        $userid = $USER->id;
    }

    $sections = course_sections($course);
    // Check each known module (described in lib.php).
    foreach ($modules as $module => $details) {
        $fields = 'id, name';
        if (array_key_exists('defaultTime', $details)) {
            $fields .= ', '.$details['defaultTime'].' as due';
        }

        // Check if this type of module is used in the course, gather instance info.
        $records = $DB->get_records($module, array('course' => $course), '', $fields);
        foreach ($records as $record) {

            // Is the module being monitored?
            // if (isset($config->{'monitor_'.$module.$record->id})) {
                $numeventsconfigured++;
            // }
            // if (progress_default_value($config->{'monitor_'.$module.$record->id}, 0) == 1) {
                $numevents++;
                // // Check the time the module is due.
                // if (
                //     isset($details['defaultTime']) &&f
                //     $record->due != 0 &&
                //     progress_default_value($config->{'locked_'.$module.$record->id}, 0)
                // ) {
                //     $expected = progress_default_value($record->due);
                // } else {
                //     $expected = $config->{'date_time_'.$module.$record->id};
                // }
                $expected = 0;

                // Gather together module information.
                $coursemodule = get_coursemodule($module, $record->id, $course);
                $events[] = array(
                    'expected' => $expected,
                    'type'     => $module,
                    'id'       => $record->id,
                    'name'     => format_string($record->name),
                    'cm'       => $coursemodule,
                    'section'  => $sections[$coursemodule->section]->section,
                    'position' => array_search($coursemodule->id, $sections[$coursemodule->section]->sequence),
                );
            // }
        }
    }

    if ($numeventsconfigured == 0) {
        return 0;
    }
    if ($numevents == 0) {
        return null;
    }

    usort($events, 'compare_events');
    return $events;
}

/**
 * Gets the course module in a backwards compatible way.
 *
 * @param int $module   the type of module (eg, assign, quiz...)
 * @param int $recordid the instance ID (from its table)
 * @param int $courseid the course ID
 * @return stdClass The course module object
 */
function get_coursemodule($module, $recordid, $courseid) {
    global $CFG;

    if ($CFG->version >= 2012120300) {
        return get_fast_modinfo($courseid)->instances[$module][$recordid];
    }
    else {
        return get_coursemodule_from_instance($module, $recordid, $courseid);
    }
}

/**
 * Calculates an overall percentage of progress
 *
 * @param array $events   The possible events that can occur for modules
 * @param array $attempts The user's attempts on course activities
 * @return int  Progress value as a percentage
 */
function progress_percentage($events, $attempts) {
    $attemptcount = 0;
    foreach ($events as $event) {
        if(array_key_exists($event['type'].$event['id'],  $attempts)) {
          if ($attempts[$event['type'].$event['id']] == 1) {
            $attemptcount++;
          }
        } else {
        }
    }
    $progressvalue = $attemptcount == 0 ? 0 : $attemptcount / count($events);
    return (int)round($progressvalue * 100);
}

$overall_progress = 0;
$index = 1;
$courses = enrol_get_my_courses();
foreach ($courses as $courseid => $course) {
  $modules = modules_in_use($course->id);
  if ($course->visible && !empty($modules)) {
    $events = event_information($modules, $course->id);
    $attempts = progress_attempts($modules, $events, $USER->id, $course->id);
    $overall_progress += progress_percentage($events, $attempts);
    // $html .= 'course'.$course->id.'<br>';
    // $html .= 'events'.count($events).'<br>';
    // $html .= 'attempts'.count($attempts).'<br>';
    //$overall_progress += count($attempts)/count($events);
    //$html .= $course->shortname;
    $index++;
  }
}
$progress = round($overall_progress/$index, 2);

$html .= '
<div id="pl-profile" class="profile pl-page">
  <h1 class="page-title">Your Profile</h1>
  <div class="page-section">
    <div class="section-content">
      <div class="player-card media">
        <div class="player-avatar avatar large image">'.$OUTPUT->user_picture($USER, array('size'=>140)).'</div>
        <div class="player-details content">
          <h2 class="player-alias">'.$profile['alias'].'</h2>
          <div class="progress-bar" id="profile-progress">
            <div class="progress-meter" title="You have completed '.round($progress, 1).'% of your total coursework">
              <div class="progress-value" style="width: '.$progress.'%"></div>
              <div class="progress-label">'.$progress.'% Completed</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>';

$html .= '
  <div class="page-section grid-12 full-width clearfix">
    <div class="profile-achievements col-6">
      <h2 class="section-title">Your Achievements</h2>';
    $item_count = 0;

    if(count($profile['scores']) != 0) {
      foreach($profile['scores'] as $score) {
        if($score['metric']['type'] == 'set') {
          $score_id = $score['metric']['id'];
          $html .= '
        <ul class="list-unstyled achievement-list clearfix">';
          foreach($score['value'] as $value) {
            $item_count += 1;
            $html .= '
          <li class="achievement-item '.($value['count'] == 0 ? 'locked' : 'achieved').'">
            <div class="achievement-icon avatar image"><img src="image_def.php?metric='.$score_id.'&item='.$value['name'].'&size=medium"></img></div>
            <div class="achievement-count" title="'.($value['count'] == 0 ? 'This achievement is locked' : '').'">'.($value['count'] == 0 ? '<i class="icon-lock no-space"></i>' : $value['count']).'</div>
            <div class="achievement-name small content">'.$value['name'].'</div>
          </li>';
          }
          $html .= '
        </ul>';
        }
      }
    }
    else if(count($profile['scores']) == 0 || $item_count == 0) {
      $html .= '
      <div class="placeholder-content empty">You\'ve not earned any achievements yet.</div>';
    }
$html .= '
    </div>

    <div class="profile-scores col-6">
      <h2 class="section-title">Your Scores</h2>
      <ul class="list-unstyled profile-score-list">';
    $score_count = 0;
    if(count($profile['scores']) != 0) {
      foreach($profile['scores'] as $score) {
        $score_type = $score['metric']['type'];
        if($score_type != 'set') {
          $score_id = $score['metric']['id'];
          $score_name = $score['metric']['name'];
          $html .= '
        <li class="score-list-item score-'.$score_type.'">
          <h5 class="score-name ellipsis">'.$score_name.'</h5>
          <div class="score-icon text-center"><img src="image_def.php?metric='.$score_id.'&size=large"></img></div>
          <div class="score-value large">'.$score['value'].'</div>
        </li>';
        }
      }
    }
    else if(count($profile['scores']) == 0 || $score_count == 0) {
      $html .= '
        <li class="placeholder-content empty">You don\'t have any scores yet.</li>';
    }
    $html .= '
      </ul>
    </div>
  </div>';


// $html .= '
//     <div class="score-widget page-hud">
//       <h3 class="hud-title">Your Scores</h3>';
//   if(count($profile['scores']) == 0) {
//     $html .= '
//       <p class="hud-section-item no-score">You don\'t have any scores in this app yet.</p>';
//   }
//   else {
//     $html .= '
//       <ul class="list-unstyled profile-score-list">';
//     foreach($profile['scores'] as $score) {
//       $score_id = $score['metric']['id'];
//       $score_name = $score['metric']['name'];
//       $score_type = $score['metric']['type'];
//       $html .= '
//         <li class="score-list-item score-'.$score_type.'">
//           <h5 class="score-name ellipsis">'.$score_name.'</h5>
//           <div class="score-icon text-center"><img src="image_def.php?metric='.$score_id.'&size=large"></img></div>';
//           if($score_type == 'point' || $score_type == 'compound') {
//             $html .= '
//             <div class="score-value large">'.$score['value'].'</div>';
//           }
//           else if($score_type == 'set') {
//             foreach($score['value'] as $value) {
//               $html .= '
//             <div class="score-item media '.($value['count'] == 0 ? 'locked' : 'achieved').'">
//               <div class="score-item-icon avatar image"><img src="image_def.php?metric='.$score_id.'&item='.$value['name'].'&size=small"></img></div>
//               <div class="score-value small content">'.$value['name'].($value['count'] == 0 ? ' [Locked]' : '&times;'.$value['count']).'</div>
//             </div>';
//             }
//           }
//       $html .= '
//         </li>';
//     }
//     $html .= '
//       </ul>';
//   }
$html .= '
</div>'; // </#pl-profile
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
