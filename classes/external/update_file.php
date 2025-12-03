<?php
namespace local_activity_utils\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function for updating an existing file resource.
 *
 * @package    local_activity_utils
 * @copyright  2024 Activity Utils
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_file extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'resourceid' => new external_value(PARAM_INT, 'Resource ID to update'),
            'name' => new external_value(PARAM_TEXT, 'File resource name', VALUE_DEFAULT, null),
            'intro' => new external_value(PARAM_RAW, 'File resource introduction/description', VALUE_DEFAULT, null),
            'filename' => new external_value(PARAM_TEXT, 'New file name (requires filecontent)', VALUE_DEFAULT, null),
            'filecontent' => new external_value(PARAM_RAW, 'New file content (base64 encoded)', VALUE_DEFAULT, null),
            'visible' => new external_value(PARAM_INT, 'Visibility (1=visible, 0=hidden)', VALUE_DEFAULT, null),
        ]);
    }

    public static function execute(
        int $resourceid,
        ?string $name = null,
        ?string $intro = null,
        ?string $filename = null,
        ?string $filecontent = null,
        ?int $visible = null
    ): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/resource/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'resourceid' => $resourceid,
            'name' => $name,
            'intro' => $intro,
            'filename' => $filename,
            'filecontent' => $filecontent,
            'visible' => $visible,
        ]);

        // Get the resource record.
        $resource = $DB->get_record('resource', ['id' => $params['resourceid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('resource', $resource->id, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:updatefile', $context);
        require_capability('mod/resource:addinstance', $context);

        // Update resource fields if provided.
        $updated = false;

        if ($params['name'] !== null) {
            $resource->name = $params['name'];
            $updated = true;
        }
        if ($params['intro'] !== null) {
            $resource->intro = $params['intro'];
            $updated = true;
        }

        // Handle file replacement if new content is provided.
        $currentfilename = null;
        if ($params['filecontent'] !== null) {
            $modulecontext = \context_module::instance($cm->id);
            $fs = get_file_storage();

            // Get the current file to potentially replace.
            $files = $fs->get_area_files($modulecontext->id, 'mod_resource', 'content', 0, 'sortorder', false);
            foreach ($files as $file) {
                $currentfilename = $file->get_filename();
                break;
            }

            // Determine the filename to use.
            $newfilename = $params['filename'];
            if (empty($newfilename)) {
                $newfilename = $currentfilename;
            }
            if (empty($newfilename)) {
                throw new \moodle_exception('invalidfilename', 'local_activity_utils');
            }
            $newfilename = clean_param($newfilename, PARAM_FILE);

            // Delete existing files.
            $fs->delete_area_files($modulecontext->id, 'mod_resource', 'content');

            // Decode content.
            $content = base64_decode($params['filecontent'], true);
            if ($content === false) {
                $content = $params['filecontent'];
            }

            // Create new file.
            $filerecord = [
                'contextid' => $modulecontext->id,
                'component' => 'mod_resource',
                'filearea' => 'content',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $newfilename,
                'userid' => $USER->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ];

            $fs->create_file_from_string($filerecord, $content);
            $resource->revision = $resource->revision + 1;
            $updated = true;
            $currentfilename = $newfilename;
        } else if ($params['filename'] !== null) {
            // Just rename the file without changing content.
            $modulecontext = \context_module::instance($cm->id);
            $fs = get_file_storage();

            $files = $fs->get_area_files($modulecontext->id, 'mod_resource', 'content', 0, 'sortorder', false);
            foreach ($files as $file) {
                $newfilename = clean_param($params['filename'], PARAM_FILE);
                if (!empty($newfilename) && $newfilename !== $file->get_filename()) {
                    // Create a new file with the new name.
                    $filerecord = [
                        'contextid' => $modulecontext->id,
                        'component' => 'mod_resource',
                        'filearea' => 'content',
                        'itemid' => 0,
                        'filepath' => '/',
                        'filename' => $newfilename,
                    ];
                    $fs->create_file_from_storedfile($filerecord, $file);
                    $file->delete();
                    $currentfilename = $newfilename;
                    $resource->revision = $resource->revision + 1;
                    $updated = true;
                } else {
                    $currentfilename = $file->get_filename();
                }
                break;
            }
        } else {
            // Get current filename for response.
            $modulecontext = \context_module::instance($cm->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($modulecontext->id, 'mod_resource', 'content', 0, 'sortorder', false);
            foreach ($files as $file) {
                $currentfilename = $file->get_filename();
                break;
            }
        }

        if ($updated) {
            $resource->timemodified = time();
            $DB->update_record('resource', $resource);
        }

        // Update course module visibility if provided.
        if ($params['visible'] !== null) {
            $cm->visible = $params['visible'];
            $cm->visibleold = $params['visible'];
            $DB->update_record('course_modules', $cm);
        }

        rebuild_course_cache($course->id, true);

        return [
            'id' => $resource->id,
            'coursemoduleid' => $cm->id,
            'name' => $resource->name,
            'filename' => $currentfilename ?? '',
            'success' => true,
            'message' => 'File resource updated successfully'
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
