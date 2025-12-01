<?php
namespace local_activity_utils;

use core_external\external_api;

/**
 * Helper class for activity creation utilities.
 * Provides common functionality for creating activities and resources in subsections.
 */
class helper {

    /**
     * Get the course_sections record for a given section number.
     * Handles both regular sections and delegated sections (subsections).
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @return \stdClass|null The section record or null if not found
     */
    public static function get_section_by_number(int $courseid, int $sectionnum): ?\stdClass {
        global $DB;
        
        $section = $DB->get_record('course_sections', [
            'course' => $courseid,
            'section' => $sectionnum
        ]);
        
        return $section;
    }

    /**
     * Resolve section ID from section number, handling subsections.
     * Returns the actual course_sections.id that should be used for course_modules.section.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @return int|null Section ID or null if section doesn't exist
     */
    public static function resolve_section_id(int $courseid, int $sectionnum): ?int {
        $section = self::get_section_by_number($courseid, $sectionnum);
        return $section ? (int)$section->id : null;
    }

    /**
     * Add a course module to a section's sequence, handling both regular and delegated sections.
     * Also ensures the module inherits visibility from delegated section parent if applicable.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number (used for lookup)
     * @param int $cmid Course module ID to add
     * @param int $coursemodule_visible The module's visibility flag (1 or 0)
     * @return void
     */
    public static function add_module_to_section(int $courseid, int $sectionnum, int $cmid, int $coursemodule_visible): void {
        global $DB;

        $section = self::get_section_by_number($courseid, $sectionnum);
        if (!$section) {
            return;
        }

        // Add to sequence if section exists
        if (!empty($section->sequence)) {
            $sequence = $section->sequence . ',' . $cmid;
        } else {
            $sequence = (string)$cmid;
        }
        $DB->set_field('course_sections', 'sequence', $sequence, ['id' => $section->id]);

        // If this is a delegated section (subsection), ensure visibility cascades properly
        // A module in a subsection should be hidden if the subsection CM is hidden
        if (!empty($section->component) && $section->component === 'mod_subsection') {
            self::inherit_subsection_visibility($cmid, $section, $coursemodule_visible);
        }
    }

    /**
     * Ensure a module's visibility cascades from its delegated section parent.
     * If the subsection module is hidden, the activity should also be marked as hidden.
     *
     * @param int $cmid Course module ID
     * @param \stdClass $delegated_section The delegated section record
     * @param int $requested_visibility The visibility requested by the caller (1 or 0)
     * @return void
     */
    private static function inherit_subsection_visibility(int $cmid, \stdClass $delegated_section, int $requested_visibility): void {
        global $DB;

        // Get the subsection course module
        $subsection_cm = $DB->get_record('course_modules', [
            'instance' => (int)$delegated_section->itemid,
            'module' => $DB->get_field('modules', 'id', ['name' => 'subsection'])
        ]);

        if ($subsection_cm) {
            // If subsection is hidden, hide the activity too
            // Activity visibility should be: requested_visibility AND subsection_cm_visibility
            $final_visibility = $requested_visibility && $subsection_cm->visible ? 1 : 0;
            
            $DB->set_field('course_modules', 'visible', $final_visibility, ['id' => $cmid]);
            $DB->set_field('course_modules', 'visibleold', $final_visibility, ['id' => $cmid]);
        }
    }
}
