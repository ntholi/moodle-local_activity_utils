<?php
namespace local_activity_utils\external\page;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_activity_utils\helper;

class create_page extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'Page name'),
            'intro' => new external_value(PARAM_RAW, 'Page introduction/description', VALUE_DEFAULT, ''),
            'content' => new external_value(PARAM_RAW, 'Page content (HTML)', VALUE_DEFAULT, ''),
            'section' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, 0),
            'visible' => new external_value(PARAM_INT, 'Visibility (1=visible, 0=hidden)', VALUE_DEFAULT, 1),
        ]);
    }

    public static function execute(
        int $courseid,
        string $name,
        string $intro = '',
        string $content = '',
        int $section = 0,
        int $visible = 1
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/page/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'name' => $name,
            'intro' => $intro,
            'content' => $content,
            'section' => $section,
            'visible' => $visible,
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:createpage', $context);
        require_capability('mod/page:addinstance', $context);

        $page = new \stdClass();
        $page->course = $params['courseid'];
        $page->name = $params['name'];
        $page->intro = $params['intro'];
        $page->introformat = FORMAT_HTML;
        $page->content = $params['content'];
        $page->contentformat = FORMAT_HTML;
        $page->legacyfiles = 0;
        $page->legacyfileslast = null;
        $page->display = 5; 
        $page->displayoptions = 'a:2:{s:12:"printheading";s:1:"1";s:10:"printintro";s:1:"0";}';
        $page->revision = 1;
        $page->timemodified = time();
        $page->timecreated = time();

        $pageid = $DB->insert_record('page', $page);

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST);

        $cm = new \stdClass();
        $cm->course = $params['courseid'];
        $cm->module = $moduleid;
        $cm->instance = $pageid;
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

        
        helper::add_module_to_section($params['courseid'], $params['section'], $cmid, $params['visible']);

        rebuild_course_cache($params['courseid'], true);

        return [
            'id' => $pageid,
            'coursemoduleid' => $cmid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'Page created successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Page ID'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Page name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
