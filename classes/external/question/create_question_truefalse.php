<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class create_question_truefalse extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Question category ID'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'questiontext' => new external_value(PARAM_RAW, 'Question text'),
            'questiontextformat' => new external_value(PARAM_INT, 'Question text format', VALUE_DEFAULT, FORMAT_HTML),
            'defaultmark' => new external_value(PARAM_FLOAT, 'Default mark/grade', VALUE_DEFAULT, 1.0),
            'generalfeedback' => new external_value(PARAM_RAW, 'General feedback', VALUE_DEFAULT, ''),
            'correctanswer' => new external_value(PARAM_BOOL, 'Correct answer (true or false)'),
            'feedbacktrue' => new external_value(PARAM_RAW, 'Feedback for true answer', VALUE_DEFAULT, ''),
            'feedbackfalse' => new external_value(PARAM_RAW, 'Feedback for false answer', VALUE_DEFAULT, ''),
            'penalty' => new external_value(PARAM_FLOAT, 'Penalty factor (0-1)', VALUE_DEFAULT, 1.0),
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
        bool $correctanswer = true,
        string $feedbacktrue = '',
        string $feedbackfalse = '',
        float $penalty = 1.0,
        string $idnumber = ''
    ): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/question/engine/bank.php');
        require_once($CFG->dirroot . '/question/type/truefalse/questiontype.php');

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'categoryid', 'name', 'questiontext', 'questiontextformat', 'defaultmark',
            'generalfeedback', 'correctanswer', 'feedbacktrue', 'feedbackfalse', 'penalty', 'idnumber'
        ));

        // Generate unique idnumber if not provided to avoid duplicate key constraint
        if (empty($params['idnumber'])) {
            $params['idnumber'] = 'tf_' . time() . '_' . uniqid();
        }

        $category = $DB->get_record('question_categories', ['id' => $params['categoryid']], '*', MUST_EXIST);
        $context = \context::instance_by_id($category->contextid);

        self::validate_context($context);
        require_capability('local/activity_utils:createquestions', $context);
        require_capability('moodle/question:add', $context);

        // Create question using proper structure for Moodle 4.0+
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
        $question->qtype = 'truefalse';
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

        // Create true answer
        $trueanswer = new \stdClass();
        $trueanswer->question = $questionid;
        $trueanswer->answer = 'True';
        $trueanswer->answerformat = FORMAT_MOODLE;
        $trueanswer->fraction = $params['correctanswer'] ? 1 : 0;
        $trueanswer->feedback = $params['feedbacktrue'];
        $trueanswer->feedbackformat = FORMAT_HTML;
        $DB->insert_record('question_answers', $trueanswer);

        // Create false answer
        $falseanswer = new \stdClass();
        $falseanswer->question = $questionid;
        $falseanswer->answer = 'False';
        $falseanswer->answerformat = FORMAT_MOODLE;
        $falseanswer->fraction = $params['correctanswer'] ? 0 : 1;
        $falseanswer->feedback = $params['feedbackfalse'];
        $falseanswer->feedbackformat = FORMAT_HTML;
        $DB->insert_record('question_answers', $falseanswer);

        return [
            'id' => $questionid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'True/False question created successfully'
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
