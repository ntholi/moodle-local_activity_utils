<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class create_question_essay extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Question category ID'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'questiontext' => new external_value(PARAM_RAW, 'Question text'),
            'questiontextformat' => new external_value(PARAM_INT, 'Question text format', VALUE_DEFAULT, FORMAT_HTML),
            'defaultmark' => new external_value(PARAM_FLOAT, 'Default mark/grade', VALUE_DEFAULT, 1.0),
            'generalfeedback' => new external_value(PARAM_RAW, 'General feedback', VALUE_DEFAULT, ''),
            'responseformat' => new external_value(PARAM_ALPHA, 'Response format: editor, editorfilepicker, plain, monospaced, noinline', VALUE_DEFAULT, 'editor'),
            'responserequired' => new external_value(PARAM_BOOL, 'Require text response', VALUE_DEFAULT, true),
            'responsefieldlines' => new external_value(PARAM_INT, 'Input box size (lines)', VALUE_DEFAULT, 15),
            'attachments' => new external_value(PARAM_INT, 'Number of attachments allowed', VALUE_DEFAULT, 0),
            'attachmentsrequired' => new external_value(PARAM_INT, 'Number of required attachments', VALUE_DEFAULT, 0),
            'maxbytes' => new external_value(PARAM_INT, 'Maximum file size in bytes (0 = course limit)', VALUE_DEFAULT, 0),
            'filetypeslist' => new external_value(PARAM_TEXT, 'Accepted file types (comma-separated)', VALUE_DEFAULT, ''),
            'graderinfo' => new external_value(PARAM_RAW, 'Information for graders', VALUE_DEFAULT, ''),
            'responsetemplate' => new external_value(PARAM_RAW, 'Response template', VALUE_DEFAULT, ''),
            'penalty' => new external_value(PARAM_FLOAT, 'Penalty factor (0-1)', VALUE_DEFAULT, 0),
            'idnumber' => new external_value(PARAM_RAW, 'ID number', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(
        int $categoryid,
        string $name,
        string $questiontext,
        int $questiontextformat = FORMAT_HTML,
        float $defaultmark = 1.0,
        string $generalfeedback = '',
        string $responseformat = 'editor',
        bool $responserequired = true,
        int $responsefieldlines = 15,
        int $attachments = 0,
        int $attachmentsrequired = 0,
        int $maxbytes = 0,
        string $filetypeslist = '',
        string $graderinfo = '',
        string $responsetemplate = '',
        float $penalty = 0,
        string $idnumber = ''
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'categoryid', 'name', 'questiontext', 'questiontextformat', 'defaultmark',
            'generalfeedback', 'responseformat', 'responserequired', 'responsefieldlines',
            'attachments', 'attachmentsrequired', 'maxbytes', 'filetypeslist',
            'graderinfo', 'responsetemplate', 'penalty', 'idnumber'
        ));

        $category = $DB->get_record('question_categories', ['id' => $params['categoryid']], '*', MUST_EXIST);
        $context = \context::instance_by_id($category->contextid);

        self::validate_context($context);
        require_capability('local/activity_utils:createquestions', $context);
        require_capability('moodle/question:add', $context);

        // Create question
        $question = new \stdClass();
        $question->category = $params['categoryid'];
        $question->parent = 0;
        $question->name = $params['name'];
        $question->questiontext = $params['questiontext'];
        $question->questiontextformat = $params['questiontextformat'];
        $question->generalfeedback = $params['generalfeedback'];
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->defaultmark = $params['defaultmark'];
        $question->penalty = $params['penalty'];
        $question->qtype = 'essay';
        $question->length = 1;
        $question->stamp = make_unique_id_code();
        $question->version = make_unique_id_code();
        $question->hidden = 0;
        $question->idnumber = $params['idnumber'];
        $question->timecreated = time();
        $question->timemodified = time();
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;

        $questionid = $DB->insert_record('question', $question);
        $question->id = $questionid;

        // Create question bank entry (Moodle 4.0+)
        $entry = new \stdClass();
        $entry->questioncategoryid = $params['categoryid'];
        $entry->idnumber = $params['idnumber'];
        $entry->ownerid = $USER->id;
        $entryid = $DB->insert_record('question_bank_entries', $entry);

        // Create question version (Moodle 4.0+)
        $version = new \stdClass();
        $version->questionbankentryid = $entryid;
        $version->questionid = $questionid;
        $version->version = 1;
        $version->status = 'ready';
        $DB->insert_record('question_versions', $version);

        // Map response format to database value
        $responseformatmap = [
            'editor' => 'editor',
            'editorfilepicker' => 'editorfilepicker',
            'plain' => 'plain',
            'monospaced' => 'monospaced',
            'noinline' => 'noinline'
        ];

        // Insert question type specific options
        $options = new \stdClass();
        $options->questionid = $questionid;
        $options->responseformat = $responseformatmap[$params['responseformat']] ?? 'editor';
        $options->responserequired = $params['responserequired'] ? 1 : 0;
        $options->responsefieldlines = $params['responsefieldlines'];
        $options->attachments = $params['attachments'];
        $options->attachmentsrequired = $params['attachmentsrequired'];
        $options->maxbytes = $params['maxbytes'];
        $options->filetypeslist = $params['filetypeslist'];
        $options->graderinfo = $params['graderinfo'];
        $options->graderinfoformat = FORMAT_HTML;
        $options->responsetemplate = $params['responsetemplate'];
        $options->responsetemplateformat = FORMAT_HTML;

        $DB->insert_record('qtype_essay_options', $options);

        return [
            'id' => $questionid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'Essay question created successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Question ID'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
