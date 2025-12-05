<?php
namespace local_activity_utils\external\url;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_activity_utils\helper;

class create_url extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'URL resource name'),
            'externalurl' => new external_value(PARAM_URL, 'The external URL'),
            'intro' => new external_value(PARAM_RAW, 'URL resource description', VALUE_DEFAULT, ''),
            'section' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, 0),
            'visible' => new external_value(PARAM_INT, 'Visibility (1=visible, 0=hidden)', VALUE_DEFAULT, 1),
            'display' => new external_value(PARAM_INT, 'Display type (0=auto, 1=embed, 2=frame, 5=open, 6=popup)', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(
        int $courseid,
        string $name,
        string $externalurl,
        string $intro = '',
        int $section = 0,
        int $visible = 1,
        int $display = 0
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/url/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'name' => $name,
            'externalurl' => $externalurl,
            'intro' => $intro,
            'section' => $section,
            'visible' => $visible,
            'display' => $display,
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:createurl', $context);
        require_capability('mod/url:addinstance', $context);

        $url = new \stdClass();
        $url->course = $params['courseid'];
        $url->name = $params['name'];
        $url->intro = $params['intro'];
        $url->introformat = FORMAT_HTML;
        $url->externalurl = $params['externalurl'];
        $url->display = $params['display'];
        $url->displayoptions = 'a:1:{s:10:"printintro";i:1;}';
        $url->parameters = 'a:0:{}';
        $url->timemodified = time();

        $urlid = $DB->insert_record('url', $url);

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'url'], MUST_EXIST);

        $cm = new \stdClass();
        $cm->course = $params['courseid'];
        $cm->module = $moduleid;
        $cm->instance = $urlid;
        $cm->section = $params['section'];
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

        // Add module to section sequence, handling both regular and delegated (subsection) sections
        helper::add_module_to_section($params['courseid'], $params['section'], $cmid, $params['visible']);

        rebuild_course_cache($params['courseid'], true);

        return [
            'id' => $urlid,
            'coursemoduleid' => $cmid,
            'name' => $params['name'],
            'externalurl' => $params['externalurl'],
            'success' => true,
            'message' => 'URL resource created successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'URL resource ID'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'URL resource name'),
            'externalurl' => new external_value(PARAM_URL, 'The external URL'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
