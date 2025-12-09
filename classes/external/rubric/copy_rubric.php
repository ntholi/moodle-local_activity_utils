<?php
namespace local_activity_utils\external\rubric;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;


class copy_rubric extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sourcecmid' => new external_value(PARAM_INT, 'Source course module ID (assignment with rubric to copy)'),
            'targetcmid' => new external_value(PARAM_INT, 'Target course module ID (assignment to copy rubric to)'),
        ]);
    }

    public static function execute(int $sourcecmid, int $targetcmid): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/grade/grading/lib.php');
        require_once($CFG->dirroot . '/grade/grading/form/rubric/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'sourcecmid' => $sourcecmid,
            'targetcmid' => $targetcmid,
        ]);

        
        $sourcecm = get_coursemodule_from_id('assign', $params['sourcecmid'], 0, false, MUST_EXIST);
        $sourcecontext = \context_module::instance($sourcecm->id);

        
        $targetcm = get_coursemodule_from_id('assign', $params['targetcmid'], 0, false, MUST_EXIST);
        $targetcontext = \context_module::instance($targetcm->id);

        
        self::validate_context($sourcecontext);
        require_capability('local/activity_utils:managerubric', $sourcecontext);

        self::validate_context($targetcontext);
        require_capability('local/activity_utils:managerubric', $targetcontext);
        require_capability('moodle/grade:managegradingforms', $targetcontext);

        
        $sourcegradingmanager = get_grading_manager($sourcecontext, 'mod_assign', 'submissions');

        
        if ($sourcegradingmanager->get_active_method() !== 'rubric') {
            return [
                'definitionid' => 0,
                'success' => false,
                'message' => 'Source assignment does not have a rubric',
            ];
        }

        $sourcecontroller = $sourcegradingmanager->get_controller('rubric');
        if (!$sourcecontroller->is_form_defined()) {
            return [
                'definitionid' => 0,
                'success' => false,
                'message' => 'Source assignment has no rubric defined',
            ];
        }

        $sourcedefinition = $sourcecontroller->get_definition();

        
        $targetgradingmanager = get_grading_manager($targetcontext, 'mod_assign', 'submissions');

        
        if ($targetgradingmanager->get_active_method() === 'rubric') {
            $targetcontroller = $targetgradingmanager->get_controller('rubric');
            if ($targetcontroller->is_form_defined()) {
                return [
                    'definitionid' => 0,
                    'success' => false,
                    'message' => 'Target assignment already has a rubric. Delete it first or use update_rubric.',
                ];
            }
        }

        
        $targetgradingmanager->set_active_method('rubric');
        $targetcontroller = $targetgradingmanager->get_controller('rubric');

        
        $sourcecriteria = $DB->get_records('gradingform_rubric_criteria', ['definitionid' => $sourcedefinition->id], 'sortorder ASC');

        $rubriccriteria = [];
        foreach ($sourcecriteria as $criterion) {
            $levels = $DB->get_records('gradingform_rubric_levels', ['criterionid' => $criterion->id], 'score ASC');

            $levelsdata = [];
            foreach ($levels as $level) {
                $levelsdata[] = [
                    'score' => $level->score,
                    'definition' => $level->definition,
                    'definitionformat' => FORMAT_HTML,
                ];
            }

            $rubriccriteria[] = [
                'description' => $criterion->description,
                'descriptionformat' => FORMAT_HTML,
                'sortorder' => $criterion->sortorder,
                'levels' => $levelsdata,
            ];
        }

        
        $sourceoptions = json_decode($sourcedefinition->options ?? '{}', true) ?: [];

        
        $rubricdata = [
            'name' => $sourcedefinition->name . ' (Copy)',
            'description_editor' => [
                'text' => $sourcedefinition->description ?? '',
                'format' => FORMAT_HTML,
            ],
            'rubric' => [
                'criteria' => $rubriccriteria,
                'options' => $sourceoptions,
            ],
            'status' => \gradingform_controller::DEFINITION_STATUS_READY,
        ];

        
        $targetcontroller->update_definition($rubricdata);

        $newdefinition = $targetcontroller->get_definition();

        return [
            'definitionid' => (int)$newdefinition->id,
            'success' => true,
            'message' => 'Rubric copied successfully',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'definitionid' => new external_value(PARAM_INT, 'New rubric definition ID'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
