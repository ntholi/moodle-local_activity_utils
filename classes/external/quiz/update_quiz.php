<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class update_quiz extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
            'name' => new external_value(PARAM_TEXT, 'Quiz name', VALUE_DEFAULT, null),
            'intro' => new external_value(PARAM_RAW, 'Quiz description', VALUE_DEFAULT, null),
            'idnumber' => new external_value(PARAM_RAW, 'ID number', VALUE_DEFAULT, null),

            // Timing settings
            'timeopen' => new external_value(PARAM_INT, 'Quiz open time (timestamp)', VALUE_DEFAULT, null),
            'timeclose' => new external_value(PARAM_INT, 'Quiz close time (timestamp)', VALUE_DEFAULT, null),
            'timelimit' => new external_value(PARAM_INT, 'Time limit in seconds (0 = no limit)', VALUE_DEFAULT, null),
            'overduehandling' => new external_value(PARAM_ALPHA, 'Overdue handling: autosubmit, graceperiod, autoabandon', VALUE_DEFAULT, null),
            'graceperiod' => new external_value(PARAM_INT, 'Grace period in seconds', VALUE_DEFAULT, null),

            // Grade settings
            'grademax' => new external_value(PARAM_INT, 'Maximum grade', VALUE_DEFAULT, null),
            'gradepass' => new external_value(PARAM_FLOAT, 'Grade to pass', VALUE_DEFAULT, null),
            'grademethod' => new external_value(PARAM_INT, 'Grading method (1=highest, 2=average, 3=first, 4=last)', VALUE_DEFAULT, null),

            // Layout and display
            'questionsperpage' => new external_value(PARAM_INT, 'Questions per page (0 = all on one page)', VALUE_DEFAULT, null),
            'navmethod' => new external_value(PARAM_ALPHA, 'Navigation method: free or seq', VALUE_DEFAULT, null),
            'shuffleanswers' => new external_value(PARAM_BOOL, 'Shuffle answers within questions', VALUE_DEFAULT, null),
            'preferredbehaviour' => new external_value(PARAM_ALPHA, 'Question behaviour', VALUE_DEFAULT, null),

            // Attempt restrictions
            'attempts' => new external_value(PARAM_INT, 'Number of allowed attempts (0 = unlimited)', VALUE_DEFAULT, null),
            'attemptonlast' => new external_value(PARAM_BOOL, 'Each attempt builds on last', VALUE_DEFAULT, null),

            // Review options
            'reviewattempt' => new external_value(PARAM_INT, 'Review attempt bitmask', VALUE_DEFAULT, null),
            'reviewcorrectness' => new external_value(PARAM_INT, 'Review correctness bitmask', VALUE_DEFAULT, null),
            'reviewmarks' => new external_value(PARAM_INT, 'Review marks bitmask', VALUE_DEFAULT, null),
            'reviewspecificfeedback' => new external_value(PARAM_INT, 'Review specific feedback bitmask', VALUE_DEFAULT, null),
            'reviewgeneralfeedback' => new external_value(PARAM_INT, 'Review general feedback bitmask', VALUE_DEFAULT, null),
            'reviewrightanswer' => new external_value(PARAM_INT, 'Review right answer bitmask', VALUE_DEFAULT, null),
            'reviewoverallfeedback' => new external_value(PARAM_INT, 'Review overall feedback bitmask', VALUE_DEFAULT, null),

            // Display options
            'showuserpicture' => new external_value(PARAM_INT, 'Show user picture (0=no, 1=small, 2=large)', VALUE_DEFAULT, null),
            'showblocks' => new external_value(PARAM_BOOL, 'Show blocks during quiz attempts', VALUE_DEFAULT, null),
            'decimalpoints' => new external_value(PARAM_INT, 'Decimal places in grades', VALUE_DEFAULT, null),

            // Security
            'password' => new external_value(PARAM_RAW, 'Quiz password', VALUE_DEFAULT, null),
            'subnet' => new external_value(PARAM_TEXT, 'Subnet restriction', VALUE_DEFAULT, null),
            'delay1' => new external_value(PARAM_INT, 'Delay between first and second attempt (seconds)', VALUE_DEFAULT, null),
            'delay2' => new external_value(PARAM_INT, 'Delay between later attempts (seconds)', VALUE_DEFAULT, null),
            'browsersecurity' => new external_value(PARAM_TEXT, 'Browser security: - (none), securewindow, safebrowser, securewindowwithjavascript', VALUE_DEFAULT, null),

            // Extra restrictions
            'canredoquestions' => new external_value(PARAM_BOOL, 'Allow redo within an attempt', VALUE_DEFAULT, null),
            'completionattemptsexhausted' => new external_value(PARAM_BOOL, 'Require all attempts to be completed', VALUE_DEFAULT, null),
            'completionminattempts' => new external_value(PARAM_INT, 'Require minimum attempts', VALUE_DEFAULT, null),
            'completionpass' => new external_value(PARAM_BOOL, 'Require passing grade', VALUE_DEFAULT, null),

            // Availability
            'visible' => new external_value(PARAM_BOOL, 'Visible on course page', VALUE_DEFAULT, null),
            'availability' => new external_value(PARAM_RAW, 'Availability JSON', VALUE_DEFAULT, null),
        ]);
    }

    public static function execute(
        int $quizid,
        ?string $name = null,
        ?string $intro = null,
        ?string $idnumber = null,
        ?int $timeopen = null,
        ?int $timeclose = null,
        ?int $timelimit = null,
        ?string $overduehandling = null,
        ?int $graceperiod = null,
        ?int $grademax = null,
        ?float $gradepass = null,
        ?int $grademethod = null,
        ?int $questionsperpage = null,
        ?string $navmethod = null,
        ?bool $shuffleanswers = null,
        ?string $preferredbehaviour = null,
        ?int $attempts = null,
        ?bool $attemptonlast = null,
        ?int $reviewattempt = null,
        ?int $reviewcorrectness = null,
        ?int $reviewmarks = null,
        ?int $reviewspecificfeedback = null,
        ?int $reviewgeneralfeedback = null,
        ?int $reviewrightanswer = null,
        ?int $reviewoverallfeedback = null,
        ?int $showuserpicture = null,
        ?bool $showblocks = null,
        ?int $decimalpoints = null,
        ?string $password = null,
        ?string $subnet = null,
        ?int $delay1 = null,
        ?int $delay2 = null,
        ?string $browsersecurity = null,
        ?bool $canredoquestions = null,
        ?bool $completionattemptsexhausted = null,
        ?int $completionminattempts = null,
        ?bool $completionpass = null,
        ?bool $visible = null,
        $availability = null
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'quizid', 'name', 'intro', 'idnumber', 'timeopen', 'timeclose', 'timelimit',
            'overduehandling', 'graceperiod', 'grademax', 'gradepass', 'grademethod',
            'questionsperpage', 'navmethod', 'shuffleanswers', 'preferredbehaviour',
            'attempts', 'attemptonlast', 'reviewattempt', 'reviewcorrectness', 'reviewmarks',
            'reviewspecificfeedback', 'reviewgeneralfeedback', 'reviewrightanswer',
            'reviewoverallfeedback', 'showuserpicture', 'showblocks', 'decimalpoints',
            'password', 'subnet', 'delay1', 'delay2', 'browsersecurity', 'canredoquestions',
            'completionattemptsexhausted', 'completionminattempts', 'completionpass',
            'visible', 'availability'
        ));

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('local/activity_utils:updatequiz', $context);
        require_capability('mod/quiz:manage', $context);

        if ($params['name'] !== null) {
            $quiz->name = $params['name'];
        }
        if ($params['intro'] !== null) {
            $quiz->intro = $params['intro'];
        }
        if ($params['timeopen'] !== null) {
            $quiz->timeopen = $params['timeopen'];
        }
        if ($params['timeclose'] !== null) {
            $quiz->timeclose = $params['timeclose'];
        }
        if ($params['timelimit'] !== null) {
            $quiz->timelimit = $params['timelimit'];
        }
        if ($params['overduehandling'] !== null) {
            $quiz->overduehandling = $params['overduehandling'];
        }
        if ($params['graceperiod'] !== null) {
            $quiz->graceperiod = $params['graceperiod'];
        }
        if ($params['preferredbehaviour'] !== null) {
            $quiz->preferredbehaviour = $params['preferredbehaviour'];
        }
        if ($params['canredoquestions'] !== null) {
            $quiz->canredoquestions = $params['canredoquestions'] ? 1 : 0;
        }
        if ($params['attempts'] !== null) {
            $quiz->attempts = $params['attempts'];
        }
        if ($params['attemptonlast'] !== null) {
            $quiz->attemptonlast = $params['attemptonlast'] ? 1 : 0;
        }
        if ($params['grademethod'] !== null) {
            $quiz->grademethod = $params['grademethod'];
        }
        if ($params['decimalpoints'] !== null) {
            $quiz->decimalpoints = $params['decimalpoints'];
        }
        if ($params['reviewattempt'] !== null) {
            $quiz->reviewattempt = $params['reviewattempt'];
        }
        if ($params['reviewcorrectness'] !== null) {
            $quiz->reviewcorrectness = $params['reviewcorrectness'];
        }
        if ($params['reviewmarks'] !== null) {
            $quiz->reviewmarks = $params['reviewmarks'];
        }
        if ($params['reviewspecificfeedback'] !== null) {
            $quiz->reviewspecificfeedback = $params['reviewspecificfeedback'];
        }
        if ($params['reviewgeneralfeedback'] !== null) {
            $quiz->reviewgeneralfeedback = $params['reviewgeneralfeedback'];
        }
        if ($params['reviewrightanswer'] !== null) {
            $quiz->reviewrightanswer = $params['reviewrightanswer'];
        }
        if ($params['reviewoverallfeedback'] !== null) {
            $quiz->reviewoverallfeedback = $params['reviewoverallfeedback'];
        }
        if ($params['questionsperpage'] !== null) {
            $quiz->questionsperpage = $params['questionsperpage'];
        }
        if ($params['navmethod'] !== null) {
            $quiz->navmethod = $params['navmethod'];
        }
        if ($params['shuffleanswers'] !== null) {
            $quiz->shuffleanswers = $params['shuffleanswers'] ? 1 : 0;
        }
        if ($params['grademax'] !== null) {
            $quiz->grade = $params['grademax'];
        }
        if ($params['password'] !== null) {
            $quiz->password = $params['password'];
        }
        if ($params['subnet'] !== null) {
            $quiz->subnet = $params['subnet'];
        }
        if ($params['browsersecurity'] !== null) {
            $quiz->browsersecurity = $params['browsersecurity'];
        }
        if ($params['delay1'] !== null) {
            $quiz->delay1 = $params['delay1'];
        }
        if ($params['delay2'] !== null) {
            $quiz->delay2 = $params['delay2'];
        }
        if ($params['showuserpicture'] !== null) {
            $quiz->showuserpicture = $params['showuserpicture'];
        }
        if ($params['showblocks'] !== null) {
            $quiz->showblocks = $params['showblocks'] ? 1 : 0;
        }
        if ($params['completionattemptsexhausted'] !== null) {
            $quiz->completionattemptsexhausted = $params['completionattemptsexhausted'] ? 1 : 0;
        }
        if ($params['completionminattempts'] !== null) {
            $quiz->completionminattempts = $params['completionminattempts'];
        }
        if ($params['completionpass'] !== null) {
            $quiz->completionpass = $params['completionpass'] ? 1 : 0;
        }

        $quiz->timemodified = time();
        $DB->update_record('quiz', $quiz);

        $cmupdated = false;
        if ($params['idnumber'] !== null && $cm->idnumber !== $params['idnumber']) {
            $cm->idnumber = $params['idnumber'];
            $cmupdated = true;
        }
        if ($params['visible'] !== null && $cm->visible !== ($params['visible'] ? 1 : 0)) {
            $cm->visible = $params['visible'] ? 1 : 0;
            $cm->visibleold = $cm->visible;
            $cmupdated = true;
        }
        if ($params['availability'] !== null && $cm->availability !== $params['availability']) {
            $cm->availability = $params['availability'];
            $cmupdated = true;
        }
        if ($params['completionpass'] !== null || $params['completionattemptsexhausted'] !== null || $params['completionminattempts'] !== null) {
            $completion = ($quiz->completionattemptsexhausted || $quiz->completionminattempts > 0 || $quiz->completionpass) ? 2 : 0;
            if ($cm->completion !== $completion) {
                $cm->completion = $completion;
                $cmupdated = true;
            }
            if ($quiz->completionpass && !$cm->completionpassgrade) {
                $cm->completionpassgrade = 1;
                $cmupdated = true;
            }
        }

        if ($cmupdated) {
            $DB->update_record('course_modules', $cm);
        }

        quiz_grade_item_update($quiz);

        if ($params['gradepass'] !== null) {
            $gradeitem = \grade_item::fetch([
                'courseid' => $course->id,
                'itemtype' => 'mod',
                'itemmodule' => 'quiz',
                'iteminstance' => $quiz->id
            ]);
            if ($gradeitem) {
                $gradeitem->gradepass = $params['gradepass'];
                $gradeitem->update();
            }
        }

        rebuild_course_cache($course->id, true);

        return [
            'id' => $quiz->id,
            'coursemoduleid' => $cm->id,
            'name' => $quiz->name,
            'success' => true,
            'message' => 'Quiz updated successfully'
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Quiz ID'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Quiz name'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
