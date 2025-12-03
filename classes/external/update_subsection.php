<?php
namespace local_activity_utils\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function for updating an existing subsection.
 *
 * @package    local_activity_utils
 * @copyright  2024 Activity Utils
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_subsection extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sectionid' => new external_value(PARAM_INT, 'Subsection section ID to update'),
            'name' => new external_value(PARAM_TEXT, 'Subsection name', VALUE_DEFAULT, null),
            'summary' => new external_value(PARAM_RAW, 'Subsection summary/description', VALUE_DEFAULT, null),
            'visible' => new external_value(PARAM_INT, 'Visibility (1=visible, 0=hidden)', VALUE_DEFAULT, null),
        ]);
    }

    public static function execute(
        int $sectionid,
        ?string $name = null,
        ?string $summary = null,
        ?int $visible = null
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'sectionid' => $sectionid,
            'name' => $name,
            'summary' => $summary,
            'visible' => $visible,
        ]);

        // Get the section record (delegated section for subsection).
        $section = $DB->get_record('course_sections', ['id' => $params['sectionid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $section->course], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:updatesubsection', $context);
        require_capability('moodle/course:update', $context);

        // Verify this is a delegated section (subsection).
        if (empty($section->component) || $section->component !== 'mod_subsection') {
            throw new \moodle_exception('invalidsubsection', 'local_activity_utils');
        }

        // Update section fields if provided.
        $sectionupdated = false;

        if ($params['name'] !== null) {
            $section->name = $params['name'];
            $sectionupdated = true;
        }
        if ($params['summary'] !== null) {
            $section->summary = $params['summary'];
            $section->summaryformat = FORMAT_HTML;
            $sectionupdated = true;
        }
        if ($params['visible'] !== null) {
            $section->visible = $params['visible'];
            $sectionupdated = true;
        }

        if ($sectionupdated) {
            $section->timemodified = time();
            $DB->update_record('course_sections', $section);
        }

        // Also update the subsection module instance if name changed.
        if ($params['name'] !== null && !empty($section->itemid)) {
            $subsection = $DB->get_record('subsection', ['id' => $section->itemid]);
            if ($subsection) {
                $subsection->name = $params['name'];
                $subsection->timemodified = time();
                $DB->update_record('subsection', $subsection);
            }
        }

        // Update course module visibility if needed.
        if ($params['visible'] !== null && !empty($section->itemid)) {
            $cm = $DB->get_record('course_modules', [
                'instance' => $section->itemid,
                'module' => $DB->get_field('modules', 'id', ['name' => 'subsection'])
            ]);
            if ($cm) {
                $cm->visible = $params['visible'];
                $cm->visibleold = $params['visible'];
                $DB->update_record('course_modules', $cm);
            }
        }

        rebuild_course_cache($course->id, true);

        return [
            'id' => $section->id,
            'sectionnum' => $section->section,
            'name' => $section->name ?? '',
            'success' => true,
            'message' => 'Subsection updated successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Section ID'),
            'sectionnum' => new external_value(PARAM_INT, 'Section number'),
            'name' => new external_value(PARAM_TEXT, 'Subsection name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
