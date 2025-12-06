<?php
namespace local_activity_utils\external\rubric;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * Get rubric filling (grading) for a student's assignment submission.
 *
 * This retrieves how a teacher graded a student using the rubric.
 */
class get_rubric_filling extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID of the assignment'),
            'userid' => new external_value(PARAM_INT, 'User ID of the student'),
        ]);
    }

    public static function execute(int $cmid, int $userid): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/grade/grading/lib.php');
        require_once($CFG->dirroot . '/grade/grading/form/rubric/lib.php');
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'userid' => $userid,
        ]);

        // Get the course module and verify it's an assignment.
        $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:viewrubricfilling', $context);

        // Verify the user exists.
        $user = $DB->get_record('user', ['id' => $params['userid']], '*', MUST_EXIST);

        // Get grading manager and verify rubric is active.
        $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');
        $activemethod = $gradingmanager->get_active_method();

        if ($activemethod !== 'rubric') {
            return [
                'instanceid' => 0,
                'grade' => 0,
                'grader' => '',
                'timecreated' => 0,
                'timemodified' => 0,
                'fillings' => [],
                'success' => false,
                'message' => 'Assignment does not use rubric grading',
            ];
        }

        // Get the rubric controller.
        $controller = $gradingmanager->get_controller('rubric');

        if (!$controller->is_form_defined()) {
            return [
                'instanceid' => 0,
                'grade' => 0,
                'grader' => '',
                'timecreated' => 0,
                'timemodified' => 0,
                'fillings' => [],
                'success' => false,
                'message' => 'Rubric is not defined for this assignment',
            ];
        }

        // Get the assignment.
        $assignment = new \assign($context, $cm, $cm->course);

        // Get the user's grade.
        $grade = $assignment->get_user_grade($params['userid'], false);

        if (!$grade) {
            return [
                'instanceid' => 0,
                'grade' => 0,
                'grader' => '',
                'timecreated' => 0,
                'timemodified' => 0,
                'fillings' => [],
                'success' => false,
                'message' => 'No grade found for this user',
            ];
        }

        // Get the grading instance.
        $instances = $DB->get_records('grading_instances', [
            'definitionid' => $controller->get_definition()->id,
            'itemid' => $grade->id
        ], 'timemodified DESC', '*', 0, 1);

        if (empty($instances)) {
            return [
                'instanceid' => 0,
                'grade' => (float)$grade->grade,
                'grader' => '',
                'timecreated' => 0,
                'timemodified' => 0,
                'fillings' => [],
                'success' => false,
                'message' => 'No rubric grading found for this user',
            ];
        }

        $instance = reset($instances);

        // Get the grader's name.
        $grader = $DB->get_record('user', ['id' => $instance->raterid], 'id, firstname, lastname');
        $gradername = $grader ? fullname($grader) : 'Unknown';

        // Get the rubric fillings.
        $fillingsdb = $DB->get_records('gradingform_rubric_fillings', ['instanceid' => $instance->id]);

        $fillings = [];
        foreach ($fillingsdb as $filling) {
            // Get criterion details.
            $criterion = $DB->get_record('gradingform_rubric_criteria', ['id' => $filling->criterionid]);
            
            // Get level details if a level is selected.
            $leveldata = null;
            if ($filling->levelid) {
                $level = $DB->get_record('gradingform_rubric_levels', ['id' => $filling->levelid]);
                if ($level) {
                    $leveldata = [
                        'id' => (int)$level->id,
                        'score' => (float)$level->score,
                        'definition' => $level->definition,
                    ];
                }
            }

            $fillings[] = [
                'criterionid' => (int)$filling->criterionid,
                'criteriondescription' => $criterion ? $criterion->description : '',
                'levelid' => (int)$filling->levelid,
                'level' => $leveldata,
                'remark' => $filling->remark ?? '',
            ];
        }

        return [
            'instanceid' => (int)$instance->id,
            'grade' => (float)$grade->grade,
            'grader' => $gradername,
            'graderid' => (int)$instance->raterid,
            'timecreated' => (int)$instance->timecreated,
            'timemodified' => (int)$instance->timemodified,
            'fillings' => $fillings,
            'success' => true,
            'message' => 'Rubric filling retrieved successfully',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'instanceid' => new external_value(PARAM_INT, 'Grading instance ID'),
            'grade' => new external_value(PARAM_FLOAT, 'Final grade'),
            'grader' => new external_value(PARAM_TEXT, 'Name of the grader'),
            'graderid' => new external_value(PARAM_INT, 'User ID of the grader'),
            'timecreated' => new external_value(PARAM_INT, 'Timestamp when grading was created'),
            'timemodified' => new external_value(PARAM_INT, 'Timestamp when grading was last modified'),
            'fillings' => new external_multiple_structure(
                new external_single_structure([
                    'criterionid' => new external_value(PARAM_INT, 'Criterion ID'),
                    'criteriondescription' => new external_value(PARAM_RAW, 'Criterion description'),
                    'levelid' => new external_value(PARAM_INT, 'Selected level ID (0 if not selected)'),
                    'level' => new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Level ID'),
                        'score' => new external_value(PARAM_FLOAT, 'Score for this level'),
                        'definition' => new external_value(PARAM_RAW, 'Level definition'),
                    ], 'Selected level details', VALUE_OPTIONAL),
                    'remark' => new external_value(PARAM_RAW, 'Remark for this criterion'),
                ])
            ),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
