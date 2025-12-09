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

        // Get quiz record.
        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);

        // Get course module.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, 0, false, MUST_EXIST);

        // Validate context and capabilities.
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('local/activity_utils:managequizquestions', $context);
        require_capability('mod/quiz:manage', $context);

        // Find the slot record.
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

        // Get the question name for the response message.
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

        // Get section information.
        $sections = $DB->get_records('quiz_sections', ['quizid' => $params['quizid']], 'firstslot ASC');
        $sectioncount = count($sections);

        // Check if this is the first slot of a section (only matters if there are multiple sections).
        if ($sectioncount > 1) {
            foreach ($sections as $section) {
                if ($section->firstslot == $params['slot']) {
                    // This is the first slot of a section.
                    // Check if there are other slots after this one before the next section.
                    $maxslot = $DB->get_field_sql(
                        'SELECT MAX(slot) FROM {quiz_slots} WHERE quizid = ?',
                        [$params['quizid']]
                    );

                    if ($params['slot'] == $maxslot) {
                        // This is the last slot and it's a section start.
                        // We need to check if there's only one slot in this section.
                        $slotsinsection = $DB->count_records_select(
                            'quiz_slots',
                            'quizid = ? AND slot >= ?',
                            [$params['quizid'], $params['slot']]
                        );

                        if ($slotsinsection == 1 && $sectioncount > 1) {
                            // Delete the section since it will be empty.
                            $DB->delete_records('quiz_sections', ['id' => $section->id]);
                        }
                    }
                    break;
                }
            }
        }

        // Delete the question reference.
        $DB->delete_records('question_references', [
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => $slotrecord->id,
        ]);

        // Delete the slot.
        $DB->delete_records('quiz_slots', ['id' => $slotrecord->id]);

        // Renumber all slots after the deleted one.
        $sql = "UPDATE {quiz_slots}
                   SET slot = slot - 1
                 WHERE quizid = ? AND slot > ?";
        $DB->execute($sql, [$params['quizid'], $params['slot']]);

        // Update section firstslots.
        $sql = "UPDATE {quiz_sections}
                   SET firstslot = firstslot - 1
                 WHERE quizid = ? AND firstslot > ?";
        $DB->execute($sql, [$params['quizid'], $params['slot']]);

        // Update sumgrades.
        $sumgrades = $DB->get_field_sql(
            'SELECT COALESCE(SUM(maxmark), 0) FROM {quiz_slots} WHERE quizid = ?',
            [$params['quizid']]
        );
        $DB->set_field('quiz', 'sumgrades', $sumgrades, ['id' => $params['quizid']]);
        $DB->set_field('quiz', 'timemodified', time(), ['id' => $params['quizid']]);

        // Trigger the slot_deleted event.
        $event = \mod_quiz\event\slot_deleted::create([
            'context' => $context,
            'objectid' => $slotrecord->id,
            'other' => [
                'quizid' => $params['quizid'],
                'slotnumber' => $params['slot'],
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
