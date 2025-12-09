<?php
namespace local_activity_utils\external\file;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_activity_utils\helper;

class create_file extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'File resource name'),
            'intro' => new external_value(PARAM_RAW, 'File resource introduction/description', VALUE_DEFAULT, ''),
            'filename' => new external_value(PARAM_TEXT, 'File name'),
            'filecontent' => new external_value(PARAM_RAW, 'File content (base64 encoded)'),
            'section' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, 0),
            'visible' => new external_value(PARAM_INT, 'Visibility (1=visible, 0=hidden)', VALUE_DEFAULT, 1),
        ]);
    }

    public static function execute(
        int $courseid,
        string $name,
        string $intro = '',
        string $filename = '',
        string $filecontent = '',
        int $section = 0,
        int $visible = 1
    ): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/resource/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'name' => $name,
            'intro' => $intro,
            'filename' => $filename,
            'filecontent' => $filecontent,
            'section' => $section,
            'visible' => $visible,
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:createfile', $context);
        require_capability('mod/resource:addinstance', $context);

        $filename = clean_param($params['filename'], PARAM_FILE);
        if (empty($filename)) {
            throw new \moodle_exception('invalidfilename', 'local_activity_utils');
        }

        $resource = new \stdClass();
        $resource->course = $params['courseid'];
        $resource->name = $params['name'];
        $resource->intro = $params['intro'];
        $resource->introformat = FORMAT_HTML;
        $resource->tobemigrated = 0;
        $resource->legacyfiles = 0;
        $resource->legacyfileslast = null;
        $resource->display = 0; 
        $resource->displayoptions = 'a:2:{s:12:"printheading";s:1:"1";s:10:"printintro";s:1:"0";}';
        $resource->filterfiles = 0;
        $resource->revision = 1;
        $resource->timemodified = time();
        $resource->timecreated = time();

        $resourceid = $DB->insert_record('resource', $resource);

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'resource'], MUST_EXIST);

        $cm = new \stdClass();
        $cm->course = $params['courseid'];
        $cm->module = $moduleid;
        $cm->instance = $resourceid;
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

        $fs = get_file_storage();
        $modulecontext = \context_module::instance($cmid);

        $content = base64_decode($params['filecontent'], true);
        if ($content === false) {
            $content = $params['filecontent'];
        }

        $filerecord = [
            'contextid' => $modulecontext->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $USER->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $fs->create_file_from_string($filerecord, $content);

        
        helper::add_module_to_section($params['courseid'], $params['section'], $cmid, $params['visible']);

        rebuild_course_cache($params['courseid'], true);

        return [
            'id' => $resourceid,
            'coursemoduleid' => $cmid,
            'name' => $params['name'],
            'filename' => $filename,
            'success' => true,
            'message' => 'File resource created successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Resource ID'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Resource name'),
            'filename' => new external_value(PARAM_TEXT, 'File name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
