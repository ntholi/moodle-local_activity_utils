<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_quiz\grade_calculator;
use mod_quiz\quiz_settings;

class remove_question_from_quiz extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
            'slot' => new external_value(PARAM_INT, 'Slot number to remove'),
        ]);
    }

    public static function execute(int $quizid, int $slot): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), compact('quizid', 'slot'));

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:managequizquestions', $context);
        require_capability('mod/quiz:manage', $context);

        $slotrecord = $DB->get_record('quiz_slots', ['quizid' => $params['quizid'], 'slot' => $params['slot']], '*', MUST_EXIST);

        // Delete the slot
        $DB->delete_records('quiz_slots', ['id' => $slotrecord->id]);

        // Renumber remaining slots
        $slots = $DB->get_records('quiz_slots', ['quizid' => $params['quizid']], 'slot ASC');
        $newslot = 1;
        foreach ($slots as $s) {
            if ($s->slot != $newslot) {
                $DB->set_field('quiz_slots', 'slot', $newslot, ['id' => $s->id]);
            }
            $newslot++;
        }

        // Update quiz sum of grades
        $sumgrades = $DB->get_field_sql(
            'SELECT SUM(maxmark) FROM {quiz_slots} WHERE quizid = ?',
            [$params['quizid']]
        );
        $quiz->sumgrades = $sumgrades ?: 0;
        $DB->update_record('quiz', $quiz);

        // Update grade item using the new grade_calculator class
        $quizobj = quiz_settings::create($quiz->id);
        grade_calculator::create($quizobj)->recompute_quiz_sumgrades();

        return [
            'success' => true,
            'message' => 'Question removed from quiz successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
