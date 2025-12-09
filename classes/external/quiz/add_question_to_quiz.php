<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_quiz\grade_calculator;

class add_question_to_quiz extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'page' => new external_value(PARAM_INT, 'Page number (0-based)', VALUE_DEFAULT, 0),
            'maxmark' => new external_value(PARAM_FLOAT, 'Maximum mark for this question in quiz', VALUE_DEFAULT, null),
            'requireprevious' => new external_value(PARAM_BOOL, 'Require previous question to be completed first', VALUE_DEFAULT, false),
        ]);
    }

    public static function execute(
        int $quizid,
        int $questionid,
        int $page = 0,
        ?float $maxmark = null,
        bool $requireprevious = false
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'quizid', 'questionid', 'page', 'maxmark', 'requireprevious'
        ));

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $question = $DB->get_record('question', ['id' => $params['questionid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:managequizquestions', $context);
        require_capability('mod/quiz:manage', $context);

        // Get the highest slot number currently in the quiz
        $maxslot = $DB->get_field_sql(
            'SELECT MAX(slot) FROM {quiz_slots} WHERE quizid = ?',
            [$params['quizid']]
        );
        $newslot = $maxslot ? $maxslot + 1 : 1;

        // Create quiz slot
        $slot = new \stdClass();
        $slot->quizid = $params['quizid'];
        $slot->slot = $newslot;
        $slot->page = $params['page'];
        $slot->requireprevious = $params['requireprevious'] ? 1 : 0;
        $slot->questionid = $params['questionid'];
        $slot->questioncategoryid = null;
        $slot->includingsubcategories = 0;
        $slot->maxmark = $params['maxmark'] ?? $question->defaultmark;

        $slotid = $DB->insert_record('quiz_slots', $slot);

        // Update quiz sum of grades
        $sumgrades = $DB->get_field_sql(
            'SELECT SUM(maxmark) FROM {quiz_slots} WHERE quizid = ?',
            [$params['quizid']]
        );
        $quiz->sumgrades = $sumgrades;
        $DB->update_record('quiz', $quiz);

        // Update grade item using the new grade_calculator class
        grade_calculator::create($quiz)->recompute_quiz_sumgrades();

        return [
            'slotid' => $slotid,
            'slot' => $newslot,
            'success' => true,
            'message' => 'Question added to quiz successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'slotid' => new external_value(PARAM_INT, 'Slot ID'),
            'slot' => new external_value(PARAM_INT, 'Slot number'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
