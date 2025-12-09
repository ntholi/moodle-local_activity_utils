<?php
namespace local_activity_utils\external\question;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * Create a short answer question.
 */
class create_shortanswer_question extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Question category ID'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'questiontext' => new external_value(PARAM_RAW, 'Question text (HTML)'),
            'answers' => new external_value(PARAM_RAW, 'JSON array of accepted answers: [{"text":"Answer","fraction":1.0,"feedback":""}]'),
            'defaultmark' => new external_value(PARAM_FLOAT, 'Default mark (points)', VALUE_DEFAULT, 1.0),
            'usecase' => new external_value(PARAM_INT, 'Case sensitive matching: 1 = yes, 0 = no', VALUE_DEFAULT, 0),
            'generalfeedback' => new external_value(PARAM_RAW, 'General feedback shown after attempt', VALUE_DEFAULT, ''),
            'idnumber' => new external_value(PARAM_RAW, 'ID number for the question', VALUE_DEFAULT, ''),
            'tags' => new external_value(PARAM_RAW, 'JSON array of tag names: ["tag1","tag2"]', VALUE_DEFAULT, '[]'),
        ]);
    }

    public static function execute(
        int $categoryid,
        string $name,
        string $questiontext,
        string $answers,
        float $defaultmark = 1.0,
        int $usecase = 0,
        string $generalfeedback = '',
        string $idnumber = '',
        string $tags = '[]'
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
            'usecase' => $usecase,
            'generalfeedback' => $generalfeedback,
            'idnumber' => $idnumber,
            'tags' => $tags,
        ]);

        // Decode JSON arrays.
        $answersarray = json_decode($params['answers'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($answersarray)) {
            return [
                'questionid' => 0,
                'questionbankentryid' => 0,
                'name' => '',
                'success' => false,
                'message' => 'Invalid answers format. Expected JSON array.',
            ];
        }

        $tagsarray = json_decode($params['tags'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $tagsarray = [];
        }

        // Validate category exists.
        $category = $DB->get_record('question_categories', ['id' => $params['categoryid']], '*', MUST_EXIST);
        $context = \context::instance_by_id($category->contextid);

        self::validate_context($context);
        require_capability('local/activity_utils:createquestion', $context);
        require_capability('moodle/question:add', $context);

        // Validate answers.
        if (empty($answersarray)) {
            return [
                'questionid' => 0,
                'questionbankentryid' => 0,
                'name' => '',
                'success' => false,
                'message' => 'Short answer questions require at least 1 answer',
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
        $question->qtype = 'shortanswer';
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

        // Create shortanswer options.
        $options = new \stdClass();
        $options->questionid = $questionid;
        $options->usecase = $params['usecase'] ? 1 : 0;

        $DB->insert_record('qtype_shortanswer_options', $options);

        // Create answers.
        foreach ($answersarray as $answerdata) {
            $answer = new \stdClass();
            $answer->question = $questionid;
            $answer->answer = $answerdata['text'];
            $answer->answerformat = FORMAT_MOODLE;
            $answer->fraction = $answerdata['fraction'] ?? 1.0;
            $answer->feedback = $answerdata['feedback'] ?? '';
            $answer->feedbackformat = FORMAT_HTML;

            $DB->insert_record('question_answers', $answer);
        }

        // Add tags if provided.
        if (!empty($tagsarray)) {
            \core_tag_tag::set_item_tags('core_question', 'question', $questionid, $context, $tagsarray);
        }

        return [
            'questionid' => (int)$questionid,
            'questionbankentryid' => (int)$qbeid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'Short answer question created successfully',
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
