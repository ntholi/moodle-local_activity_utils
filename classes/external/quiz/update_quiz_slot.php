<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_quiz\grade_calculator;
use mod_quiz\quiz_settings;

class update_quiz_slot extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
            'slot' => new external_value(PARAM_INT, 'Slot number'),
            'maxmark' => new external_value(PARAM_FLOAT, 'Maximum mark', VALUE_DEFAULT, null),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, null),
            'requireprevious' => new external_value(PARAM_BOOL, 'Require previous', VALUE_DEFAULT, null),
        ]);
    }

    public static function execute(
        int $quizid,
        int $slot,
        ?float $maxmark = null,
        ?int $page = null,
        ?bool $requireprevious = null
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'quizid', 'slot', 'maxmark', 'page', 'requireprevious'
        ));

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:managequizquestions', $context);
        require_capability('mod/quiz:manage', $context);

        $slotrecord = $DB->get_record('quiz_slots',
            ['quizid' => $params['quizid'], 'slot' => $params['slot']], '*', MUST_EXIST);

        $updated = false;

        if ($params['maxmark'] !== null) {
            $slotrecord->maxmark = $params['maxmark'];
            $updated = true;
        }
        if ($params['page'] !== null) {
            $slotrecord->page = $params['page'];
            $updated = true;
        }
        if ($params['requireprevious'] !== null) {
            $slotrecord->requireprevious = $params['requireprevious'] ? 1 : 0;
            $updated = true;
        }

        if ($updated) {
            $DB->update_record('quiz_slots', $slotrecord);

            if ($params['maxmark'] !== null) {
                $sumgrades = $DB->get_field_sql(
                    'SELECT SUM(maxmark) FROM {quiz_slots} WHERE quizid = ?',
                    [$params['quizid']]
                );
                $quiz->sumgrades = $sumgrades;
                $DB->update_record('quiz', $quiz);
                $quizobj = quiz_settings::create($quiz->id);
                grade_calculator::create($quizobj)->recompute_quiz_sumgrades();
            }
        }

        return [
            'success' => true,
            'message' => 'Quiz slot updated successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
