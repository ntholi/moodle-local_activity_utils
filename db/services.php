<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_createassign_create_assessment' => array(
        'classname' => 'local_createassign\external\create_assessment',
        'methodname' => 'execute',
        'description' => 'Create a new assignment/assessment',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/createassign:createassessment',
    ),
);

