<?php
namespace local_activity_utils\external\book;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_activity_utils\helper;

/**
 * External function for creating a book resource with chapters.
 *
 * @package    local_activity_utils
 * @copyright  2024 Activity Utils
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_book extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'Book name/title'),
            'intro' => new external_value(PARAM_RAW, 'Book introduction/description (HTML)', VALUE_DEFAULT, ''),
            'section' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, 0),
            'visible' => new external_value(PARAM_INT, 'Visibility (1=visible, 0=hidden)', VALUE_DEFAULT, 1),
            'numbering' => new external_value(PARAM_INT, 'Chapter numbering style (0=none, 1=numbers, 2=bullets, 3=indented)', VALUE_DEFAULT, 1),
            'navstyle' => new external_value(PARAM_INT, 'Navigation style (0=none, 1=images, 2=text)', VALUE_DEFAULT, 1),
            'customtitles' => new external_value(PARAM_INT, 'Use custom titles (0=no, 1=yes)', VALUE_DEFAULT, 0),
            'chapters' => new external_multiple_structure(
                new external_single_structure([
                    'title' => new external_value(PARAM_TEXT, 'Chapter title'),
                    'content' => new external_value(PARAM_RAW, 'Chapter content (HTML)', VALUE_DEFAULT, ''),
                    'subchapter' => new external_value(PARAM_INT, 'Is subchapter (0=main chapter, 1=subchapter)', VALUE_DEFAULT, 0),
                    'hidden' => new external_value(PARAM_INT, 'Hidden (0=visible, 1=hidden)', VALUE_DEFAULT, 0),
                    'tags' => new external_value(PARAM_RAW, 'Comma-separated tags for the chapter', VALUE_DEFAULT, ''),
                ]),
                'Array of chapters to add to the book',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Creates a new book resource with optional chapters.
     *
     * @param int $courseid Course ID
     * @param string $name Book name
     * @param string $intro Book introduction
     * @param int $section Course section number
     * @param int $visible Visibility
     * @param int $numbering Chapter numbering style
     * @param int $navstyle Navigation style
     * @param int $customtitles Use custom titles
     * @param array $chapters Array of chapters
     * @return array Response with book details
     */
    public static function execute(
        int $courseid,
        string $name,
        string $intro = '',
        int $section = 0,
        int $visible = 1,
        int $numbering = 1,
        int $navstyle = 1,
        int $customtitles = 0,
        array $chapters = []
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/book/lib.php');
        require_once($CFG->dirroot . '/mod/book/locallib.php');

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'name' => $name,
            'intro' => $intro,
            'section' => $section,
            'visible' => $visible,
            'numbering' => $numbering,
            'navstyle' => $navstyle,
            'customtitles' => $customtitles,
            'chapters' => $chapters,
        ]);

        // Get course and validate context.
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('local/activity_utils:createbook', $context);
        require_capability('mod/book:addinstance', $context);

        // Validate numbering style (0-3).
        $numbering = max(0, min(3, $params['numbering']));

        // Validate navigation style (0-2).
        $navstyle = max(0, min(2, $params['navstyle']));

        // Create the book record.
        $book = new \stdClass();
        $book->course = $params['courseid'];
        $book->name = $params['name'];
        $book->intro = $params['intro'];
        $book->introformat = FORMAT_HTML;
        $book->numbering = $numbering;
        $book->navstyle = $navstyle;
        $book->customtitles = $params['customtitles'] ? 1 : 0;
        $book->revision = 1;
        $book->timecreated = time();
        $book->timemodified = time();

        $bookid = $DB->insert_record('book', $book);

        // Get module ID for book.
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'book'], MUST_EXIST);

        // Create course module record.
        $cm = new \stdClass();
        $cm->course = $params['courseid'];
        $cm->module = $moduleid;
        $cm->instance = $bookid;
        $cm->section = $params['section'];
        $cm->idnumber = '';
        $cm->added = time();
        $cm->score = 0;
        $cm->indent = 0;
        $cm->visible = $params['visible'];
        $cm->visibleoncoursepage = 1;
        $cm->visibleold = $params['visible'];
        $cm->groupmode = 0;
        $cm->groupingid = 0;
        $cm->completion = 0;
        $cm->completionview = 0;
        $cm->completionexpected = 0;
        $cm->completionpassgrade = 0;
        $cm->showdescription = 0;
        $cm->availability = null;
        $cm->deletioninprogress = 0;
        $cm->downloadcontent = 1;
        $cm->lang = '';
        $cm->completiongradeitemnumber = null;

        $cmid = $DB->insert_record('course_modules', $cm);

        // Add module to section sequence.
        helper::add_module_to_section($params['courseid'], $params['section'], $cmid, $params['visible']);

        // Get module context for file handling.
        $modulecontext = \context_module::instance($cmid);

        // Create chapters if provided.
        $createdchapters = [];
        $pagenum = 0;

        foreach ($params['chapters'] as $chapterdata) {
            $pagenum++;

            $chapter = new \stdClass();
            $chapter->bookid = $bookid;
            $chapter->pagenum = $pagenum;
            $chapter->subchapter = !empty($chapterdata['subchapter']) ? 1 : 0;
            $chapter->title = $chapterdata['title'];
            $chapter->content = $chapterdata['content'] ?? '';
            $chapter->contentformat = FORMAT_HTML;
            $chapter->hidden = !empty($chapterdata['hidden']) ? 1 : 0;
            $chapter->timecreated = time();
            $chapter->timemodified = time();
            $chapter->importsrc = '';

            $chapterid = $DB->insert_record('book_chapters', $chapter);

            // Handle tags if provided.
            if (!empty($chapterdata['tags'])) {
                $tags = array_map('trim', explode(',', $chapterdata['tags']));
                $tags = array_filter($tags);
                if (!empty($tags)) {
                    \core_tag_tag::set_item_tags('mod_book', 'book_chapters', $chapterid, $modulecontext, $tags);
                }
            }

            $createdchapters[] = [
                'id' => $chapterid,
                'pagenum' => $pagenum,
                'title' => $chapter->title,
                'subchapter' => $chapter->subchapter,
            ];
        }

        // Rebuild course cache.
        rebuild_course_cache($params['courseid'], true);

        return [
            'id' => $bookid,
            'coursemoduleid' => $cmid,
            'name' => $params['name'],
            'chaptercount' => count($createdchapters),
            'chapters' => $createdchapters,
            'success' => true,
            'message' => 'Book created successfully' . (count($createdchapters) > 0 ? ' with ' . count($createdchapters) . ' chapter(s)' : ''),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Book ID'),
            'coursemoduleid' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Book name'),
            'chaptercount' => new external_value(PARAM_INT, 'Number of chapters created'),
            'chapters' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Chapter ID'),
                    'pagenum' => new external_value(PARAM_INT, 'Page number'),
                    'title' => new external_value(PARAM_TEXT, 'Chapter title'),
                    'subchapter' => new external_value(PARAM_INT, 'Is subchapter'),
                ]),
                'List of created chapters'
            ),
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
