<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * Create a multiple choice question (single or multiple answer).
 */
class create_multichoice_question extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Question category ID'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'questiontext' => new external_value(PARAM_RAW, 'Question text (HTML)'),
            'answers' => new external_multiple_structure(
                new external_single_structure([
                    'text' => new external_value(PARAM_RAW, 'Answer text (HTML)'),
                    'fraction' => new external_value(PARAM_FLOAT, 'Grade fraction (1.0 = 100%, 0.5 = 50%, 0 = 0%, negative for penalties)'),
                    'feedback' => new external_value(PARAM_RAW, 'Feedback for this answer', VALUE_DEFAULT, ''),
                ]),
                'Array of answer options'
            ),
            'defaultmark' => new external_value(PARAM_FLOAT, 'Default mark (points)', VALUE_DEFAULT, 1.0),
            'single' => new external_value(PARAM_INT, '1 = single answer (radio), 0 = multiple answers (checkboxes)', VALUE_DEFAULT, 1),
            'shuffleanswers' => new external_value(PARAM_INT, 'Shuffle answer order (1=yes, 0=no)', VALUE_DEFAULT, 1),
            'answernumbering' => new external_value(PARAM_ALPHA, 'Answer numbering: abc, ABC, 123, iii, III, none', VALUE_DEFAULT, 'abc'),
            'correctfeedback' => new external_value(PARAM_RAW, 'Feedback for correct answer', VALUE_DEFAULT, ''),
            'partiallycorrectfeedback' => new external_value(PARAM_RAW, 'Feedback for partially correct answer', VALUE_DEFAULT, ''),
            'incorrectfeedback' => new external_value(PARAM_RAW, 'Feedback for incorrect answer', VALUE_DEFAULT, ''),
            'generalfeedback' => new external_value(PARAM_RAW, 'General feedback shown after attempt', VALUE_DEFAULT, ''),
            'idnumber' => new external_value(PARAM_RAW, 'ID number for the question', VALUE_DEFAULT, ''),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Tag name'),
                'Tags for the question',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    public static function execute(
        int $categoryid,
        string $name,
        string $questiontext,
        array $answers,
        float $defaultmark = 1.0,
        int $single = 1,
        int $shuffleanswers = 1,
        string $answernumbering = 'abc',
        string $correctfeedback = '',
        string $partiallycorrectfeedback = '',
        string $incorrectfeedback = '',
        string $generalfeedback = '',
        string $idnumber = '',
        array $tags = []
    ): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/question/engine/lib.php');
        require_once($CFG->dirroot . '/question/engine/bank.php');
        require_once($CFG->libdir . '/questionlib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'categoryid' => $categoryid,
            'name' => $name,
            'questiontext' => $questiontext,
            'answers' => $answers,
            'defaultmark' => $defaultmark,
            'single' => $single,
            'shuffleanswers' => $shuffleanswers,
            'answernumbering' => $answernumbering,
            'correctfeedback' => $correctfeedback,
            'partiallycorrectfeedback' => $partiallycorrectfeedback,
            'incorrectfeedback' => $incorrectfeedback,
            'generalfeedback' => $generalfeedback,
            'idnumber' => $idnumber,
            'tags' => $tags,
        ]);

        // Validate category exists.
        $category = $DB->get_record('question_categories', ['id' => $params['categoryid']], '*', MUST_EXIST);
        $context = \context::instance_by_id($category->contextid);

        self::validate_context($context);
        require_capability('local/activity_utils:createquestion', $context);
        require_capability('moodle/question:add', $context);

        // Validate answers.
        if (empty($params['answers']) || count($params['answers']) < 2) {
            return [
                'questionid' => 0,
                'questionbankentryid' => 0,
                'name' => '',
                'success' => false,
                'message' => 'Multiple choice questions require at least 2 answers',
            ];
        }

        // Validate answernumbering.
        $validnumbering = ['abc', 'ABC', '123', 'iii', 'III', 'none'];
        if (!in_array($params['answernumbering'], $validnumbering)) {
            $params['answernumbering'] = 'abc';
        }

        // Create the question record.
        $question = new \stdClass();
        $question->category = $params['categoryid'];
        $question->parent = 0;
        $question->name = $params['name'];
        $question->questiontext = $params['questiontext'];
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = $params['generalfeedback'];
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->defaultmark = $params['defaultmark'];
        $question->penalty = 0.3333333;
        $question->qtype = 'multichoice';
        $question->length = 1;
        $question->stamp = make_unique_id_code();
        $question->timecreated = time();
        $question->timemodified = time();
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;

        $questionid = $DB->insert_record('question', $question);

        // Create question_bank_entries record.
        $qbe = new \stdClass();
        $qbe->questioncategoryid = $params['categoryid'];
        $qbe->idnumber = !empty($params['idnumber']) ? $params['idnumber'] : null;
        $qbe->ownerid = $USER->id;

        $qbeid = $DB->insert_record('question_bank_entries', $qbe);

        // Create question_versions record.
        $qv = new \stdClass();
        $qv->questionbankentryid = $qbeid;
        $qv->questionid = $questionid;
        $qv->version = 1;
        $qv->status = 'ready';

        $DB->insert_record('question_versions', $qv);

        // Create multichoice options.
        $options = new \stdClass();
        $options->questionid = $questionid;
        $options->single = $params['single'];
        $options->shuffleanswers = $params['shuffleanswers'];
        $options->answernumbering = $params['answernumbering'];
        $options->showstandardinstruction = 1;
        $options->correctfeedback = $params['correctfeedback'];
        $options->correctfeedbackformat = FORMAT_HTML;
        $options->partiallycorrectfeedback = $params['partiallycorrectfeedback'];
        $options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $options->incorrectfeedback = $params['incorrectfeedback'];
        $options->incorrectfeedbackformat = FORMAT_HTML;
        $options->shownumcorrect = 0;

        $DB->insert_record('qtype_multichoice_options', $options);

        // Create answers.
        foreach ($params['answers'] as $answerdata) {
            $answer = new \stdClass();
            $answer->question = $questionid;
            $answer->answer = $answerdata['text'];
            $answer->answerformat = FORMAT_HTML;
            $answer->fraction = $answerdata['fraction'];
            $answer->feedback = $answerdata['feedback'] ?? '';
            $answer->feedbackformat = FORMAT_HTML;

            $DB->insert_record('question_answers', $answer);
        }

        // Add tags if provided.
        if (!empty($params['tags'])) {
            \core_tag_tag::set_item_tags('core_question', 'question', $questionid, $context, $params['tags']);
        }

        return [
            'questionid' => (int)$questionid,
            'questionbankentryid' => (int)$qbeid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'Multiple choice question created successfully',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'questionbankentryid' => new external_value(PARAM_INT, 'Question bank entry ID (use this to add to quiz)'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
