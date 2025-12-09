<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class get_quiz extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz instance ID'),
        ]);
    }

    public static function execute(int $quizid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
        ]);

        
        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);

        
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, 0, false, MUST_EXIST);

        
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('local/activity_utils:viewquiz', $context);
        require_capability('mod/quiz:view', $context);

        
        $course = $DB->get_record('course', ['id' => $cm->course], 'id, fullname, shortname', MUST_EXIST);

        
        $sections = $DB->get_records('quiz_sections', ['quizid' => $quiz->id], 'firstslot ASC');
        $sectionsarray = [];
        foreach ($sections as $section) {
            $sectionsarray[] = [
                'id' => (int)$section->id,
                'firstslot' => (int)$section->firstslot,
                'heading' => $section->heading ?? '',
                'shufflequestions' => (int)$section->shufflequestions,
            ];
        }

        
        
        $sql = "SELECT qs.id AS slotid, qs.slot, qs.page, qs.maxmark, qs.requireprevious, qs.displaynumber,
                       qr.questionbankentryid, qr.version AS refversion,
                       qbe.idnumber AS questionidnumber,
                       qv.questionid, qv.version, qv.status,
                       q.name AS questionname, q.qtype, q.questiontext, q.questiontextformat,
                       q.defaultmark, q.generalfeedback, q.generalfeedbackformat
                  FROM {quiz_slots} qs
                  JOIN {question_references} qr ON qr.component = 'mod_quiz'
                       AND qr.questionarea = 'slot'
                       AND qr.itemid = qs.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qs.quizid = ?
                   AND qv.version = (
                       SELECT MAX(qv2.version)
                         FROM {question_versions} qv2
                        WHERE qv2.questionbankentryid = qbe.id
                          AND qv2.status = 'ready'
                   )
              ORDER BY qs.slot ASC";

        $slots = $DB->get_records_sql($sql, [$quiz->id]);

        $questionsarray = [];
        foreach ($slots as $slot) {
            $questionsarray[] = [
                'slotid' => (int)$slot->slotid,
                'slot' => (int)$slot->slot,
                'page' => (int)$slot->page,
                'maxmark' => (float)$slot->maxmark,
                'requireprevious' => (int)$slot->requireprevious,
                'displaynumber' => $slot->displaynumber ?? '',
                'questionbankentryid' => (int)$slot->questionbankentryid,
                'questionid' => (int)$slot->questionid,
                'questionidnumber' => $slot->questionidnumber ?? '',
                'questionname' => $slot->questionname,
                'qtype' => $slot->qtype,
                'questiontext' => $slot->questiontext ?? '',
                'questiontextformat' => (int)$slot->questiontextformat,
                'defaultmark' => (float)$slot->defaultmark,
                'generalfeedback' => $slot->generalfeedback ?? '',
                'generalfeedbackformat' => (int)$slot->generalfeedbackformat,
                'version' => (int)$slot->version,
                'status' => $slot->status,
            ];
        }

        
        $attemptcount = $DB->count_records('quiz_attempts', ['quiz' => $quiz->id, 'preview' => 0]);

        return [
            'id' => (int)$quiz->id,
            'coursemoduleid' => (int)$cm->id,
            'courseid' => (int)$cm->course,
            'coursename' => $course->fullname,
            'name' => $quiz->name,
            'intro' => $quiz->intro ?? '',
            'introformat' => (int)$quiz->introformat,
            'timeopen' => (int)$quiz->timeopen,
            'timeclose' => (int)$quiz->timeclose,
            'timelimit' => (int)$quiz->timelimit,
            'overduehandling' => $quiz->overduehandling,
            'graceperiod' => (int)$quiz->graceperiod,
            'preferredbehaviour' => $quiz->preferredbehaviour,
            'canredoquestions' => (int)$quiz->canredoquestions,
            'attempts' => (int)$quiz->attempts,
            'attemptonlast' => (int)$quiz->attemptonlast,
            'grademethod' => (int)$quiz->grademethod,
            'decimalpoints' => (int)$quiz->decimalpoints,
            'questiondecimalpoints' => (int)$quiz->questiondecimalpoints,
            'reviewattempt' => (int)$quiz->reviewattempt,
            'reviewcorrectness' => (int)$quiz->reviewcorrectness,
            'reviewmarks' => (int)$quiz->reviewmarks,
            'reviewspecificfeedback' => (int)$quiz->reviewspecificfeedback,
            'reviewgeneralfeedback' => (int)$quiz->reviewgeneralfeedback,
            'reviewrightanswer' => (int)$quiz->reviewrightanswer,
            'reviewoverallfeedback' => (int)$quiz->reviewoverallfeedback,
            'reviewmaxmarks' => (int)$quiz->reviewmaxmarks,
            'questionsperpage' => (int)$quiz->questionsperpage,
            'navmethod' => $quiz->navmethod,
            'shuffleanswers' => (int)$quiz->shuffleanswers,
            'sumgrades' => (float)$quiz->sumgrades,
            'grade' => (float)$quiz->grade,
            'timecreated' => (int)$quiz->timecreated,
            'timemodified' => (int)$quiz->timemodified,
            'password' => $quiz->password ?? '',
            'subnet' => $quiz->subnet ?? '',
            'browsersecurity' => $quiz->browsersecurity ?? '-',
            'delay1' => (int)$quiz->delay1,
            'delay2' => (int)$quiz->delay2,
            'showuserpicture' => (int)$quiz->showuserpicture,
            'showblocks' => (int)$quiz->showblocks,
            'completionattemptsexhausted' => (int)$quiz->completionattemptsexhausted,
            'completionminattempts' => (int)$quiz->completionminattempts,
            'allowofflineattempts' => (int)$quiz->allowofflineattempts,
            'visible' => (int)$cm->visible,
            'attemptcount' => $attemptcount,
            'sections' => $sectionsarray,
            'questions' => $questionsarray,
            'success' => true,
            'message' => 'Quiz retrieved successfully with ' . count($questionsarray) . ' question(s)',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Quiz ID'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'coursename' => new external_value(PARAM_TEXT, 'Course full name'),
            'name' => new external_value(PARAM_TEXT, 'Quiz name'),
            'intro' => new external_value(PARAM_RAW, 'Quiz introduction'),
            'introformat' => new external_value(PARAM_INT, 'Intro format'),
            'timeopen' => new external_value(PARAM_INT, 'Quiz open time'),
            'timeclose' => new external_value(PARAM_INT, 'Quiz close time'),
            'timelimit' => new external_value(PARAM_INT, 'Time limit in seconds'),
            'overduehandling' => new external_value(PARAM_TEXT, 'Overdue handling method'),
            'graceperiod' => new external_value(PARAM_INT, 'Grace period in seconds'),
            'preferredbehaviour' => new external_value(PARAM_TEXT, 'Question behaviour'),
            'canredoquestions' => new external_value(PARAM_INT, 'Can redo questions'),
            'attempts' => new external_value(PARAM_INT, 'Number of allowed attempts'),
            'attemptonlast' => new external_value(PARAM_INT, 'Build on last attempt'),
            'grademethod' => new external_value(PARAM_INT, 'Grade method'),
            'decimalpoints' => new external_value(PARAM_INT, 'Decimal points for grades'),
            'questiondecimalpoints' => new external_value(PARAM_INT, 'Question decimal points'),
            'reviewattempt' => new external_value(PARAM_INT, 'Review attempt options'),
            'reviewcorrectness' => new external_value(PARAM_INT, 'Review correctness options'),
            'reviewmarks' => new external_value(PARAM_INT, 'Review marks options'),
            'reviewspecificfeedback' => new external_value(PARAM_INT, 'Review specific feedback options'),
            'reviewgeneralfeedback' => new external_value(PARAM_INT, 'Review general feedback options'),
            'reviewrightanswer' => new external_value(PARAM_INT, 'Review right answer options'),
            'reviewoverallfeedback' => new external_value(PARAM_INT, 'Review overall feedback options'),
            'reviewmaxmarks' => new external_value(PARAM_INT, 'Review max marks options'),
            'questionsperpage' => new external_value(PARAM_INT, 'Questions per page'),
            'navmethod' => new external_value(PARAM_TEXT, 'Navigation method'),
            'shuffleanswers' => new external_value(PARAM_INT, 'Shuffle answers'),
            'sumgrades' => new external_value(PARAM_FLOAT, 'Sum of question grades'),
            'grade' => new external_value(PARAM_FLOAT, 'Maximum grade'),
            'timecreated' => new external_value(PARAM_INT, 'Time created'),
            'timemodified' => new external_value(PARAM_INT, 'Time modified'),
            'password' => new external_value(PARAM_TEXT, 'Quiz password'),
            'subnet' => new external_value(PARAM_TEXT, 'Allowed IP subnet'),
            'browsersecurity' => new external_value(PARAM_TEXT, 'Browser security'),
            'delay1' => new external_value(PARAM_INT, 'Delay between attempts 1-2'),
            'delay2' => new external_value(PARAM_INT, 'Delay between later attempts'),
            'showuserpicture' => new external_value(PARAM_INT, 'Show user picture'),
            'showblocks' => new external_value(PARAM_INT, 'Show blocks'),
            'completionattemptsexhausted' => new external_value(PARAM_INT, 'Complete when attempts exhausted'),
            'completionminattempts' => new external_value(PARAM_INT, 'Minimum attempts for completion'),
            'allowofflineattempts' => new external_value(PARAM_INT, 'Allow offline attempts'),
            'visible' => new external_value(PARAM_INT, 'Module visibility'),
            'attemptcount' => new external_value(PARAM_INT, 'Total number of attempts'),
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Section ID'),
                    'firstslot' => new external_value(PARAM_INT, 'First slot number in section'),
                    'heading' => new external_value(PARAM_TEXT, 'Section heading'),
                    'shufflequestions' => new external_value(PARAM_INT, 'Shuffle questions in section'),
                ]),
                'Quiz sections'
            ),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'slotid' => new external_value(PARAM_INT, 'Slot ID'),
                    'slot' => new external_value(PARAM_INT, 'Slot number'),
                    'page' => new external_value(PARAM_INT, 'Page number'),
                    'maxmark' => new external_value(PARAM_FLOAT, 'Maximum mark for this question'),
                    'requireprevious' => new external_value(PARAM_INT, 'Require previous question'),
                    'displaynumber' => new external_value(PARAM_TEXT, 'Custom display number'),
                    'questionbankentryid' => new external_value(PARAM_INT, 'Question bank entry ID'),
                    'questionid' => new external_value(PARAM_INT, 'Question ID'),
                    'questionidnumber' => new external_value(PARAM_TEXT, 'Question ID number'),
                    'questionname' => new external_value(PARAM_TEXT, 'Question name'),
                    'qtype' => new external_value(PARAM_TEXT, 'Question type'),
                    'questiontext' => new external_value(PARAM_RAW, 'Question text'),
                    'questiontextformat' => new external_value(PARAM_INT, 'Question text format'),
                    'defaultmark' => new external_value(PARAM_FLOAT, 'Default mark'),
                    'generalfeedback' => new external_value(PARAM_RAW, 'General feedback'),
                    'generalfeedbackformat' => new external_value(PARAM_INT, 'General feedback format'),
                    'version' => new external_value(PARAM_INT, 'Question version'),
                    'status' => new external_value(PARAM_TEXT, 'Question status'),
                ]),
                'Quiz questions'
            ),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
