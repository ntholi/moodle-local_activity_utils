<?php
namespace local_activity_utils\external\forum;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_activity_utils\helper;

class create_forum extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'Forum name'),
            'intro' => new external_value(PARAM_RAW, 'Forum description', VALUE_DEFAULT, ''),
            'type' => new external_value(PARAM_TEXT, 'Forum type (general, news, social, eachuser, single, qanda, blog)', VALUE_DEFAULT, 'general'),
            'section' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, 0),
            'idnumber' => new external_value(PARAM_RAW, 'ID number', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(
        int $courseid,
        string $name,
        string $intro = '',
        string $type = 'general',
        int $section = 0,
        string $idnumber = ''
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'name' => $name,
            'intro' => $intro,
            'type' => $type,
            'section' => $section,
            'idnumber' => $idnumber,
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('mod/forum:addinstance', $context);

        $forum = new \stdClass();
        $forum->course = $params['courseid'];
        $forum->name = $params['name'];
        $forum->intro = $params['intro'];
        $forum->introformat = FORMAT_HTML;
        $forum->type = $params['type'];
        $forum->assessed = 0;
        $forum->assesstimestart = 0;
        $forum->assesstimefinish = 0;
        $forum->scale = 0;
        $forum->maxbytes = 0;
        $forum->maxattachments = 1;
        $forum->forcesubscribe = 0;
        $forum->trackingtype = 1;
        $forum->rsstype = 0;
        $forum->rssarticles = 0;
        $forum->timemodified = time();
        $forum->warnafter = 0;
        $forum->blockafter = 0;
        $forum->blockperiod = 0;
        $forum->completiondiscussions = 0;
        $forum->completionreplies = 0;
        $forum->completionposts = 0;
        $forum->displaywordcount = 0;
        $forum->lockdiscussionafter = 0;
        $forum->duedate = 0;
        $forum->cutoffdate = 0;

        $forumid = $DB->insert_record('forum', $forum);

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'forum'], MUST_EXIST);

        $cm = new \stdClass();
        $cm->course = $params['courseid'];
        $cm->module = $moduleid;
        $cm->instance = $forumid;
        $cm->section = $params['section'];
        $cm->idnumber = $params['idnumber'];
        $cm->added = time();
        $cm->score = 0;
        $cm->indent = 0;
        $cm->visible = 1;
        $cm->visibleoncoursepage = 1;
        $cm->visibleold = 1;
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

        helper::add_module_to_section($params['courseid'], $params['section'], $cmid, 1);

        rebuild_course_cache($params['courseid'], true);

        return [
            'id' => $forumid,
            'coursemoduleid' => $cmid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'Forum created successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Forum ID'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Forum name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
