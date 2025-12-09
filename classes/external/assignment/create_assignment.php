<?php
namespace local_activity_utils\external\assignment;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_activity_utils\helper;

class create_assignment extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'Assignment name'),
            'intro' => new external_value(PARAM_RAW, 'Assignment description', VALUE_DEFAULT, ''),
            'activity' => new external_value(PARAM_RAW, 'Activity instructions', VALUE_DEFAULT, ''),
            'allowsubmissionsfromdate' => new external_value(PARAM_INT, 'Allow submissions from date timestamp', VALUE_DEFAULT, 0),
            'duedate' => new external_value(PARAM_INT, 'Due date timestamp', VALUE_DEFAULT, 0),
            'section' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, 0),
            'idnumber' => new external_value(PARAM_RAW, 'ID number for gradebook and external system reference', VALUE_DEFAULT, ''),
            'grademax' => new external_value(PARAM_INT, 'Maximum grade (can be negative to indicate use of a scale)', VALUE_DEFAULT, 100),
            'introfiles' => new external_value(PARAM_RAW, 'Additional files as JSON array', VALUE_DEFAULT, '[]'),
        ]);
    }

    public static function execute(
        int $courseid,
        string $name,
        string $intro = '',
        string $activity = '',
        int $allowsubmissionsfromdate = 0,
        int $duedate = 0,
        int $section = 0,
        string $idnumber = '',
        int $grademax = 100,
        string $introfiles = '[]'
    ): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/assign/lib.php');
        require_once($CFG->dirroot . '/lib/gradelib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'name' => $name,
            'intro' => $intro,
            'activity' => $activity,
            'allowsubmissionsfromdate' => $allowsubmissionsfromdate,
            'duedate' => $duedate,
            'section' => $section,
            'idnumber' => $idnumber,
            'grademax' => $grademax,
            'introfiles' => $introfiles,
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:createassignment', $context);
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
        $assign->cutoffdate = 0;
        $assign->gradingduedate = 0;
        $assign->allowsubmissionsfromdate = 0;
        $assign->grade = $params['grademax'];
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
        $assign->activity = $params['activity'];
        $assign->activityformat = FORMAT_HTML;
        $assign->timelimit = 0;
        $assign->submissionattachments = 0;
        $assign->allowsubmissionsfromdate = $params['allowsubmissionsfromdate'];

        $assignid = $DB->insert_record('assign', $assign);

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST);

        $cm = new \stdClass();
        $cm->course = $params['courseid'];
        $cm->module = $moduleid;
        $cm->instance = $assignid;
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

        grade_update('mod/assign', $params['courseid'], 'mod', 'assign', $assignid, 0, null, [
            'itemname' => $params['name'],
            'gradetype' => GRADE_TYPE_VALUE,
            'grademax' => $params['grademax'],
            'grademin' => 0
        ]);

        
        $pluginconfig = new \stdClass();
        $pluginconfig->assignment = $assignid;
        $pluginconfig->plugin = 'file';
        $pluginconfig->subtype = 'assignsubmission';
        $pluginconfig->name = 'enabled';
        $pluginconfig->value = '1';
        $DB->insert_record('assign_plugin_config', $pluginconfig);

        
        $pluginconfig = new \stdClass();
        $pluginconfig->assignment = $assignid;
        $pluginconfig->plugin = 'file';
        $pluginconfig->subtype = 'assignsubmission';
        $pluginconfig->name = 'maxfilesubmissions';
        $pluginconfig->value = '20';
        $DB->insert_record('assign_plugin_config', $pluginconfig);

        
        $pluginconfig = new \stdClass();
        $pluginconfig->assignment = $assignid;
        $pluginconfig->plugin = 'file';
        $pluginconfig->subtype = 'assignsubmission';
        $pluginconfig->name = 'maxsubmissionsizebytes';
        $pluginconfig->value = $CFG->maxbytes ?? '0';
        $DB->insert_record('assign_plugin_config', $pluginconfig);

        if (!empty($params['introfiles']) && $params['introfiles'] !== '[]') {
            $files = json_decode($params['introfiles'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($files) && !empty($files)) {
                $fs = get_file_storage();
                $modulecontext = \context_module::instance($cmid);

                foreach ($files as $file) {
                    if (!empty($file['filename']) && isset($file['content'])) {

                        $filename = clean_param($file['filename'], PARAM_FILE);
                        if (empty($filename)) {
                            continue;
                        }

                        $filepath = '/';
                        $existingfile = $fs->get_file(
                            $modulecontext->id,
                            'mod_assign',
                            'introattachment',
                            0,
                            $filepath,
                            $filename
                        );
                        if ($existingfile) {
                            $existingfile->delete();
                        }

                        $filerecord = [
                            'contextid' => $modulecontext->id,
                            'component' => 'mod_assign',
                            'filearea' => 'introattachment',
                            'itemid' => 0,
                            'filepath' => $filepath,
                            'filename' => $filename,
                            'userid' => $USER->id,
                            'timecreated' => time(),
                            'timemodified' => time(),
                        ];

                        $filecontent = base64_decode($file['content'], true);
                        if ($filecontent === false) {
                            $filecontent = $file['content'];
                        }

                        $fs->create_file_from_string($filerecord, $filecontent);
                    }
                }
            }
        }

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
