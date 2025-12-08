<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class get_or_create_question_category extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID (0 for system-wide)'),
            'name' => new external_value(PARAM_TEXT, 'Category name'),
            'info' => new external_value(PARAM_RAW, 'Category description', VALUE_DEFAULT, ''),
            'infoformat' => new external_value(PARAM_INT, 'Description format', VALUE_DEFAULT, FORMAT_HTML),
            'parent' => new external_value(PARAM_INT, 'Parent category ID (0 for top level)', VALUE_DEFAULT, 0),
            'idnumber' => new external_value(PARAM_RAW, 'ID number', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(
        int $courseid,
        string $name,
        string $info = '',
        int $infoformat = FORMAT_HTML,
        int $parent = 0,
        string $idnumber = ''
    ): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'name' => $name,
            'info' => $info,
            'infoformat' => $infoformat,
            'parent' => $parent,
            'idnumber' => $idnumber,
        ]);

        // Determine context
        if ($params['courseid'] == 0) {
            $context = \context_system::instance();
        } else {
            $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
            $context = \context_course::instance($course->id);
        }

        self::validate_context($context);
        require_capability('local/activity_utils:managequestioncategories', $context);
        require_capability('moodle/question:managecategory', $context);

        // If parent is specified, get its context
        $parentcontextid = $context->id;
        if ($params['parent'] > 0) {
            $parentcat = $DB->get_record('question_categories', ['id' => $params['parent']], '*', MUST_EXIST);
            $parentcontextid = $parentcat->contextid;
        }

        // Try to find existing category with same contextid and idnumber
        $conditions = [
            'contextid' => $parentcontextid,
            'idnumber' => $params['idnumber']
        ];

        $existingcategory = $DB->get_record('question_categories', $conditions);

        if ($existingcategory) {
            // Category exists - return it
            return [
                'id' => $existingcategory->id,
                'name' => $existingcategory->name,
                'contextid' => $existingcategory->contextid,
                'created' => false,
                'success' => true,
                'message' => 'Question category already exists'
            ];
        }

        // Category doesn't exist - create it
        $category = new \stdClass();
        $category->name = $params['name'];
        $category->contextid = $parentcontextid;
        $category->info = $params['info'];
        $category->infoformat = $params['infoformat'];
        $category->parent = $params['parent'];
        $category->sortorder = 999;
        $category->stamp = make_unique_id_code();
        $category->idnumber = $params['idnumber'];

        $categoryid = $DB->insert_record('question_categories', $category);

        return [
            'id' => $categoryid,
            'name' => $params['name'],
            'contextid' => $parentcontextid,
            'created' => true,
            'success' => true,
            'message' => 'Question category created successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Category ID'),
            'name' => new external_value(PARAM_TEXT, 'Category name'),
            'contextid' => new external_value(PARAM_INT, 'Context ID'),
            'created' => new external_value(PARAM_BOOL, 'Whether category was newly created (true) or already existed (false)'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
