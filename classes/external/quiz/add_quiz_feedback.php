<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class add_quiz_feedback extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
            'feedbacktext' => new external_value(PARAM_RAW, 'Feedback text'),
            'mingrade' => new external_value(PARAM_FLOAT, 'Minimum grade percentage (0-100)'),
            'maxgrade' => new external_value(PARAM_FLOAT, 'Maximum grade percentage (0-100)'),
        ]);
    }

    public static function execute(
        int $quizid,
        string $feedbacktext,
        float $mingrade,
        float $maxgrade
    ): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'quizid', 'feedbacktext', 'mingrade', 'maxgrade'
        ));

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:managequizfeedback', $context);
        require_capability('mod/quiz:manage', $context);

        $mingradepercent = $params['mingrade'] / 100;
        $maxgradepercent = $params['maxgrade'] / 100;

        $feedback = new \stdClass();
        $feedback->quizid = $params['quizid'];
        $feedback->feedbacktext = $params['feedbacktext'];
        $feedback->feedbacktextformat = FORMAT_HTML;
        $feedback->mingrade = $mingradepercent;
        $feedback->maxgrade = $maxgradepercent;

        $feedbackid = $DB->insert_record('quiz_feedback', $feedback);

        return [
            'id' => $feedbackid,
            'success' => true,
            'message' => 'Quiz feedback added successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Feedback ID'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
