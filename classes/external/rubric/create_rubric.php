<?php
namespace local_activity_utils\external\rubric;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * Create a rubric for an assignment.
 *
 * This provides a simplified API for creating rubrics compared to core_grading_save_definitions.
 */
class create_rubric extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID of the assignment'),
            'name' => new external_value(PARAM_TEXT, 'Rubric name'),
            'description' => new external_value(PARAM_RAW, 'Rubric description', VALUE_DEFAULT, ''),
            'criteria' => new external_multiple_structure(
                new external_single_structure([
                    'description' => new external_value(PARAM_RAW, 'Criterion description'),
                    'sortorder' => new external_value(PARAM_INT, 'Sort order', VALUE_DEFAULT, 0),
                    'levels' => new external_multiple_structure(
                        new external_single_structure([
                            'score' => new external_value(PARAM_FLOAT, 'Score for this level'),
                            'definition' => new external_value(PARAM_RAW, 'Level definition/description'),
                        ]),
                        'Rubric levels for this criterion'
                    ),
                ]),
                'Rubric criteria with levels'
            ),
            'options' => new external_single_structure([
                'sortlevelsasc' => new external_value(PARAM_INT, 'Sort levels ascending (0 or 1)', VALUE_DEFAULT, 1),
                'lockzeropoints' => new external_value(PARAM_INT, 'Lock zero points (0 or 1)', VALUE_DEFAULT, 1),
                'showdescriptionstudent' => new external_value(PARAM_INT, 'Show description to student (0 or 1)', VALUE_DEFAULT, 1),
                'showdescriptionteacher' => new external_value(PARAM_INT, 'Show description to teacher (0 or 1)', VALUE_DEFAULT, 1),
                'showscoreteacher' => new external_value(PARAM_INT, 'Show score to teacher (0 or 1)', VALUE_DEFAULT, 1),
                'showscorestudent' => new external_value(PARAM_INT, 'Show score to student (0 or 1)', VALUE_DEFAULT, 1),
                'enableremarks' => new external_value(PARAM_INT, 'Enable remarks (0 or 1)', VALUE_DEFAULT, 1),
                'showremarksstudent' => new external_value(PARAM_INT, 'Show remarks to student (0 or 1)', VALUE_DEFAULT, 1),
            ], 'Rubric options', VALUE_DEFAULT, []),
        ]);
    }

    public static function execute(
        int $cmid,
        string $name,
        string $description,
        array $criteria,
        array $options = []
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/grade/grading/lib.php');
        require_once($CFG->dirroot . '/grade/grading/form/rubric/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'name' => $name,
            'description' => $description,
            'criteria' => $criteria,
            'options' => $options,
        ]);

        // Get the course module and verify it's an assignment.
        $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:managerubric', $context);
        require_capability('moodle/grade:managegradingforms', $context);

        // Get or create grading area.
        $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');

        // Check if a rubric already exists.
        $currentmethod = $gradingmanager->get_active_method();
        if ($currentmethod === 'rubric') {
            $controller = $gradingmanager->get_controller('rubric');
            if ($controller->is_form_defined()) {
                return [
                    'definitionid' => 0,
                    'success' => false,
                    'message' => 'A rubric already exists for this assignment. Use update_rubric to modify it.',
                ];
            }
        }

        // Set rubric as the active method.
        $gradingmanager->set_active_method('rubric');

        // Get the controller.
        $controller = $gradingmanager->get_controller('rubric');
        $definitionid = $controller->get_definition() ? $controller->get_definition()->id : null;

        // Build the rubric definition data.
        // Moodle expects criteria and levels to be keyed by 'NEWIDn' for new items.
        $rubriccriteria = [];
        $sortorder = 1;
        $criterionindex = 1;
        foreach ($params['criteria'] as $criterion) {
            $criterionkey = 'NEWID' . $criterionindex;
            $criteriondata = [
                'description' => $criterion['description'],
                'descriptionformat' => FORMAT_HTML,
                'sortorder' => $criterion['sortorder'] ?: $sortorder,
                'levels' => [],
            ];

            $levelindex = 1;
            foreach ($criterion['levels'] as $level) {
                $levelkey = 'NEWID' . $levelindex;
                $criteriondata['levels'][$levelkey] = [
                    'score' => $level['score'],
                    'definition' => $level['definition'],
                    'definitionformat' => FORMAT_HTML,
                ];
                $levelindex++;
            }

            $rubriccriteria[$criterionkey] = $criteriondata;
            $sortorder++;
            $criterionindex++;
        }

        // Set default options.
        $defaultoptions = [
            'sortlevelsasc' => 1,
            'lockzeropoints' => 1,
            'showdescriptionstudent' => 1,
            'showdescriptionteacher' => 1,
            'showscoreteacher' => 1,
            'showscorestudent' => 1,
            'enableremarks' => 1,
            'showremarksstudent' => 1,
        ];
        $rubricoptions = array_merge($defaultoptions, $params['options']);

        // Prepare the form data as stdClass (required by update_definition).
        $rubricdata = new \stdClass();
        $rubricdata->name = $params['name'];
        $rubricdata->description_editor = [
            'text' => $params['description'],
            'format' => FORMAT_HTML,
        ];
        $rubricdata->rubric = [
            'criteria' => $rubriccriteria,
            'options' => $rubricoptions,
        ];
        $rubricdata->status = \gradingform_controller::DEFINITION_STATUS_READY;

        // Update the definition.
        $controller->update_definition($rubricdata);

        // Get the new definition ID.
        $definition = $controller->get_definition();

        return [
            'definitionid' => $definition->id,
            'success' => true,
            'message' => 'Rubric created successfully',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'definitionid' => new external_value(PARAM_INT, 'Rubric definition ID'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
