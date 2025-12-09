<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class reorder_quiz_questions extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
            'slotorder' => new external_value(PARAM_RAW, 'JSON array of slot numbers in desired order'),
        ]);
    }

    public static function execute(int $quizid, string $slotorder): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), compact('quizid', 'slotorder'));

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:managequizquestions', $context);
        require_capability('mod/quiz:manage', $context);

        $order = json_decode($params['slotorder'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($order)) {
            throw new \invalid_parameter_exception('Invalid slotorder JSON format');
        }

        $currentslots = $DB->get_records('quiz_slots', ['quizid' => $params['quizid']], '', 'slot,id,page,requireprevious,questionid,maxmark');

        foreach ($order as $oldslot) {
            if (!isset($currentslots[$oldslot])) {
                throw new \invalid_parameter_exception("Slot $oldslot does not exist in quiz");
            }
        }

        $newslot = 1;
        foreach ($order as $oldslot) {
            $slotrecord = $currentslots[$oldslot];
            $DB->set_field('quiz_slots', 'slot', $newslot, ['id' => $slotrecord->id]);
            $newslot++;
        }

        return [
            'success' => true,
            'message' => 'Quiz questions reordered successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
