<?php
namespace local_createassign\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class create_assessment extends external_api {
    
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'Assignment name'),
            'intro' => new external_value(PARAM_RAW, 'Assignment description', VALUE_DEFAULT, ''),
            'duedate' => new external_value(PARAM_INT, 'Due date timestamp', VALUE_DEFAULT, 0),
            'cutoffdate' => new external_value(PARAM_INT, 'Cut-off date timestamp', VALUE_DEFAULT, 0),
            'section' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(
        int $courseid,
        string $name,
        string $intro = '',
        int $duedate = 0,
        int $cutoffdate = 0,
        int $section = 0
    ): array {
        global $CFG, $DB;
        
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/assign/lib.php');
        require_once($CFG->dirroot . '/lib/gradelib.php');
        
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'name' => $name,
            'intro' => $intro,
            'duedate' => $duedate,
            'cutoffdate' => $cutoffdate,
            'section' => $section,
        ]);
        
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);
        
        self::validate_context($context);
        require_capability('local/createassign:createassessment', $context);
        require_capability('mod/assign:addinstance', $context);
        
        $assign = new \stdClass();
        $assign->course = $params['courseid'];
        $assign->name = $params['name'];
        $assign->intro = $params['intro'];
        $assign->introformat = FORMAT_HTML;
        $assign->alwaysshowdescription = 0;
        $assign->submissiondrafts = 0;
        $assign->sendnotifications = 0;
        $assign->sendlatenotifications = 0;
        $assign->sendstudentnotifications = 1;
        $assign->duedate = $params['duedate'];
        $assign->cutoffdate = $params['cutoffdate'];
        $assign->gradingduedate = 0;
        $assign->allowsubmissionsfromdate = 0;
        $assign->grade = 100;
        $assign->timemodified = time();
        $assign->timecreated = time();
        $assign->teamsubmission = 0;
        $assign->requireallteammemberssubmit = 0;
        $assign->teamsubmissiongroupingid = 0;
        $assign->blindmarking = 0;
        $assign->hidegrader = 0;
        $assign->revealidentities = 0;
        $assign->attemptreopenmethod = 'none';
        $assign->maxattempts = -1;
        $assign->markingworkflow = 0;
        $assign->markingallocation = 0;
        $assign->requiresubmissionstatement = 0;
        $assign->preventsubmissionnotingroup = 0;
        $assign->activity = null;
        $assign->activityformat = 0;
        $assign->timelimit = 0;
        $assign->submissionattachments = 0;
        
        $assignid = $DB->insert_record('assign', $assign);
        
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST);
        
        $cm = new \stdClass();
        $cm->course = $params['courseid'];
        $cm->module = $moduleid;
        $cm->instance = $assignid;
        $cm->section = $params['section'];
        $cm->idnumber = '';
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
        
        $sectionid = $DB->get_field('course_sections', 'id', [
            'course' => $params['courseid'],
            'section' => $params['section']
        ]);
        
        if ($sectionid) {
            $section = $DB->get_record('course_sections', ['id' => $sectionid]);
            if (!empty($section->sequence)) {
                $sequence = $section->sequence . ',' . $cmid;
            } else {
                $sequence = $cmid;
            }
            $DB->set_field('course_sections', 'sequence', $sequence, ['id' => $sectionid]);
        }
        
        rebuild_course_cache($params['courseid'], true);
        
        grade_update('mod/assign', $params['courseid'], 'mod', 'assign', $assignid, 0, null, ['itemname' => $params['name']]);
        
        return [
            'id' => $assignid,
            'coursemoduleid' => $cmid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'Assignment created successfully'
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
