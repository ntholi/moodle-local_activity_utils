<?php
namespace local_activity_utils\tests;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use advanced_testcase;
use context_course;
use local_activity_utils\external\create_subsection;
use local_activity_utils\external\create_page;
use local_activity_utils\external\create_assignment;
use local_activity_utils\external\create_file;

/**
 * Test subsection and activity creation functionality.
 *
 * @package    local_activity_utils
 * @category   test
 * @copyright  2024 Limkokwing University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subsection_and_activities_test extends advanced_testcase {

    private $course;
    private $user;

    protected function setUp(): void {
        $this->resetAfterTest(true);

        // Create a course
        $this->course = $this->getDataGenerator()->create_course([
            'fullname' => 'Test Course',
            'shortname' => 'TEST101',
            'numsections' => 5,
        ]);

        // Create a user with editing teacher role
        $this->user = $this->getDataGenerator()->create_user();
        $teacherrole = $this->getDataGenerator()->get_role_by_shortname('editingteacher');
        $this->getDataGenerator()->role_assign($teacherrole->id, $this->user->id, context_course::instance($this->course->id));

        // Set the user as current
        $this->setUser($this->user);
    }

    /**
     * Test creating a subsection sets proper delegation metadata.
     */
    public function test_create_subsection_sets_delegation_metadata(): void {
        global $DB;

        // Create a subsection
        $result = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: 1,
            name: 'Week 1.1: Introduction',
            summary: 'Getting started with the course',
            visible: 1
        );

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['sectionnum']);
        $this->assertNotEmpty($result['coursemoduleid']);

        // Verify the delegated section has proper metadata
        $section = $DB->get_record('course_sections', ['id' => $result['id']]);
        $this->assertNotNull($section);
        $this->assertEquals('mod_subsection', $section->component);
        $this->assertEquals($result['id'], $section->itemid);
        $this->assertEquals(1, $section->visible);

        // Verify the subsection course module exists
        $cm = $DB->get_record('course_modules', ['id' => $result['coursemoduleid']]);
        $this->assertNotNull($cm);
        $this->assertEquals(1, $cm->section); // Parent section
    }

    /**
     * Test that subsection is properly part of parent section's sequence.
     */
    public function test_subsection_in_parent_sequence(): void {
        global $DB;

        $result = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: 1,
            name: 'Subsection 1',
            visible: 1
        );

        // Get parent section
        $parentsection = $DB->get_record('course_sections', [
            'course' => $this->course->id,
            'section' => 1
        ]);

        // Verify subsection CM is in parent section's sequence
        $sequence = array_map('intval', explode(',', $parentsection->sequence));
        $this->assertContains($result['coursemoduleid'], $sequence);
    }

    /**
     * Test creating a page in a subsection places it correctly.
     */
    public function test_create_page_in_subsection(): void {
        global $DB;

        // Create subsection
        $subsection = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: 1,
            name: 'Week 1.1',
            visible: 1
        );

        // Create page in subsection (using returned sectionnum)
        $pageresult = create_page::execute(
            courseid: $this->course->id,
            name: 'Getting Started Guide',
            intro: 'Read this first',
            content: '<h2>Getting Started</h2><p>Follow these steps...</p>',
            section: $subsection['sectionnum'],
            visible: 1
        );

        $this->assertTrue($pageresult['success']);

        // Verify page module is in the subsection's sequence
        $subsection_section = $DB->get_record('course_sections', ['id' => $subsection['id']]);
        $sequence = array_map('intval', explode(',', $subsection_section->sequence ?? ''));
        $this->assertContains($pageresult['coursemoduleid'], $sequence);

        // Verify page CM's section field points to delegated section
        $pagecm = $DB->get_record('course_modules', ['id' => $pageresult['coursemoduleid']]);
        $this->assertEquals($subsection['id'], $pagecm->section);
    }

    /**
     * Test creating an assignment in a subsection.
     */
    public function test_create_assignment_in_subsection(): void {
        global $DB;

        // Create subsection
        $subsection = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: 2,
            name: 'Week 2.1: Assignments',
            visible: 1
        );

        // Create assignment in subsection
        $assignresult = create_assignment::execute(
            courseid: $this->course->id,
            name: 'Assignment 1',
            intro: 'Complete this assignment',
            activity: 'Answer all questions',
            section: $subsection['sectionnum'],
            duedate: 0,
            grademax: 100
        );

        $this->assertTrue($assignresult['success']);

        // Verify assignment is in subsection
        $subsection_section = $DB->get_record('course_sections', ['id' => $subsection['id']]);
        $sequence = array_map('intval', explode(',', $subsection_section->sequence ?? ''));
        $this->assertContains($assignresult['coursemoduleid'], $sequence);

        // Verify assignment CM points to delegated section
        $assigncm = $DB->get_record('course_modules', ['id' => $assignresult['coursemoduleid']]);
        $this->assertEquals($subsection['id'], $assigncm->section);
    }

    /**
     * Test creating a file resource in a subsection.
     */
    public function test_create_file_in_subsection(): void {
        global $DB;

        // Create subsection
        $subsection = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: 3,
            name: 'Week 3.1: Resources',
            visible: 1
        );

        // Create file in subsection
        $filecontent = base64_encode('This is test file content');
        $fileresult = create_file::execute(
            courseid: $this->course->id,
            name: 'Course Syllabus',
            intro: 'Download the syllabus',
            filename: 'syllabus.txt',
            filecontent: $filecontent,
            section: $subsection['sectionnum'],
            visible: 1
        );

        $this->assertTrue($fileresult['success']);

        // Verify file is in subsection
        $subsection_section = $DB->get_record('course_sections', ['id' => $subsection['id']]);
        $sequence = array_map('intval', explode(',', $subsection_section->sequence ?? ''));
        $this->assertContains($fileresult['coursemoduleid'], $sequence);

        // Verify file CM points to delegated section
        $filecm = $DB->get_record('course_modules', ['id' => $fileresult['coursemoduleid']]);
        $this->assertEquals($subsection['id'], $filecm->section);
    }

    /**
     * Test visibility inheritance when subsection is hidden.
     */
    public function test_activity_visibility_respects_hidden_subsection(): void {
        global $DB;

        // Create hidden subsection
        $subsection = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: 1,
            name: 'Hidden Subsection',
            visible: 0
        );

        // Create visible page in hidden subsection
        $pageresult = create_page::execute(
            courseid: $this->course->id,
            name: 'Page in Hidden Subsection',
            content: 'This content is in a hidden subsection',
            section: $subsection['sectionnum'],
            visible: 1
        );

        // Verify page is hidden due to parent subsection being hidden
        $pagecm = $DB->get_record('course_modules', ['id' => $pageresult['coursemoduleid']]);
        $this->assertEquals(0, $pagecm->visible, 'Page should be hidden when its subsection is hidden');
    }

    /**
     * Test multiple activities in subsection maintain order.
     */
    public function test_multiple_activities_in_subsection(): void {
        global $DB;

        // Create subsection
        $subsection = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: 1,
            name: 'Multi-activity Subsection',
            visible: 1
        );

        // Create multiple pages
        $page1 = create_page::execute(
            courseid: $this->course->id,
            name: 'Page 1',
            section: $subsection['sectionnum']
        );
        $page2 = create_page::execute(
            courseid: $this->course->id,
            name: 'Page 2',
            section: $subsection['sectionnum']
        );
        $page3 = create_page::execute(
            courseid: $this->course->id,
            name: 'Page 3',
            section: $subsection['sectionnum']
        );

        // Verify all are in subsection sequence in order
        $subsection_section = $DB->get_record('course_sections', ['id' => $subsection['id']]);
        $sequence = array_map('intval', explode(',', $subsection_section->sequence ?? ''));

        $page1pos = array_search($page1['coursemoduleid'], $sequence);
        $page2pos = array_search($page2['coursemoduleid'], $sequence);
        $page3pos = array_search($page3['coursemoduleid'], $sequence);

        $this->assertNotFalse($page1pos);
        $this->assertNotFalse($page2pos);
        $this->assertNotFalse($page3pos);
        $this->assertLessThan($page2pos, $page1pos);
        $this->assertLessThan($page3pos, $page2pos);
    }

    /**
     * Test nested subsections (subsection in subsection).
     */
    public function test_nested_subsections(): void {
        global $DB;

        // Create first level subsection
        $level1 = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: 1,
            name: 'Level 1 Subsection',
            visible: 1
        );

        // Create second level subsection in first level
        $level2 = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: $level1['sectionnum'],
            name: 'Level 2 Subsection',
            visible: 1
        );

        // Create activity in level 2
        $pageresult = create_page::execute(
            courseid: $this->course->id,
            name: 'Nested Page',
            section: $level2['sectionnum'],
            visible: 1
        );

        // Verify level 2 is delegated
        $level2section = $DB->get_record('course_sections', ['id' => $level2['id']]);
        $this->assertEquals('mod_subsection', $level2section->component);

        // Verify page is in level 2
        $pagesection = $DB->get_record('course_sections', ['id' => $level2['id']]);
        $sequence = array_map('intval', explode(',', $pagesection->sequence ?? ''));
        $this->assertContains($pageresult['coursemoduleid'], $sequence);
    }

    /**
     * Test course cache is properly rebuilt after creating subsection and activities.
     */
    public function test_course_cache_rebuilt(): void {
        global $CFG;

        // Create subsection
        $subsection = create_subsection::execute(
            courseid: $this->course->id,
            parentsection: 1,
            name: 'Cache Test',
            visible: 1
        );

        // Create page
        create_page::execute(
            courseid: $this->course->id,
            name: 'Cache Test Page',
            section: $subsection['sectionnum']
        );

        // Verify course cache is valid by loading modinfo
        require_once($CFG->dirroot . '/course/lib.php');
        $modinfo = get_fast_modinfo($this->course);

        // Should not throw any exceptions
        $this->assertNotNull($modinfo);
        $this->assertGreater(count($modinfo->sections), 0);
    }
}
