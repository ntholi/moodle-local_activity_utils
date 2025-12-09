<?php
namespace local_activity_utils\external\quiz;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class remove_question_from_quiz extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz instance ID'),
            'slot' => new external_value(PARAM_INT, 'Slot number to remove'),
        ]);
    }

    public static function execute(int $quizid, int $slot): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
            'slot' => $slot,
        ]);

        
        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);

        
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, 0, false, MUST_EXIST);

        
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('local/activity_utils:managequizquestions', $context);
        require_capability('mod/quiz:manage', $context);

        
        $slotrecord = $DB->get_record('quiz_slots', [
            'quizid' => $params['quizid'],
            'slot' => $params['slot'],
        ]);

        if (!$slotrecord) {
            return [
                'success' => false,
                'message' => 'Slot not found in quiz',
            ];
        }

        
        $sql = "SELECT q.name
                  FROM {question_references} qr
                  JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qr.component = 'mod_quiz'
                   AND qr.questionarea = 'slot'
                   AND qr.itemid = ?
                   AND qv.version = (
                       SELECT MAX(qv2.version)
                         FROM {question_versions} qv2
                        WHERE qv2.questionbankentryid = qbe.id
                          AND qv2.status = 'ready'
                   )";
        $questionname = $DB->get_field_sql($sql, [$slotrecord->id]);
        $questionname = $questionname ?: 'Unknown question';

        
        $sections = $DB->get_records('quiz_sections', ['quizid' => $params['quizid']], 'firstslot ASC');
        $sectioncount = count($sections);

        
        if ($sectioncount > 1) {
            foreach ($sections as $section) {
                if ($section->firstslot == $params['slot']) {
                    
                    
                    $maxslot = $DB->get_field_sql(
                        'SELECT MAX(slot) FROM {quiz_slots} WHERE quizid = ?',
                        [$params['quizid']]
                    );

                    if ($params['slot'] == $maxslot) {
                        
                        
                        $slotsinsection = $DB->count_records_select(
                            'quiz_slots',
                            'quizid = ? AND slot >= ?',
                            [$params['quizid'], $params['slot']]
                        );

                        if ($slotsinsection == 1 && $sectioncount > 1) {
                            
                            $DB->delete_records('quiz_sections', ['id' => $section->id]);
                        }
                    }
                    break;
                }
            }
        }

        
        $DB->delete_records('question_references', [
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => $slotrecord->id,
        ]);

        
        $DB->delete_records('quiz_slots', ['id' => $slotrecord->id]);

        
        $sql = "UPDATE {quiz_slots}
                   SET slot = slot - 1
                 WHERE quizid = ? AND slot > ?";
        $DB->execute($sql, [$params['quizid'], $params['slot']]);

        
        $sql = "UPDATE {quiz_sections}
                   SET firstslot = firstslot - 1
                 WHERE quizid = ? AND firstslot > ?";
        $DB->execute($sql, [$params['quizid'], $params['slot']]);

        
        $sumgrades = $DB->get_field_sql(
            'SELECT COALESCE(SUM(maxmark), 0) FROM {quiz_slots} WHERE quizid = ?',
            [$params['quizid']]
        );
        $DB->set_field('quiz', 'sumgrades', $sumgrades, ['id' => $params['quizid']]);
        $DB->set_field('quiz', 'timemodified', time(), ['id' => $params['quizid']]);

        
        $event = \mod_quiz\event\slot_deleted::create([
            'context' => $context,
            'objectid' => $slotrecord->id,
            'other' => [
                'quizid' => $params['quizid'],
                'slotnumber' => $params['slot'],
                'page' => (int)$slotrecord->page,
            ],
        ]);
        $event->trigger();

        return [
            'success' => true,
            'message' => 'Question "' . $questionname . '" removed from slot ' . $params['slot'],
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
