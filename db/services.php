<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_activity_utils_create_assignment' => array(
        'classname' => 'local_activity_utils\external\create_assignment',
        'methodname' => 'execute',
        'description' => 'Create a new assignment',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createassignment',
    ),
    'local_activity_utils_delete_assignment' => array(
        'classname' => 'local_activity_utils\external\delete_assignment',
        'methodname' => 'execute',
        'description' => 'Delete an existing assignment',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:deleteassignment',
    ),
    'local_activity_utils_create_section' => array(
        'classname' => 'local_activity_utils\external\create_section',
        'methodname' => 'execute',
        'description' => 'Create a new course section',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createsection',
    ),
    'local_activity_utils_create_page' => array(
        'classname' => 'local_activity_utils\external\create_page',
        'methodname' => 'execute',
        'description' => 'Create a new page activity',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createpage',
    ),
    'local_activity_utils_create_file' => array(
        'classname' => 'local_activity_utils\external\create_file',
        'methodname' => 'execute',
        'description' => 'Create a new file resource',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createfile',
    ),
    'local_activity_utils_create_subsection' => array(
        'classname' => 'local_activity_utils\external\create_subsection',
        'methodname' => 'execute',
        'description' => 'Create a new subsection within a course section',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createsubsection',
    ),
    'local_activity_utils_create_book' => array(
        'classname' => 'local_activity_utils\external\create_book',
        'methodname' => 'execute',
        'description' => 'Create a new book resource with optional chapters',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createbook',
    ),
    'local_activity_utils_add_book_chapter' => array(
        'classname' => 'local_activity_utils\external\add_book_chapter',
        'methodname' => 'execute',
        'description' => 'Add a chapter to an existing book',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createbook',
    ),
    'local_activity_utils_get_book' => array(
        'classname' => 'local_activity_utils\external\get_book',
        'methodname' => 'execute',
        'description' => 'Get complete book details with all chapters and content',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:readbook',
    ),
);

