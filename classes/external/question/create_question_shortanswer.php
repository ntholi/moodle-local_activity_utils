<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class create_question_shortanswer extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Question category ID'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'questiontext' => new external_value(PARAM_RAW, 'Question text'),
            'questiontextformat' => new external_value(PARAM_INT, 'Question text format', VALUE_DEFAULT, FORMAT_HTML),
            'defaultmark' => new external_value(PARAM_FLOAT, 'Default mark/grade', VALUE_DEFAULT, 1.0),
            'generalfeedback' => new external_value(PARAM_RAW, 'General feedback', VALUE_DEFAULT, ''),
            'usecase' => new external_value(PARAM_BOOL, 'Case sensitive', VALUE_DEFAULT, false),
            'answers' => new external_value(PARAM_RAW, 'JSON array of answers with fields: text, fraction (0-1 for weight), feedback'),
            'penalty' => new external_value(PARAM_FLOAT, 'Penalty factor (0-1)', VALUE_DEFAULT, 0.3333333),
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
        bool $usecase = false,
        string $answers = '[]',
        float $penalty = 0.3333333,
        string $idnumber = ''
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'categoryid', 'name', 'questiontext', 'questiontextformat', 'defaultmark',
            'generalfeedback', 'usecase', 'answers', 'penalty', 'idnumber'
        ));

        $category = $DB->get_record('question_categories', ['id' => $params['categoryid']], '*', MUST_EXIST);
        $context = \context::instance_by_id($category->contextid);

        self::validate_context($context);
        require_capability('local/activity_utils:createquestions', $context);
        require_capability('moodle/question:add', $context);

        // Decode answers
        $answersdata = json_decode($params['answers'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($answersdata) || empty($answersdata)) {
            throw new \invalid_parameter_exception('Invalid answers JSON format');
        }

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
        $question->qtype = 'shortanswer';
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

        // Insert question type specific options
        $options = new \stdClass();
        $options->questionid = $questionid;
        $options->usecase = $params['usecase'] ? 1 : 0;

        $DB->insert_record('qtype_shortanswer_options', $options);

        // Insert answers
        foreach ($answersdata as $answerdata) {
            $answer = new \stdClass();
            $answer->question = $questionid;
            $answer->answer = $answerdata['text'] ?? '';
            $answer->answerformat = FORMAT_MOODLE;
            $answer->fraction = $answerdata['fraction'] ?? 0;
            $answer->feedback = $answerdata['feedback'] ?? '';
            $answer->feedbackformat = FORMAT_HTML;

            $DB->insert_record('question_answers', $answer);
        }

        return [
            'id' => $questionid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'Short answer question created successfully'
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
