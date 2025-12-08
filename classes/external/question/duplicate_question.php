<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class duplicate_question extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'Question ID to duplicate'),
            'newcategoryid' => new external_value(PARAM_INT, 'Target category ID (0 = same category)', VALUE_DEFAULT, 0),
            'newname' => new external_value(PARAM_TEXT, 'New question name (empty = auto)', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(
        int $questionid,
        int $newcategoryid = 0,
        string $newname = ''
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/question/editlib.php');

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'questionid', 'newcategoryid', 'newname'
        ));

        $question = $DB->get_record('question', ['id' => $params['questionid']], '*', MUST_EXIST);
        $category = $DB->get_record('question_categories', ['id' => $question->category'], '*', MUST_EXIST);
        $context = \context::instance_by_id($category->contextid);

        self::validate_context($context);
        require_capability('local/activity_utils:createquestions', $context);
        require_capability('moodle/question:add', $context);

        // Determine target category
        $targetcategoryid = $params['newcategoryid'] > 0 ? $params['newcategoryid'] : $question->category;

        if ($params['newcategoryid'] > 0) {
            $targetcategory = $DB->get_record('question_categories', ['id' => $targetcategoryid], '*', MUST_EXIST);
            $targetcontext = \context::instance_by_id($targetcategory->contextid);
            require_capability('moodle/question:add', $targetcontext);
        }

        // Use Moodle's question duplication function
        $newquestionid = question_make_copy($params['questionid'], $targetcategoryid);

        // Update name if provided
        if (!empty($params['newname'])) {
            $DB->set_field('question', 'name', $params['newname'], ['id' => $newquestionid]);
        } else {
            // Auto-generate name with " (copy)" suffix
            $newquestion = $DB->get_record('question', ['id' => $newquestionid]);
            if (!strpos($newquestion->name, '(copy)')) {
                $DB->set_field('question', 'name', $newquestion->name . ' (copy)', ['id' => $newquestionid]);
            }
        }

        $newquestion = $DB->get_record('question', ['id' => $newquestionid]);

        return [
            'id' => $newquestionid,
            'name' => $newquestion->name,
            'success' => true,
            'message' => 'Question duplicated successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'New question ID'),
            'name' => new external_value(PARAM_TEXT, 'New question name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
