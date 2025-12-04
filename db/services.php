<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    // Assignment functions
    'local_activity_utils_create_assignment' => array(
        'classname' => 'local_activity_utils\external\assignment\create_assignment',
        'methodname' => 'execute',
        'description' => 'Create a new assignment',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createassignment',
    ),
    'local_activity_utils_delete_assignment' => array(
        'classname' => 'local_activity_utils\external\assignment\delete_assignment',
        'methodname' => 'execute',
        'description' => 'Delete an existing assignment',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:deleteassignment',
    ),
    'local_activity_utils_update_assignment' => array(
        'classname' => 'local_activity_utils\external\assignment\update_assignment',
        'methodname' => 'execute',
        'description' => 'Update an existing assignment',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:updateassignment',
    ),

    // Book functions
    'local_activity_utils_create_book' => array(
        'classname' => 'local_activity_utils\external\book\create_book',
        'methodname' => 'execute',
        'description' => 'Create a new book resource with optional chapters',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createbook',
    ),
    'local_activity_utils_add_book_chapter' => array(
        'classname' => 'local_activity_utils\external\book\add_book_chapter',
        'methodname' => 'execute',
        'description' => 'Add a chapter to an existing book',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createbook',
    ),
    'local_activity_utils_get_book' => array(
        'classname' => 'local_activity_utils\external\book\get_book',
        'methodname' => 'execute',
        'description' => 'Get complete book details with all chapters and content',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:readbook',
    ),
    'local_activity_utils_update_book' => array(
        'classname' => 'local_activity_utils\external\book\update_book',
        'methodname' => 'execute',
        'description' => 'Update an existing book resource',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:updatebook',
    ),
    'local_activity_utils_update_book_chapter' => array(
        'classname' => 'local_activity_utils\external\book\update_book_chapter',
        'methodname' => 'execute',
        'description' => 'Update an existing book chapter',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:updatebook',
    ),

    // File functions
    'local_activity_utils_create_file' => array(
        'classname' => 'local_activity_utils\external\file\create_file',
        'methodname' => 'execute',
        'description' => 'Create a new file resource',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createfile',
    ),
    'local_activity_utils_update_file' => array(
        'classname' => 'local_activity_utils\external\file\update_file',
        'methodname' => 'execute',
        'description' => 'Update an existing file resource',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:updatefile',
    ),

    // Page functions
    'local_activity_utils_create_page' => array(
        'classname' => 'local_activity_utils\external\page\create_page',
        'methodname' => 'execute',
        'description' => 'Create a new page activity',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createpage',
    ),
    'local_activity_utils_update_page' => array(
        'classname' => 'local_activity_utils\external\page\update_page',
        'methodname' => 'execute',
        'description' => 'Update an existing page activity',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:updatepage',
    ),

    // Section functions
    'local_activity_utils_create_section' => array(
        'classname' => 'local_activity_utils\external\section\create_section',
        'methodname' => 'execute',
        'description' => 'Create a new course section',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createsection',
    ),
    'local_activity_utils_update_section' => array(
        'classname' => 'local_activity_utils\external\section\update_section',
        'methodname' => 'execute',
        'description' => 'Update an existing course section',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:updatesection',
    ),
    'local_activity_utils_create_subsection' => array(
        'classname' => 'local_activity_utils\external\section\create_subsection',
        'methodname' => 'execute',
        'description' => 'Create a new subsection within a course section',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createsubsection',
    ),
    'local_activity_utils_update_subsection' => array(
        'classname' => 'local_activity_utils\external\section\update_subsection',
        'methodname' => 'execute',
        'description' => 'Update an existing subsection',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:updatesubsection',
    ),

    // Rubric functions
    'local_activity_utils_create_rubric' => array(
        'classname' => 'local_activity_utils\external\rubric\create_rubric',
        'methodname' => 'execute',
        'description' => 'Create a rubric for an assignment',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:managerubric',
    ),
    'local_activity_utils_get_rubric' => array(
        'classname' => 'local_activity_utils\external\rubric\get_rubric',
        'methodname' => 'execute',
        'description' => 'Get rubric definition for an assignment',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:managerubric',
    ),
    'local_activity_utils_update_rubric' => array(
        'classname' => 'local_activity_utils\external\rubric\update_rubric',
        'methodname' => 'execute',
        'description' => 'Update an existing rubric',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:managerubric',
    ),
    'local_activity_utils_delete_rubric' => array(
        'classname' => 'local_activity_utils\external\rubric\delete_rubric',
        'methodname' => 'execute',
        'description' => 'Delete a rubric from an assignment',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:managerubric',
    ),
    'local_activity_utils_copy_rubric' => array(
        'classname' => 'local_activity_utils\external\rubric\copy_rubric',
        'methodname' => 'execute',
        'description' => 'Copy a rubric from one assignment to another',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:managerubric',
    ),

    // BigBlueButton functions
    'local_activity_utils_create_bigbluebuttonbn' => array(
        'classname' => 'local_activity_utils\external\bigbluebuttonbn\create_bigbluebuttonbn',
        'methodname' => 'execute',
        'description' => 'Create a new BigBlueButton activity',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:createbigbluebuttonbn',
    ),
    'local_activity_utils_update_bigbluebuttonbn' => array(
        'classname' => 'local_activity_utils\external\bigbluebuttonbn\update_bigbluebuttonbn',
        'methodname' => 'execute',
        'description' => 'Update an existing BigBlueButton activity',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activity_utils:updatebigbluebuttonbn',
    ),
);

