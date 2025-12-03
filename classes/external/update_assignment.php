<?php
namespace local_activity_utils\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function for updating an existing assignment.
 *
 * @package    local_activity_utils
 * @copyright  2024 Activity Utils
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_assignment extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID to update'),
            'name' => new external_value(PARAM_TEXT, 'Assignment name', VALUE_DEFAULT, null),
            'intro' => new external_value(PARAM_RAW, 'Assignment description', VALUE_DEFAULT, null),
            'activity' => new external_value(PARAM_RAW, 'Activity instructions', VALUE_DEFAULT, null),
            'allowsubmissionsfromdate' => new external_value(PARAM_INT, 'Allow submissions from date timestamp', VALUE_DEFAULT, null),
            'duedate' => new external_value(PARAM_INT, 'Due date timestamp', VALUE_DEFAULT, null),
            'cutoffdate' => new external_value(PARAM_INT, 'Cut-off date timestamp', VALUE_DEFAULT, null),
            'idnumber' => new external_value(PARAM_RAW, 'ID number for gradebook and external system reference', VALUE_DEFAULT, null),
            'grademax' => new external_value(PARAM_INT, 'Maximum grade', VALUE_DEFAULT, null),
            'visible' => new external_value(PARAM_INT, 'Visibility (1=visible, 0=hidden)', VALUE_DEFAULT, null),
        ]);
    }

    public static function execute(
        int $assignmentid,
        ?string $name = null,
        ?string $intro = null,
        ?string $activity = null,
        ?int $allowsubmissionsfromdate = null,
        ?int $duedate = null,
        ?int $cutoffdate = null,
        ?string $idnumber = null,
        ?int $grademax = null,
        ?int $visible = null
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/assign/lib.php');
        require_once($CFG->dirroot . '/lib/gradelib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'assignmentid' => $assignmentid,
            'name' => $name,
            'intro' => $intro,
            'activity' => $activity,
            'allowsubmissionsfromdate' => $allowsubmissionsfromdate,
            'duedate' => $duedate,
            'cutoffdate' => $cutoffdate,
            'idnumber' => $idnumber,
            'grademax' => $grademax,
            'visible' => $visible,
        ]);

        // Get the assignment record.
        $assign = $DB->get_record('assign', ['id' => $params['assignmentid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('assign', $assign->id, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:updateassignment', $context);
        require_capability('mod/assign:addinstance', $context);

        // Update assignment fields if provided.
        $updated = false;

        if ($params['name'] !== null) {
            $assign->name = $params['name'];
            $updated = true;
        }
        if ($params['intro'] !== null) {
            $assign->intro = $params['intro'];
            $updated = true;
        }
        if ($params['activity'] !== null) {
            $assign->activity = $params['activity'];
            $updated = true;
        }
        if ($params['allowsubmissionsfromdate'] !== null) {
            $assign->allowsubmissionsfromdate = $params['allowsubmissionsfromdate'];
            $updated = true;
        }
        if ($params['duedate'] !== null) {
            $assign->duedate = $params['duedate'];
            $updated = true;
        }
        if ($params['cutoffdate'] !== null) {
            $assign->cutoffdate = $params['cutoffdate'];
            $updated = true;
        }
        if ($params['grademax'] !== null) {
            $assign->grade = $params['grademax'];
            $updated = true;
        }

        if ($updated) {
            $assign->timemodified = time();
            $DB->update_record('assign', $assign);
        }

        // Update course module fields if provided.
        if ($params['idnumber'] !== null || $params['visible'] !== null) {
            if ($params['idnumber'] !== null) {
                $cm->idnumber = $params['idnumber'];
            }
            if ($params['visible'] !== null) {
                $cm->visible = $params['visible'];
                $cm->visibleold = $params['visible'];
            }
            $DB->update_record('course_modules', $cm);
        }

        // Update grade item if grademax changed.
        if ($params['grademax'] !== null || $params['name'] !== null) {
            grade_update('mod/assign', $course->id, 'mod', 'assign', $assign->id, 0, null, [
                'itemname' => $assign->name,
                'gradetype' => GRADE_TYPE_VALUE,
                'grademax' => $assign->grade,
                'grademin' => 0
            ]);
        }

        rebuild_course_cache($course->id, true);

        return [
            'id' => $assign->id,
            'coursemoduleid' => $cm->id,
            'name' => $assign->name,
            'success' => true,
            'message' => 'Assignment updated successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Assignment ID'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Assignment name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
