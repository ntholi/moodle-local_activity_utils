<?php
namespace local_activity_utils;

use core_external\external_api;

class helper {

    public static function get_section_by_number(int $courseid, int $sectionnum): ?\stdClass {
        global $DB;
        
        $section = $DB->get_record('course_sections', [
            'course' => $courseid,
            'section' => $sectionnum
        ]);
        
        return $section;
    }

    public static function resolve_section_id(int $courseid, int $sectionnum): ?int {
        $section = self::get_section_by_number($courseid, $sectionnum);
        return $section ? (int)$section->id : null;
    }

    public static function add_module_to_section(int $courseid, int $sectionnum, int $cmid, int $coursemodule_visible): void {
        global $DB;

        $section = self::get_section_by_number($courseid, $sectionnum);
        if (!$section) {
            return;
        }

        if (!empty($section->sequence)) {
            $sequence = $section->sequence . ',' . $cmid;
        } else {
            $sequence = (string)$cmid;
        }
        $DB->set_field('course_sections', 'sequence', $sequence, ['id' => $section->id]);

        if (!empty($section->component) && $section->component === 'mod_subsection') {
            self::inherit_subsection_visibility($cmid, $section, $coursemodule_visible);
        }
    }

    private static function inherit_subsection_visibility(int $cmid, \stdClass $delegated_section, int $requested_visibility): void {
        global $DB;

        $subsection_cm = $DB->get_record('course_modules', [
            'instance' => (int)$delegated_section->itemid,
            'module' => $DB->get_field('modules', 'id', ['name' => 'subsection'])
        ]);

        if ($subsection_cm) {
            $final_visibility = $requested_visibility && $subsection_cm->visible ? 1 : 0;
            
            $DB->set_field('course_modules', 'visible', $final_visibility, ['id' => $cmid]);
            $DB->set_field('course_modules', 'visibleold', $final_visibility, ['id' => $cmid]);
        }
    }
}
