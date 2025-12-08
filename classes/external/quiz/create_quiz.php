<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_activity_utils\helper;

class create_quiz extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'Quiz name'),
            'intro' => new external_value(PARAM_RAW, 'Quiz description', VALUE_DEFAULT, ''),
            'section' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, 0),
            'idnumber' => new external_value(PARAM_RAW, 'ID number', VALUE_DEFAULT, ''),

            // Timing settings
            'timeopen' => new external_value(PARAM_INT, 'Quiz open time (timestamp)', VALUE_DEFAULT, 0),
            'timeclose' => new external_value(PARAM_INT, 'Quiz close time (timestamp)', VALUE_DEFAULT, 0),
            'timelimit' => new external_value(PARAM_INT, 'Time limit in seconds (0 = no limit)', VALUE_DEFAULT, 0),
            'overduehandling' => new external_value(PARAM_ALPHA, 'Overdue handling: autosubmit, graceperiod, autoabandon', VALUE_DEFAULT, 'autosubmit'),
            'graceperiod' => new external_value(PARAM_INT, 'Grace period in seconds', VALUE_DEFAULT, 0),

            // Grade settings
            'grademax' => new external_value(PARAM_INT, 'Maximum grade', VALUE_DEFAULT, 10),
            'gradepass' => new external_value(PARAM_FLOAT, 'Grade to pass', VALUE_DEFAULT, 0),
            'grademethod' => new external_value(PARAM_INT, 'Grading method (1=highest, 2=average, 3=first, 4=last)', VALUE_DEFAULT, 1),
            'gradecategory' => new external_value(PARAM_INT, 'Grade category ID', VALUE_DEFAULT, 0),

            // Layout and display
            'questionsperpage' => new external_value(PARAM_INT, 'Questions per page (0 = all on one page)', VALUE_DEFAULT, 1),
            'navmethod' => new external_value(PARAM_ALPHA, 'Navigation method: free or seq', VALUE_DEFAULT, 'free'),
            'shuffleanswers' => new external_value(PARAM_BOOL, 'Shuffle answers within questions', VALUE_DEFAULT, 1),
            'preferredbehaviour' => new external_value(PARAM_ALPHA, 'Question behaviour', VALUE_DEFAULT, 'deferredfeedback'),

            // Question behaviour options: deferredfeedback, adaptive, adaptivenopenalty, immediatefeedback,
            // immediateadaptive, interactive, interactivecountback

            // Attempt restrictions
            'attempts' => new external_value(PARAM_INT, 'Number of allowed attempts (0 = unlimited)', VALUE_DEFAULT, 0),
            'attemptonlast' => new external_value(PARAM_BOOL, 'Each attempt builds on last', VALUE_DEFAULT, 0),

            // Review options (when to show what)
            'reviewattempt' => new external_value(PARAM_INT, 'Review attempt bitmask', VALUE_DEFAULT, 69904),
            'reviewcorrectness' => new external_value(PARAM_INT, 'Review correctness bitmask', VALUE_DEFAULT, 69904),
            'reviewmarks' => new external_value(PARAM_INT, 'Review marks bitmask', VALUE_DEFAULT, 69904),
            'reviewspecificfeedback' => new external_value(PARAM_INT, 'Review specific feedback bitmask', VALUE_DEFAULT, 69904),
            'reviewgeneralfeedback' => new external_value(PARAM_INT, 'Review general feedback bitmask', VALUE_DEFAULT, 69904),
            'reviewrightanswer' => new external_value(PARAM_INT, 'Review right answer bitmask', VALUE_DEFAULT, 69904),
            'reviewoverallfeedback' => new external_value(PARAM_INT, 'Review overall feedback bitmask', VALUE_DEFAULT, 69904),

            // Display options
            'showuserpicture' => new external_value(PARAM_INT, 'Show user picture (0=no, 1=small, 2=large)', VALUE_DEFAULT, 0),
            'showblocks' => new external_value(PARAM_BOOL, 'Show blocks during quiz attempts', VALUE_DEFAULT, 0),
            'decimalpoints' => new external_value(PARAM_INT, 'Decimal places in grades', VALUE_DEFAULT, 2),

            // Security
            'password' => new external_value(PARAM_RAW, 'Quiz password', VALUE_DEFAULT, ''),
            'subnet' => new external_value(PARAM_TEXT, 'Subnet restriction', VALUE_DEFAULT, ''),
            'delay1' => new external_value(PARAM_INT, 'Delay between first and second attempt (seconds)', VALUE_DEFAULT, 0),
            'delay2' => new external_value(PARAM_INT, 'Delay between later attempts (seconds)', VALUE_DEFAULT, 0),
            'browsersecurity' => new external_value(PARAM_TEXT, 'Browser security: - (none), securewindow, safebrowser, securewindowwithjavascript', VALUE_DEFAULT, '-'),

            // Extra restrictions
            'canredoquestions' => new external_value(PARAM_BOOL, 'Allow redo within an attempt', VALUE_DEFAULT, 0),
            'completionattemptsexhausted' => new external_value(PARAM_BOOL, 'Require all attempts to be completed', VALUE_DEFAULT, 0),
            'completionminattempts' => new external_value(PARAM_INT, 'Require minimum attempts', VALUE_DEFAULT, 0),
            'completionpass' => new external_value(PARAM_BOOL, 'Require passing grade', VALUE_DEFAULT, 0),

            // Availability
            'visible' => new external_value(PARAM_BOOL, 'Visible on course page', VALUE_DEFAULT, 1),
            'availability' => new external_value(PARAM_RAW, 'Availability JSON', VALUE_DEFAULT, null),
        ]);
    }

    public static function execute(
        int $courseid,
        string $name,
        string $intro = '',
        int $section = 0,
        string $idnumber = '',
        int $timeopen = 0,
        int $timeclose = 0,
        int $timelimit = 0,
        string $overduehandling = 'autosubmit',
        int $graceperiod = 0,
        int $grademax = 10,
        float $gradepass = 0,
        int $grademethod = 1,
        int $gradecategory = 0,
        int $questionsperpage = 1,
        string $navmethod = 'free',
        bool $shuffleanswers = true,
        string $preferredbehaviour = 'deferredfeedback',
        int $attempts = 0,
        bool $attemptonlast = false,
        int $reviewattempt = 69904,
        int $reviewcorrectness = 69904,
        int $reviewmarks = 69904,
        int $reviewspecificfeedback = 69904,
        int $reviewgeneralfeedback = 69904,
        int $reviewrightanswer = 69904,
        int $reviewoverallfeedback = 69904,
        int $showuserpicture = 0,
        bool $showblocks = false,
        int $decimalpoints = 2,
        string $password = '',
        string $subnet = '',
        int $delay1 = 0,
        int $delay2 = 0,
        string $browsersecurity = '-',
        bool $canredoquestions = false,
        bool $completionattemptsexhausted = false,
        int $completionminattempts = 0,
        bool $completionpass = false,
        bool $visible = true,
        $availability = null
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/lib/gradelib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'name' => $name,
            'intro' => $intro,
            'section' => $section,
            'idnumber' => $idnumber,
            'timeopen' => $timeopen,
            'timeclose' => $timeclose,
            'timelimit' => $timelimit,
            'overduehandling' => $overduehandling,
            'graceperiod' => $graceperiod,
            'grademax' => $grademax,
            'gradepass' => $gradepass,
            'grademethod' => $grademethod,
            'gradecategory' => $gradecategory,
            'questionsperpage' => $questionsperpage,
            'navmethod' => $navmethod,
            'shuffleanswers' => $shuffleanswers,
            'preferredbehaviour' => $preferredbehaviour,
            'attempts' => $attempts,
            'attemptonlast' => $attemptonlast,
            'reviewattempt' => $reviewattempt,
            'reviewcorrectness' => $reviewcorrectness,
            'reviewmarks' => $reviewmarks,
            'reviewspecificfeedback' => $reviewspecificfeedback,
            'reviewgeneralfeedback' => $reviewgeneralfeedback,
            'reviewrightanswer' => $reviewrightanswer,
            'reviewoverallfeedback' => $reviewoverallfeedback,
            'showuserpicture' => $showuserpicture,
            'showblocks' => $showblocks,
            'decimalpoints' => $decimalpoints,
            'password' => $password,
            'subnet' => $subnet,
            'delay1' => $delay1,
            'delay2' => $delay2,
            'browsersecurity' => $browsersecurity,
            'canredoquestions' => $canredoquestions,
            'completionattemptsexhausted' => $completionattemptsexhausted,
            'completionminattempts' => $completionminattempts,
            'completionpass' => $completionpass,
            'visible' => $visible,
            'availability' => $availability,
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:createquiz', $context);
        require_capability('mod/quiz:addinstance', $context);

        // Create quiz record
        $quiz = new \stdClass();
        $quiz->course = $params['courseid'];
        $quiz->name = $params['name'];
        $quiz->intro = $params['intro'];
        $quiz->introformat = FORMAT_HTML;
        $quiz->timeopen = $params['timeopen'];
        $quiz->timeclose = $params['timeclose'];
        $quiz->timelimit = $params['timelimit'];
        $quiz->overduehandling = $params['overduehandling'];
        $quiz->graceperiod = $params['graceperiod'];
        $quiz->preferredbehaviour = $params['preferredbehaviour'];
        $quiz->canredoquestions = $params['canredoquestions'] ? 1 : 0;
        $quiz->attempts = $params['attempts'];
        $quiz->attemptonlast = $params['attemptonlast'] ? 1 : 0;
        $quiz->grademethod = $params['grademethod'];
        $quiz->decimalpoints = $params['decimalpoints'];
        $quiz->questiondecimalpoints = -1;
        $quiz->reviewattempt = $params['reviewattempt'];
        $quiz->reviewcorrectness = $params['reviewcorrectness'];
        $quiz->reviewmarks = $params['reviewmarks'];
        $quiz->reviewspecificfeedback = $params['reviewspecificfeedback'];
        $quiz->reviewgeneralfeedback = $params['reviewgeneralfeedback'];
        $quiz->reviewrightanswer = $params['reviewrightanswer'];
        $quiz->reviewoverallfeedback = $params['reviewoverallfeedback'];
        $quiz->questionsperpage = $params['questionsperpage'];
        $quiz->navmethod = $params['navmethod'];
        $quiz->shuffleanswers = $params['shuffleanswers'] ? 1 : 0;
        $quiz->sumgrades = 0;
        $quiz->grade = $params['grademax'];
        $quiz->timecreated = time();
        $quiz->timemodified = time();
        $quiz->password = $params['password'];
        $quiz->subnet = $params['subnet'];
        $quiz->browsersecurity = $params['browsersecurity'];
        $quiz->delay1 = $params['delay1'];
        $quiz->delay2 = $params['delay2'];
        $quiz->showuserpicture = $params['showuserpicture'];
        $quiz->showblocks = $params['showblocks'] ? 1 : 0;
        $quiz->completionattemptsexhausted = $params['completionattemptsexhausted'] ? 1 : 0;
        $quiz->completionminattempts = $params['completionminattempts'];
        $quiz->completionpass = $params['completionpass'] ? 1 : 0;
        $quiz->allowofflineattempts = 0;

        $quizid = $DB->insert_record('quiz', $quiz);

        // Create course module
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST);

        $cm = new \stdClass();
        $cm->course = $params['courseid'];
        $cm->module = $moduleid;
        $cm->instance = $quizid;
        $cm->section = $params['section'];
        $cm->idnumber = $params['idnumber'];
        $cm->added = time();
        $cm->score = 0;
        $cm->indent = 0;
        $cm->visible = $params['visible'] ? 1 : 0;
        $cm->visibleoncoursepage = 1;
        $cm->visibleold = $params['visible'] ? 1 : 0;
        $cm->groupmode = 0;
        $cm->groupingid = 0;
        $cm->completion = ($params['completionattemptsexhausted'] || $params['completionminattempts'] > 0 || $params['completionpass']) ? 2 : 0;
        $cm->completionview = 0;
        $cm->completionexpected = 0;
        $cm->completionpassgrade = $params['completionpass'] ? 1 : 0;
        $cm->showdescription = 0;
        $cm->availability = $params['availability'];
        $cm->deletioninprogress = 0;
        $cm->downloadcontent = 1;
        $cm->lang = '';
        $cm->completiongradeitemnumber = null;

        $cmid = $DB->insert_record('course_modules', $cm);

        // Add module to section
        helper::add_module_to_section($params['courseid'], $params['section'], $cmid, 1);

        // Initialize grade item
        rebuild_course_cache($params['courseid'], true);

        quiz_grade_item_update($quiz);

        // Update grade to pass if specified
        if ($params['gradepass'] > 0) {
            $gradeitem = \grade_item::fetch([
                'courseid' => $params['courseid'],
                'itemtype' => 'mod',
                'itemmodule' => 'quiz',
                'iteminstance' => $quizid
            ]);
            if ($gradeitem) {
                $gradeitem->gradepass = $params['gradepass'];
                $gradeitem->update();
            }
        }

        return [
            'id' => $quizid,
            'coursemoduleid' => $cmid,
            'name' => $params['name'],
            'success' => true,
            'message' => 'Quiz created successfully'
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
