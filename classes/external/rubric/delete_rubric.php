<?php
namespace local_activity_utils\external\rubric;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Delete a rubric from an assignment.
 *
 * This removes the rubric and reverts the assignment to simple grading.
 * Not available in the core Moodle API.
 */
class delete_rubric extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID of the assignment'),
        ]);
    }

    public static function execute(int $cmid): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/grade/grading/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
        ]);

        // Get the course module and verify it's an assignment.
        $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:managerubric', $context);
        require_capability('moodle/grade:managegradingforms', $context);

        // Get grading manager.
        $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');

        // Check if rubric is active.
        if ($gradingmanager->get_active_method() !== 'rubric') {
            return [
                'success' => false,
                'message' => 'No rubric is set for this assignment',
            ];
        }

        // Get the controller to check if form is defined.
        $controller = $gradingmanager->get_controller('rubric');

        if (!$controller->is_form_defined()) {
            // Just clear the method.
            $gradingmanager->set_active_method('');
            return [
                'success' => true,
                'message' => 'Grading method cleared (no rubric was defined)',
            ];
        }

        $definition = $controller->get_definition();
        $definitionid = $definition->id;

        // Check if there are any grading instances using this rubric.
        $instances = $DB->count_records('grading_instances', ['definitionid' => $definitionid]);
        if ($instances > 0) {
            // Delete the grading instances and their fillings.
            $instanceids = $DB->get_fieldset_select('grading_instances', 'id', 'definitionid = ?', [$definitionid]);
            foreach ($instanceids as $instanceid) {
                $DB->delete_records('gradingform_rubric_fillings', ['instanceid' => $instanceid]);
            }
            $DB->delete_records('grading_instances', ['definitionid' => $definitionid]);
        }

        // Delete rubric levels.
        $criteriaids = $DB->get_fieldset_select('gradingform_rubric_criteria', 'id', 'definitionid = ?', [$definitionid]);
        foreach ($criteriaids as $criteriaid) {
            $DB->delete_records('gradingform_rubric_levels', ['criterionid' => $criteriaid]);
        }

        // Delete rubric criteria.
        $DB->delete_records('gradingform_rubric_criteria', ['definitionid' => $definitionid]);

        // Delete the definition.
        $DB->delete_records('grading_definitions', ['id' => $definitionid]);

        // Clear the active method.
        $gradingmanager->set_active_method('');

        return [
            'success' => true,
            'message' => 'Rubric deleted successfully',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
