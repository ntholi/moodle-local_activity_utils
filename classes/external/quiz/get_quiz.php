<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

class get_quiz extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
        ]);
    }

    public static function execute(int $quizid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['quizid' => $quizid]);

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:viewquiz', $context);

        // Get quiz slots (questions)
        $slots = $DB->get_records('quiz_slots', ['quizid' => $params['quizid']], 'slot ASC');
        $questions = [];
        foreach ($slots as $slot) {
            $question = $DB->get_record('question', ['id' => $slot->questionid], 'id,name,qtype');
            $questions[] = [
                'slot' => $slot->slot,
                'questionid' => $slot->questionid,
                'questionname' => $question ? $question->name : 'Unknown',
                'questiontype' => $question ? $question->qtype : 'unknown',
                'page' => $slot->page,
                'maxmark' => $slot->maxmark,
                'requireprevious' => $slot->requireprevious
            ];
        }

        // Get feedback
        $feedbacks = $DB->get_records('quiz_feedback', ['quizid' => $params['quizid']], 'mingrade ASC');
        $feedbacklist = [];
        foreach ($feedbacks as $feedback) {
            $feedbacklist[] = [
                'id' => $feedback->id,
                'feedbacktext' => $feedback->feedbacktext,
                'mingrade' => $feedback->mingrade * 100,
                'maxgrade' => $feedback->maxgrade * 100
            ];
        }

        return [
            'id' => $quiz->id,
            'coursemoduleid' => $cm->id,
            'name' => $quiz->name,
            'intro' => $quiz->intro,
            'timeopen' => $quiz->timeopen,
            'timeclose' => $quiz->timeclose,
            'timelimit' => $quiz->timelimit,
            'overduehandling' => $quiz->overduehandling,
            'graceperiod' => $quiz->graceperiod,
            'preferredbehaviour' => $quiz->preferredbehaviour,
            'attempts' => $quiz->attempts,
            'grademethod' => $quiz->grademethod,
            'decimalpoints' => $quiz->decimalpoints,
            'questionsperpage' => $quiz->questionsperpage,
            'navmethod' => $quiz->navmethod,
            'shuffleanswers' => $quiz->shuffleanswers,
            'sumgrades' => $quiz->sumgrades,
            'grade' => $quiz->grade,
            'password' => $quiz->password,
            'subnet' => $quiz->subnet,
            'browsersecurity' => $quiz->browsersecurity,
            'delay1' => $quiz->delay1,
            'delay2' => $quiz->delay2,
            'showuserpicture' => $quiz->showuserpicture,
            'showblocks' => $quiz->showblocks,
            'visible' => $cm->visible,
            'questions' => $questions,
            'feedbacks' => $feedbacklist
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Quiz ID'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Quiz name'),
            'intro' => new external_value(PARAM_RAW, 'Quiz description'),
            'timeopen' => new external_value(PARAM_INT, 'Quiz open time'),
            'timeclose' => new external_value(PARAM_INT, 'Quiz close time'),
            'timelimit' => new external_value(PARAM_INT, 'Time limit'),
            'overduehandling' => new external_value(PARAM_TEXT, 'Overdue handling'),
            'graceperiod' => new external_value(PARAM_INT, 'Grace period'),
            'preferredbehaviour' => new external_value(PARAM_TEXT, 'Preferred behaviour'),
            'attempts' => new external_value(PARAM_INT, 'Number of attempts allowed'),
            'grademethod' => new external_value(PARAM_INT, 'Grading method'),
            'decimalpoints' => new external_value(PARAM_INT, 'Decimal points'),
            'questionsperpage' => new external_value(PARAM_INT, 'Questions per page'),
            'navmethod' => new external_value(PARAM_TEXT, 'Navigation method'),
            'shuffleanswers' => new external_value(PARAM_INT, 'Shuffle answers'),
            'sumgrades' => new external_value(PARAM_FLOAT, 'Sum of grades'),
            'grade' => new external_value(PARAM_FLOAT, 'Grade'),
            'password' => new external_value(PARAM_RAW, 'Password'),
            'subnet' => new external_value(PARAM_TEXT, 'Subnet'),
            'browsersecurity' => new external_value(PARAM_TEXT, 'Browser security'),
            'delay1' => new external_value(PARAM_INT, 'Delay 1'),
            'delay2' => new external_value(PARAM_INT, 'Delay 2'),
            'showuserpicture' => new external_value(PARAM_INT, 'Show user picture'),
            'showblocks' => new external_value(PARAM_INT, 'Show blocks'),
            'visible' => new external_value(PARAM_INT, 'Visible'),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'slot' => new external_value(PARAM_INT, 'Slot number'),
                    'questionid' => new external_value(PARAM_INT, 'Question ID'),
                    'questionname' => new external_value(PARAM_TEXT, 'Question name'),
                    'questiontype' => new external_value(PARAM_TEXT, 'Question type'),
                    'page' => new external_value(PARAM_INT, 'Page number'),
                    'maxmark' => new external_value(PARAM_FLOAT, 'Maximum mark'),
                    'requireprevious' => new external_value(PARAM_INT, 'Require previous')
                ])
            ),
            'feedbacks' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Feedback ID'),
                    'feedbacktext' => new external_value(PARAM_RAW, 'Feedback text'),
                    'mingrade' => new external_value(PARAM_FLOAT, 'Min grade %'),
                    'maxgrade' => new external_value(PARAM_FLOAT, 'Max grade %')
                ])
            )
        ]);
    }
}
