<?php
namespace local_activity_utils\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class create_subsection extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'parentsection' => new external_value(PARAM_INT, 'Parent section number'),
            'name' => new external_value(PARAM_TEXT, 'Subsection name'),
            'summary' => new external_value(PARAM_RAW, 'Subsection summary/description', VALUE_DEFAULT, ''),
            'visible' => new external_value(PARAM_INT, 'Visibility (1=visible, 0=hidden)', VALUE_DEFAULT, 1),
        ]);
    }

    public static function execute(
        int $courseid,
        int $parentsection,
        string $name,
        string $summary = '',
        int $visible = 1
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'parentsection' => $parentsection,
            'name' => $name,
            'summary' => $summary,
            'visible' => $visible,
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:createsubsection', $context);
        require_capability('moodle/course:update', $context);

        // Verify the subsection module exists (Moodle 4.0+)
        $subsectionmodule = $DB->get_record('modules', ['name' => 'subsection']);
        if (!$subsectionmodule) {
            throw new \moodle_exception('subsectionmodulenotfound', 'local_activity_utils');
        }

        // Verify parent section exists
        $parentsectionrecord = $DB->get_record('course_sections', [
            'course' => $params['courseid'],
            'section' => $params['parentsection']
        ], '*', MUST_EXIST);

        // Get the next available section number
        $maxsection = $DB->get_field_sql(
            'SELECT MAX(section) FROM {course_sections} WHERE course = ?',
            [$params['courseid']]
        );
        $newsectionnum = $maxsection + 1;

        // Create the subsection module instance first
        $subsection = new \stdClass();
        $subsection->course = $params['courseid'];
        $subsection->name = $params['name'];
        $subsection->timemodified = time();

        $subsectionid = $DB->insert_record('subsection', $subsection);

        // Create course module record for the subsection
        $cm = new \stdClass();
        $cm->course = $params['courseid'];
        $cm->module = $subsectionmodule->id;
        $cm->instance = $subsectionid;
        $cm->section = $params['parentsection'];
        $cm->idnumber = '';
        $cm->added = time();
        $cm->score = 0;
        $cm->indent = 0;
        $cm->visible = $params['visible'];
        $cm->visibleoncoursepage = 1;
        $cm->visibleold = $params['visible'];
        $cm->groupmode = 0;
        $cm->groupingid = 0;
        $cm->completion = 0;
        $cm->completionview = 0;
        $cm->completionexpected = 0;
        $cm->completionpassgrade = 0;
        $cm->showdescription = 0;
        $cm->availability = null;
        $cm->deletioninprogress = 0;
        $cm->downloadcontent = 1;
        $cm->lang = '';
        $cm->completiongradeitemnumber = null;

        $cmid = $DB->insert_record('course_modules', $cm);

        // Add course module to parent section sequence
        if (!empty($parentsectionrecord->sequence)) {
            $sequence = $parentsectionrecord->sequence . ',' . $cmid;
        } else {
            $sequence = $cmid;
        }
        $DB->set_field('course_sections', 'sequence', $sequence, ['id' => $parentsectionrecord->id]);

        // Create the new section record that will be the subsection
        $sectiondata = new \stdClass();
        $sectiondata->course = $params['courseid'];
        $sectiondata->section = $newsectionnum;
        $sectiondata->name = $params['name'];
        $sectiondata->summary = $params['summary'];
        $sectiondata->summaryformat = FORMAT_HTML;
        $sectiondata->visible = $params['visible'];
        $sectiondata->component = 'mod_subsection';
        $sectiondata->itemid = $cmid;
        $sectiondata->timemodified = time();

        $sectionid = $DB->insert_record('course_sections', $sectiondata);

        // Rebuild course cache
        rebuild_course_cache($params['courseid'], true);

        return [
            'id' => $sectionid,
            'sectionnum' => $newsectionnum,
            'coursemoduleid' => $cmid,
            'parentsection' => $params['parentsection'],
            'name' => $params['name'],
            'success' => true,
            'message' => 'Subsection created successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Section ID'),
            'sectionnum' => new external_value(PARAM_INT, 'Section number'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'parentsection' => new external_value(PARAM_INT, 'Parent section number'),
            'name' => new external_value(PARAM_TEXT, 'Subsection name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
