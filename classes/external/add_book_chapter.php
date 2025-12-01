<?php
namespace local_activity_utils\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function for adding a chapter to an existing book.
 *
 * @package    local_activity_utils
 * @copyright  2024 Activity Utils
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_book_chapter extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bookid' => new external_value(PARAM_INT, 'Book instance ID'),
            'title' => new external_value(PARAM_TEXT, 'Chapter title'),
            'content' => new external_value(PARAM_RAW, 'Chapter content (HTML)', VALUE_DEFAULT, ''),
            'subchapter' => new external_value(PARAM_INT, 'Is subchapter (0=main chapter, 1=subchapter)', VALUE_DEFAULT, 0),
            'hidden' => new external_value(PARAM_INT, 'Hidden (0=visible, 1=hidden)', VALUE_DEFAULT, 0),
            'pagenum' => new external_value(PARAM_INT, 'Page number position (0=append at end)', VALUE_DEFAULT, 0),
            'tags' => new external_value(PARAM_RAW, 'Comma-separated tags for the chapter', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Adds a chapter to an existing book.
     *
     * @param int $bookid Book instance ID
     * @param string $title Chapter title
     * @param string $content Chapter content
     * @param int $subchapter Is subchapter
     * @param int $hidden Is hidden
     * @param int $pagenum Page number position
     * @param string $tags Comma-separated tags
     * @return array Response with chapter details
     */
    public static function execute(
        int $bookid,
        string $title,
        string $content = '',
        int $subchapter = 0,
        int $hidden = 0,
        int $pagenum = 0,
        string $tags = ''
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/book/lib.php');
        require_once($CFG->dirroot . '/mod/book/locallib.php');

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'bookid' => $bookid,
            'title' => $title,
            'content' => $content,
            'subchapter' => $subchapter,
            'hidden' => $hidden,
            'pagenum' => $pagenum,
            'tags' => $tags,
        ]);

        // Get book and course module.
        $book = $DB->get_record('book', ['id' => $params['bookid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('book', $book->id, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        // Validate context.
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('local/activity_utils:createbook', \context_course::instance($course->id));
        require_capability('mod/book:edit', $context);

        // Determine page number.
        $maxpagenum = $DB->get_field_sql('SELECT MAX(pagenum) FROM {book_chapters} WHERE bookid = ?', [$book->id]);
        $maxpagenum = $maxpagenum ? (int)$maxpagenum : 0;

        if ($params['pagenum'] <= 0 || $params['pagenum'] > $maxpagenum + 1) {
            // Append at the end.
            $newpagenum = $maxpagenum + 1;
        } else {
            // Insert at specified position.
            $newpagenum = $params['pagenum'];

            // Make room for the new chapter by shifting existing pages.
            $sql = "UPDATE {book_chapters}
                       SET pagenum = pagenum + 1
                     WHERE bookid = ? AND pagenum >= ?";
            $DB->execute($sql, [$book->id, $newpagenum]);
        }

        // Create chapter record.
        $chapter = new \stdClass();
        $chapter->bookid = $book->id;
        $chapter->pagenum = $newpagenum;
        $chapter->subchapter = $params['subchapter'] ? 1 : 0;
        $chapter->title = $params['title'];
        $chapter->content = $params['content'];
        $chapter->contentformat = FORMAT_HTML;
        $chapter->hidden = $params['hidden'] ? 1 : 0;
        $chapter->timecreated = time();
        $chapter->timemodified = time();
        $chapter->importsrc = '';

        $chapterid = $DB->insert_record('book_chapters', $chapter);

        // Handle tags if provided.
        if (!empty($params['tags'])) {
            $tagsarray = array_map('trim', explode(',', $params['tags']));
            $tagsarray = array_filter($tagsarray);
            if (!empty($tagsarray)) {
                \core_tag_tag::set_item_tags('mod_book', 'book_chapters', $chapterid, $context, $tagsarray);
            }
        }

        // Increment book revision.
        $DB->set_field('book', 'revision', $book->revision + 1, ['id' => $book->id]);

        // Rebuild course cache.
        rebuild_course_cache($course->id, true);

        return [
            'id' => $chapterid,
            'bookid' => $book->id,
            'pagenum' => $newpagenum,
            'title' => $params['title'],
            'subchapter' => $chapter->subchapter,
            'success' => true,
            'message' => 'Chapter added successfully',
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Chapter ID'),
            'bookid' => new external_value(PARAM_INT, 'Book ID'),
            'pagenum' => new external_value(PARAM_INT, 'Page number'),
            'title' => new external_value(PARAM_TEXT, 'Chapter title'),
            'subchapter' => new external_value(PARAM_INT, 'Is subchapter'),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
