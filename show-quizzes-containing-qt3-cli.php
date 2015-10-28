<?php

/*
 * Command line script to show all quizzes
 * which contain any question of type QT3 (turtipskupon) 
 */

// CLI script
define('CLI_SCRIPT', 1);

// Visibility status - 1: Show, 0: Hide
define('CURRENT_VISIBILITY_STATUS', 0);
define('NEW_VISIBILITY_STATUS', 1);

// Run from /admin/cli dir
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/course/lib.php');

// Open report file
$reportfile = fopen('hide-quizzes-containing-qt3-cli_report.txt', 'w');

// Identify the quizzes in question
$sql = "SELECT DISTINCT
            cm.id,
            qz.id AS quizid,
            qz.name AS quizname,
            c.id AS courseid,
            c.fullname AS coursename
          FROM {question} q
          JOIN {quiz_slots} qs ON qs.questionid = q.id
          JOIN {quiz} qz ON qz.id = qs.quizid
          JOIN {course} c ON c.id = qz.course
          JOIN {course_modules} cm ON cm.instance = qz.id
          JOIN {modules} m ON m.id = cm.module
         WHERE q.qtype = ?
           AND m.name = ?
           AND cm.visible = ?
      ORDER BY cm.id";
$params = array('turtipskupon', 'quiz', CURRENT_VISIBILITY_STATUS);
$records = $DB->get_records_sql($sql, $params);

foreach ($records as $record) {
    $cm = get_coursemodule_from_id('', $record->id, 0, true, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $modcontext = context_module::instance($cm->id);
    set_coursemodule_visible($cm->id, NEW_VISIBILITY_STATUS);
    \core\event\course_module_updated::create_from_cm($cm, $modcontext)->trigger();
    xlog("Quiz ID {$record->quizid}: '{$record->quizname}' in course ID {$record->courseid}: '{$record->coursename}' set as visible as it contains question of type QT3 (turtipskupon)");
}

function xlog($message) {
    global $reportfile;

    // Output to screen
    mtrace($message);

    // Write to report file
    fwrite($reportfile, $message . "\n");
}

// Close the report file
fclose($reportfile);
