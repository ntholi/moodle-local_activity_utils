<?php
namespace local_activity_utils\external\rubric;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

class fill_rubric extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID of the assignment'),
            'userid' => new external_value(PARAM_INT, 'User ID of the student being graded'),
            'fillings' => new external_multiple_structure(
                new external_single_structure([
                    'criterionid' => new external_value(PARAM_INT, 'Criterion ID'),
                    'levelid' => new external_value(PARAM_INT, 'Level ID to select for this criterion', VALUE_DEFAULT, 0),
                    'score' => new external_value(PARAM_FLOAT, 'Custom score (optional, overrides levelid)', VALUE_DEFAULT, null),
                    'remark' => new external_value(PARAM_RAW, 'Remark/feedback for this criterion', VALUE_DEFAULT, ''),
                ]),
                'Rubric fillings (selected levels/scores and remarks for each criterion)'
            ),
            'overallremark' => new external_value(PARAM_RAW, 'Overall feedback/remark for the grading', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(
        int $cmid,
        int $userid,
        array $fillings,
        string $overallremark = ''
    ): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/grade/grading/lib.php');
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'userid' => $userid,
            'fillings' => $fillings,
            'overallremark' => $overallremark,
        ]);

        $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:graderubric', $context);
        require_capability('mod/assign:grade', $context);

        $user = $DB->get_record('user', ['id' => $params['userid']], '*', MUST_EXIST);
        if (!is_enrolled($context, $user)) {
            return [
                'instanceid' => 0,
                'grade' => 0,
                'success' => false,
                'message' => 'User is not enrolled in this course',
            ];
        }

        $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');
        $activemethod = $gradingmanager->get_active_method();

        if ($activemethod !== 'fivedays') {
            return [
                'instanceid' => 0,
                'grade' => 0,
                'success' => false,
                'message' => 'Assignment does not use FiveDays rubric grading',
            ];
        }

        $controller = $gradingmanager->get_controller('fivedays');

        if (!$controller->is_form_defined()) {
            return [
                'instanceid' => 0,
                'grade' => 0,
                'success' => false,
                'message' => 'FiveDays rubric is not defined for this assignment',
            ];
        }

        $assignment = new \assign($context, $cm, $cm->course);
        $submission = $assignment->get_user_submission($params['userid'], false);

        if (!$submission) {
            return [
                'instanceid' => 0,
                'grade' => 0,
                'success' => false,
                'message' => 'No submission found for this user',
            ];
        }

        $grade = $assignment->get_user_grade($params['userid'], true);
        $instance = $controller->get_or_create_instance($grade->id, $USER->id, $grade->id);

        $definition = $controller->get_definition();
        $criteria = $DB->get_records('gradingform_fivedays_criteria', ['definitionid' => $definition->id], '', 'id');

        $validfillings = [];
        foreach ($params['fillings'] as $filling) {
            if (!isset($criteria[$filling['criterionid']])) {
                return [
                    'instanceid' => 0,
                    'grade' => 0,
                    'success' => false,
                    'message' => 'Invalid criterion ID: ' . $filling['criterionid'],
                ];
            }

            if (!empty($filling['levelid'])) {
                $level = $DB->get_record('gradingform_fivedays_levels', [
                    'id' => $filling['levelid'],
                    'criterionid' => $filling['criterionid']
                ]);

                if (!$level) {
                    return [
                        'instanceid' => 0,
                        'grade' => 0,
                        'success' => false,
                        'message' => 'Invalid level ID: ' . $filling['levelid'] . ' for criterion: ' . $filling['criterionid'],
                    ];
                }
            }

            $validfillings[$filling['criterionid']] = [
                'levelid' => $filling['levelid'] ?? null,
                'score' => $filling['score'] ?? null,
                'remark' => $filling['remark'] ?? '',
            ];
        }

        $instancedata = [
            'criteria' => []
        ];

        foreach ($validfillings as $criterionid => $filling) {
            $instancedata['criteria'][$criterionid] = [
                'levelid' => $filling['levelid'],
                'score' => $filling['score'],
                'remark' => $filling['remark'],
            ];
        }

        $instance->update($instancedata);

        $gradevalue = $instance->get_grade();

        $gradedata = new \stdClass();
        $gradedata->userid = $params['userid'];
        $gradedata->grade = $gradevalue;
        $gradedata->attemptnumber = $submission->attemptnumber;

        if (!empty($params['overallremark'])) {
            $gradedata->assignfeedbackcomments_editor = [
                'text' => $params['overallremark'],
                'format' => FORMAT_HTML,
            ];
        }

        $assignment->save_grade($params['userid'], $gradedata);

        return [
            'instanceid' => $instance->get_id(),
            'grade' => (float)$gradevalue,
            'success' => true,
            'message' => 'FiveDays rubric filled and grade saved successfully',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'instanceid' => new external_value(PARAM_INT, 'Grading instance ID'),
            'grade' => new external_value(PARAM_FLOAT, 'Calculated grade from rubric'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
