<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * Create a numerical question.
 */
class create_numerical_question extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Question category ID'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'questiontext' => new external_value(PARAM_RAW, 'Question text (HTML)'),
            'answers' => new external_multiple_structure(
                new external_single_structure([
                    'answer' => new external_value(PARAM_RAW, 'Numerical answer value'),
                    'tolerance' => new external_value(PARAM_FLOAT, 'Tolerance (error margin)', VALUE_DEFAULT, 0),
                    'fraction' => new external_value(PARAM_FLOAT, 'Grade fraction (1.0 = 100%)', VALUE_DEFAULT, 1.0),
                    'feedback' => new external_value(PARAM_RAW, 'Feedback for this answer', VALUE_DEFAULT, ''),
                ]),
                'Array of accepted numerical answers'
            ),
            'defaultmark' => new external_value(PARAM_FLOAT, 'Default mark (points)', VALUE_DEFAULT, 1.0),
            'unitgradingtype' => new external_value(PARAM_INT, 'Unit grading: 0 = not graded, 1 = fraction of response grade, 2 = fraction of total grade', VALUE_DEFAULT, 0),
            'unitpenalty' => new external_value(PARAM_FLOAT, 'Penalty for wrong unit (0-1)', VALUE_DEFAULT, 0.1),
            'showunits' => new external_value(PARAM_INT, 'Show units: 0 = text input, 1 = multichoice, 2 = dropdown, 3 = not visible', VALUE_DEFAULT, 3),
            'unitsleft' => new external_value(PARAM_INT, 'Units position: 0 = right, 1 = left', VALUE_DEFAULT, 0),
            'units' => new external_multiple_structure(
                new external_single_structure([
                    'unit' => new external_value(PARAM_RAW, 'Unit name (e.g., "m", "kg")'),
                    'multiplier' => new external_value(PARAM_FLOAT, 'Multiplier for this unit', VALUE_DEFAULT, 1.0),
                ]),
                'Array of units',
                VALUE_DEFAULT,
                []
            ),
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
        int $unitgradingtype = 0,
        float $unitpenalty = 0.1,
        int $showunits = 3,
        int $unitsleft = 0,
        array $units = [],
        string $generalfeedback = '',
        string $idnumber = '',
        array $tags = []
    ): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/question/engine/lib.php');
        require_once($CFG->libdir . '/questionlib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'categoryid' => $categoryid,
            'name' => $name,
            'questiontext' => $questiontext,
            'answers' => $answers,
            'defaultmark' => $defaultmark,
            'unitgradingtype' => $unitgradingtype,
            'unitpenalty' => $unitpenalty,
            'showunits' => $showunits,
            'unitsleft' => $unitsleft,
            'units' => $units,
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
        if (empty($params['answers'])) {
            return [
                'questionid' => 0,
                'questionbankentryid' => 0,
                'name' => '',
                'success' => false,
                'message' => 'Numerical questions require at least 1 answer',
            ];
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
        $question->qtype = 'numerical';
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

        // Create numerical options.
        $options = new \stdClass();
        $options->question = $questionid;
        $options->unitgradingtype = $params['unitgradingtype'];
        $options->unitpenalty = $params['unitpenalty'];
        $options->showunits = $params['showunits'];
        $options->unitsleft = $params['unitsleft'];

        $DB->insert_record('question_numerical_options', $options);

        // Create answers and numerical records.
        foreach ($params['answers'] as $answerdata) {
            $answer = new \stdClass();
            $answer->question = $questionid;
            $answer->answer = $answerdata['answer'];
            $answer->answerformat = FORMAT_MOODLE;
            $answer->fraction = $answerdata['fraction'] ?? 1.0;
            $answer->feedback = $answerdata['feedback'] ?? '';
            $answer->feedbackformat = FORMAT_HTML;

            $answerid = $DB->insert_record('question_answers', $answer);

            // Create numerical record for tolerance.
            $numerical = new \stdClass();
            $numerical->question = $questionid;
            $numerical->answer = $answerid;
            $numerical->tolerance = $answerdata['tolerance'] ?? 0;

            $DB->insert_record('question_numerical', $numerical);
        }

        // Create units if provided.
        foreach ($params['units'] as $unitdata) {
            $unit = new \stdClass();
            $unit->question = $questionid;
            $unit->unit = $unitdata['unit'];
            $unit->multiplier = $unitdata['multiplier'] ?? 1.0;

            $DB->insert_record('question_numerical_units', $unit);
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
            'message' => 'Numerical question created successfully',
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
