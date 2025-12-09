<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class create_question_matching extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Question category ID'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'questiontext' => new external_value(PARAM_RAW, 'Question text'),
            'questiontextformat' => new external_value(PARAM_INT, 'Question text format', VALUE_DEFAULT, FORMAT_HTML),
            'defaultmark' => new external_value(PARAM_FLOAT, 'Default mark/grade', VALUE_DEFAULT, 1.0),
            'generalfeedback' => new external_value(PARAM_RAW, 'General feedback', VALUE_DEFAULT, ''),
            'shuffleanswers' => new external_value(PARAM_BOOL, 'Shuffle answers', VALUE_DEFAULT, true),
            'correctfeedback' => new external_value(PARAM_RAW, 'Feedback for correct response', VALUE_DEFAULT, 'Your answer is correct.'),
            'partiallycorrectfeedback' => new external_value(PARAM_RAW, 'Feedback for partially correct response', VALUE_DEFAULT, 'Your answer is partially correct.'),
            'incorrectfeedback' => new external_value(PARAM_RAW, 'Feedback for incorrect response', VALUE_DEFAULT, 'Your answer is incorrect.'),
            'shownumcorrect' => new external_value(PARAM_BOOL, 'Show number of correct answers', VALUE_DEFAULT, true),
            'subquestions' => new external_value(PARAM_RAW, 'JSON array of subquestions with fields: questiontext, answertext'),
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
        bool $shuffleanswers = true,
        string $correctfeedback = 'Your answer is correct.',
        string $partiallycorrectfeedback = 'Your answer is partially correct.',
        string $incorrectfeedback = 'Your answer is incorrect.',
        bool $shownumcorrect = true,
        string $subquestions = '[]',
        float $penalty = 0.3333333,
        string $idnumber = ''
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'categoryid', 'name', 'questiontext', 'questiontextformat', 'defaultmark',
            'generalfeedback', 'shuffleanswers', 'correctfeedback', 'partiallycorrectfeedback',
            'incorrectfeedback', 'shownumcorrect', 'subquestions', 'penalty', 'idnumber'
        ));

        $category = $DB->get_record('question_categories', ['id' => $params['categoryid']], '*', MUST_EXIST);
        $context = \context::instance_by_id($category->contextid);

        self::validate_context($context);
        require_capability('local/activity_utils:createquestions', $context);
        require_capability('moodle/question:add', $context);

        // Decode subquestions
        $subquestionsdata = json_decode($params['subquestions'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($subquestionsdata) || empty($subquestionsdata)) {
            throw new \invalid_parameter_exception('Invalid subquestions JSON format');
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
        $question->qtype = 'match';
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
        $options->shuffleanswers = $params['shuffleanswers'] ? 1 : 0;
        $options->correctfeedback = $params['correctfeedback'];
        $options->correctfeedbackformat = FORMAT_HTML;
        $options->partiallycorrectfeedback = $params['partiallycorrectfeedback'];
        $options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $options->incorrectfeedback = $params['incorrectfeedback'];
        $options->incorrectfeedbackformat = FORMAT_HTML;
        $options->shownumcorrect = $params['shownumcorrect'] ? 1 : 0;

        $DB->insert_record('qtype_match_options', $options);

        // Insert subquestions
        foreach ($subquestionsdata as $subq) {
            $subquestion = new \stdClass();
            $subquestion->question = $questionid;
            $subquestion->questiontext = $subq['questiontext'] ?? '';
            $subquestion->questiontextformat = FORMAT_HTML;
            $subquestion->answertext = $subq['answertext'] ?? '';
            $subquestion->answertextformat = FORMAT_HTML;

            $DB->insert_record('qtype_match_subquestions', $subquestion);
        }

        return [
            'id' => $questionid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'Matching question created successfully'
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
