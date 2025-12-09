<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class delete_question extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'Question ID to delete'),
        ]);
    }

    public static function execute(int $questionid): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/question/editlib.php');

        $params = self::validate_parameters(self::execute_parameters(), ['questionid' => $questionid]);

        $question = $DB->get_record('question', ['id' => $params['questionid']], '*', MUST_EXIST);
        $category = $DB->get_record('question_categories', ['id' => $question->category], '*', MUST_EXIST);
        $context = \context::instance_by_id($category->contextid);

        self::validate_context($context);
        require_capability('local/activity_utils:deletequestions', $context);
        require_capability('moodle/question:editall', $context);

        $questionname = $question->name;

        $usage = $DB->get_records('quiz_slots', ['questionid' => $params['questionid']]);
        if (!empty($usage)) {
            $quizcount = count($usage);
            return [
                'success' => false,
                'message' => "Cannot delete question: it is being used in $quizcount quiz(es). Remove it from all quizzes first."
            ];
        }

        question_delete_question($params['questionid']);

        return [
            'success' => true,
            'message' => "Question '$questionname' deleted successfully"
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
