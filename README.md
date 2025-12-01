# Activity Utils - Moodle Plugin

A Moodle plugin that extends Moodle functionality to allow external applications (such as the [Registry Portal](https://github.com/ntholi/registry-web)) to programmatically create course sections, assignments, and various activities/resources using a REST API.

This plugin provides web service endpoints for:
- Creating course sections
- Creating subsections (Moodle 4.0+)
- Creating assignments
- Deleting assignments
- Creating page activities
- Creating file resources

## Installation

1. Copy this folder to your Moodle installation's `local/` directory
2. Rename the folder to `activity_utils`
3. Log in as admin and go to Site Administration > Notifications
4. Complete the installation

## Setup

**1. Enable Web Services**
- Site Administration > Advanced features
- Check "Enable web services"

**2. Enable REST Protocol**
- Site Administration > Plugins > Web services > Manage protocols
- Enable "REST protocol"

**3. Add the Service Functions**
- Site Administration > Plugins > Web services > External services
- Create a new service or edit an existing one
- Click "Add functions"
- Add the following functions:
  - `local_activity_utils_create_assignment`
  - `local_activity_utils_delete_assignment`
  - `local_activity_utils_create_section`
  - `local_activity_utils_create_subsection`
  - `local_activity_utils_create_page`
  - `local_activity_utils_create_file`

**4. Create an API Token**
- Site Administration > Plugins > Web services > Manage tokens
- Create a token for your API user and service
- Save the token for use in API requests

## API Usage

### Base Endpoint
```
POST https://yourmoodle.com/webservice/rest/server.php
```

### Common Parameters
All API calls require these base parameters:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `wstoken` | string | Yes | Your web service token |
| `wsfunction` | string | Yes | The web service function name (see below) |
| `moodlewsrestformat` | string | Yes | Response format (`json`) |

---

## 1. Create Assignment

Creates a new assignment in a Moodle course.

**Function:** `local_activity_utils_create_assignment`

**Required Capability:** `local/activity_utils:createassignment` and `mod/assign:addinstance`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | integer | Yes | The course ID where the assignment will be created |
| `name` | string | Yes | Assignment name/title |
| `intro` | string | No | Assignment description (supports HTML) |
| `activity` | string | No | Activity instructions (supports HTML) |
| `allowsubmissionsfromdate` | integer | No | Allow submissions from date (Unix timestamp) |
| `duedate` | integer | No | Due date (Unix timestamp) |
| `section` | integer | No | Course section number (default: 0) |
| `idnumber` | string | No | ID number for gradebook and external system reference (default: '') |
| `grademax` | integer | No | Maximum grade for the assignment (default: 100, can be negative to indicate use of a scale) |
| `introfiles` | string | No | Additional files as JSON array (see File Upload Format below) |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_create_assignment" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Weekly Assignment" \
  -d "intro=<p>Complete the exercises below</p>" \
  -d "activity=<p>Read chapter 5 and answer the questions</p>" \
  -d "allowsubmissionsfromdate=1732704000" \
  -d "duedate=1735689600" \
  -d "idnumber=ASSIGN001" \
  -d "grademax=100"
```

**File Upload Format for introfiles:**
```json
[
  {
    "filename": "assignment_guide.pdf",
    "content": "JVBERi0xLjQK...",
    "base64": true
  }
]
```

**Response:**
```json
{
  "id": 45,
  "coursemoduleid": 123,
  "name": "Weekly Assignment",
  "success": true,
  "message": "Assignment created successfully"
}
```

---

## 2. Delete Assignment

Deletes an existing assignment from a Moodle course.

**Function:** `local_activity_utils_delete_assignment`

**Required Capability:** `local/activity_utils:deleteassignment` and `moodle/course:manageactivities`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cmid` | integer | Yes | The course module ID of the assignment to delete |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_delete_assignment" \
  -d "moodlewsrestformat=json" \
  -d "cmid=123"
```

**Response:**
```json
{
  "success": true,
  "message": "Assignment \"Weekly Assignment\" deleted successfully"
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Course module not found"
}
```

```json
{
  "success": false,
  "message": "The specified course module is not an assignment"
}
```

---

## 3. Create Section

Creates a new section in a Moodle course.

**Function:** `local_activity_utils_create_section`

**Required Capability:** `local/activity_utils:createsection` and `moodle/course:update`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | integer | Yes | The course ID |
| `name` | string | No | Section name/title |
| `summary` | string | No | Section summary/description (supports HTML) |
| `sectionnum` | integer | No | Section number/position (auto-generated if not provided) |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_create_section" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Week 1: Introduction" \
  -d "summary=<p>This week we cover the basics</p>"
```

**Response:**
```json
{
  "id": 15,
  "sectionnum": 1,
  "name": "Week 1: Introduction",
  "success": true,
  "message": "Section created successfully"
}
```

---

## 4. Create Subsection

Creates a new subsection within a parent section in a Moodle course. Subsections are a feature introduced in Moodle 4.0+ that allow nested sections for better course organization.

**Function:** `local_activity_utils_create_subsection`

**Required Capability:** `local/activity_utils:createsubsection` and `moodle/course:update`

**Requirements:** Moodle 4.0 or later with subsection module support enabled.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | integer | Yes | The course ID |
| `parentsection` | integer | Yes | Parent section number where the subsection will be nested |
| `name` | string | Yes | Subsection name/title |
| `summary` | string | No | Subsection summary/description (supports HTML) |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden (default: 1) |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_create_subsection" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "parentsection=1" \
  -d "name=Week 1.1: Getting Started" \
  -d "summary=<p>Introduction to the course materials</p>"
```

**Response:**
```json
{
  "id": 20,
  "sectionnum": 5,
  "coursemoduleid": 156,
  "parentsection": 1,
  "name": "Week 1.1: Getting Started",
  "success": true,
  "message": "Subsection created successfully"
}
```

**Adding Activities to Subsections:**

After creating a subsection, you can add activities (pages, assignments, files, etc.) to it by using the `sectionnum` value returned in the response. This allows you to create a hierarchical course structure:

```bash
# First, create a subsection
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_create_subsection" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "parentsection=1" \
  -d "name=Week 1.1: Getting Started"

# Then, add a page to that subsection using the returned sectionnum (e.g., 5)
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_create_page" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "section=5" \
  -d "name=Getting Started Guide" \
  -d "content=<p>Follow these steps to get started...</p>"
```

The subsection and its content will now be properly visible to students as part of the normal course structure.

**Error Responses:**
```json
{
  "exception": "moodle_exception",
  "errorcode": "subsectionmodulenotfound",
  "message": "Subsection module not found. This feature requires Moodle 4.0 or later with subsection support."
}
```

---

## 5. Create Page Activity

Creates a new page activity in a Moodle course.

**Function:** `local_activity_utils_create_page`

**Required Capability:** `local/activity_utils:createpage` and `mod/page:addinstance`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | integer | Yes | The course ID |
| `name` | string | Yes | Page name/title |
| `intro` | string | No | Page introduction/description (supports HTML) |
| `content` | string | No | Page content (supports HTML) |
| `section` | integer | No | Course section number (default: 0) |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden (default: 1) |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_create_page" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Course Syllabus" \
  -d "intro=<p>View the course syllabus below</p>" \
  -d "content=<h1>Course Syllabus</h1><p>Week 1: ...</p>"
```

**Response:**
```json
{
  "id": 28,
  "coursemoduleid": 145,
  "name": "Course Syllabus",
  "success": true,
  "message": "Page created successfully"
}
```

---

## 6. Create File Resource

Creates a new file resource in a Moodle course.

**Function:** `local_activity_utils_create_file`

**Required Capability:** `local/activity_utils:createfile` and `mod/resource:addinstance`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | integer | Yes | The course ID |
| `name` | string | Yes | File resource name/title |
| `intro` | string | No | File resource introduction/description (supports HTML) |
| `filename` | string | Yes | File name (e.g., "document.pdf") |
| `filecontent` | string | Yes | File content (base64 encoded) |
| `section` | integer | No | Course section number (default: 0) |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden (default: 1) |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_create_file" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Course Textbook" \
  -d "intro=<p>Download the course textbook</p>" \
  -d "filename=textbook.pdf" \
  -d "filecontent=JVBERi0xLjQKJeLjz9MKMSAwIG9iago8..."
```

**Response:**
```json
{
  "id": 32,
  "coursemoduleid": 150,
  "name": "Course Textbook",
  "filename": "textbook.pdf",
  "success": true,
  "message": "File resource created successfully"
}
```

---

## Permissions

The API user needs these capabilities for each function:

### Create Assignment
- `local/activity_utils:createassignment` (granted to editing teachers and managers by default)
- `mod/assign:addinstance` (standard Moodle capability)

### Delete Assignment
- `local/activity_utils:deleteassignment` (granted to editing teachers and managers by default)
- `moodle/course:manageactivities` (standard Moodle capability)

### Create Section
- `local/activity_utils:createsection` (granted to editing teachers and managers by default)
- `moodle/course:update` (standard Moodle capability)

### Create Subsection
- `local/activity_utils:createsubsection` (granted to editing teachers and managers by default)
- `moodle/course:update` (standard Moodle capability)

### Create Page
- `local/activity_utils:createpage` (granted to editing teachers and managers by default)
- `mod/page:addinstance` (standard Moodle capability)

### Create File
- `local/activity_utils:createfile` (granted to editing teachers and managers by default)
- `mod/resource:addinstance` (standard Moodle capability)

---

## Compatibility & Migration

### Moodle Version Support

- **Minimum Moodle**: 4.1 (3.9+)
- **Recommended**: Moodle 4.2 or later
- **Subsection features**: Require Moodle 4.0+ with `mod_subsection` module enabled

### v2.4 Breaking Changes & Migration

#### Issue Fixed
In v2.3, subsections were created without proper delegation metadata (`component` and `itemid` fields). This caused:
- Activities placed in subsections appeared as top-level sections instead of nested content
- Visibility inheritance didn't work correctly
- Subsection structure violated Moodle 4.x delegation standards

#### What Changed
v2.4 now correctly sets delegation metadata on subsection course sections:
```php
$section->component = 'mod_subsection';
$section->itemid = $subsection_module_instance_id;
```

#### Migration Path
**Existing Subsections (v2.3 or earlier):**

If you created subsections using v2.3 or earlier, they will continue to function but **without proper delegation**:
- They will appear as regular sections, not nested subsections
- Activities in them won't inherit subsection visibility rules
- The subsection course module will be "orphaned"

**Recommendation:** After upgrading to v2.4, manually recreate subsections using the API. This is a one-time operation.

**SQL Migration Script (if needed):**
```sql
-- This script fixes existing subsections by adding delegation metadata
-- WARNING: Back up your database before running this!
-- Run in Moodle database context

UPDATE {course_sections} cs
SET 
    cs.component = 'mod_subsection',
    cs.itemid = cm.instance,
    cs.timemodified = ?
FROM {course_modules} cm
WHERE 
    cm.module = (SELECT id FROM {modules} WHERE name = 'subsection')
    AND cm.instance = cs.id  -- This only works if itemid matches instance
    AND cs.component IS NULL;
```

**Better approach - Recreate via API:**
1. Export subsection definitions from your course
2. Delete existing subsections
3. Recreate using `create_subsection` endpoint
4. Re-add activities using the new subsection section numbers

### Activity Placement in Subsections

#### How It Works (v2.4+)
When creating an activity in a subsection:

```bash
# Step 1: Create subsection
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wsfunction=local_activity_utils_create_subsection" \
  -d "courseid=2" \
  -d "parentsection=1" \
  -d "name=Week 1.1" \
  # Returns: {"sectionnum": 5, "id": 20, ...}

# Step 2: Create activity in subsection using returned sectionnum
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wsfunction=local_activity_utils_create_page" \
  -d "courseid=2" \
  -d "section=5"  # Use the sectionnum from subsection response
  -d "name=Getting Started"
```

The `section` parameter accepts the **section number** (sectionnum), and the plugin automatically resolves it to the correct delegated section ID.

### Visibility Behavior

#### Visibility Rules (v2.4+)
Activities created in subsections follow these visibility rules:

| Subsection Visible | Activity Visible (requested) | Final Activity Visibility |
|---|---|---|
| Yes | Yes | **Visible** |
| Yes | No | **Hidden** |
| No | Yes | **Hidden** (inherits from parent) |
| No | No | **Hidden** |

Activities are automatically hidden if the parent subsection is hidden, even if you request them to be visible. This prevents "orphaned" visible content in hidden sections.

#### Example
```php
// Create hidden subsection
$subsection = create_subsection::execute(
    courseid: 2,
    parentsection: 1,
    name: 'Hidden Section',
    visible: 0
);

// Request visible page in hidden subsection
$page = create_page::execute(
    courseid: 2,
    section: $subsection['sectionnum'],
    name: 'My Page',
    visible: 1  // Request visibility
);

// Result: Page is HIDDEN because parent subsection is hidden
// (unless/until the subsection is made visible in the course editor)
```

### Database Schema Notes

#### course_sections Table
Subsections use these fields for delegation:
- `component` (VARCHAR): Must be `'mod_subsection'` for subsections
- `itemid` (INT): Must match `course_modules.instance` of the subsection module
- `visible` (TINYINT): Controls visibility of the subsection and its content
- `section` (INT): Unique section number per course

#### course_modules Table
The subsection activity module (instance) is placed in the parent section:
- `cm->section` = parent section ID (the course_sections.id)
- `cm->module` = subsection module ID
- `cm->instance` = ID of the subsection row

### Known Limitations

1. **Subsections in subsections (nested)**: Supported but limited by Moodle's delegation depth
2. **Section numbers**: Once assigned, section numbers cannot be reordered via API (use course edit interface)
3. **Deleting subsections**: Use Moodle's course editor; API doesn't provide delete subsection function
4. **Moving subsections**: Not supported via this API; use course editor

### Troubleshooting Subsection Issues

**Problem**: Activities appear in wrong section or as top-level
- **Cause**: Using section number instead of section ID, or section not properly delegated
- **Solution**: Verify subsection was created with v2.4+; check `course_sections.component` field is `'mod_subsection'`

**Problem**: Activities in subsection are hidden but should be visible
- **Cause**: Parent subsection is hidden
- **Solution**: Make parent subsection visible in course editor, or request activities without visibility flag

**Problem**: "Subsection module not found" error
- **Cause**: Moodle version doesn't support subsections (< 4.0)
- **Solution**: Upgrade Moodle to 4.0 or later, or check if subsection plugin is enabled

**Problem**: Course cache errors after creating subsections
- **Cause**: Course cache not properly rebuilt
- **Solution**: Force cache rebuild: `rebuild_course_cache($courseid, true)` (done automatically by API)

## Future Enhancements

This plugin is designed to be extensible. Future versions may include:
- CRUD operations for all activities (Update operations)
- Support for additional activity types (Quiz, Forum, etc.)
- Bulk operations
- More granular configuration options

---

## Version History

### v2.4 (2024-12-02)
- **FIXED: Subsection delegation and activity placement bug**
  - Fixed critical issue where activities placed in subsections were appearing as top-level sections
  - Subsections now properly set `component='mod_subsection'` and `itemid` metadata for correct Moodle 4.x delegation
  - Activities (pages, assignments, files) created in subsections are now correctly nested and visible
  - Added visibility inheritance: activities in hidden subsections are automatically hidden
  - **Breaking change for stored subsection data**: Existing subsection sections without delegation metadata will appear as regular sections; see Migration section below
- Added `helper` class for shared section handling logic
- Updated all activity creation endpoints to use improved section handling

### v2.3 (2024-12-01)
- **Fixed subsection visibility issue**: Subsections and activities added to them are now properly visible to students and part of the normal course structure
- Removed erroneous `component` and `itemid` fields from subsection course sections that were causing the "not part of course structure" warning

### v2.2 (2024-12-01)
- Added subsection creation functionality (`local_activity_utils_create_subsection`)
- Added `createsubsection` capability
- Subsections require Moodle 4.0+ with subsection module support

### v2.1 (2024-11-29)
- Added delete assignment functionality (`local_activity_utils_delete_assignment`)
- Added `deleteassignment` capability

### v2.0 (2024-11-29)
- Renamed plugin from `local_createassign` to `local_activity_utils`
- Renamed "assessment" to "assignment" throughout
- Added support for creating course sections
- Added support for creating Page activities
- Added support for creating File resources
- Updated all capabilities and web service function names

### v1.0
- Initial release with assignment creation functionality

---

## License

This plugin is developed for the Limkokwing University Registry Portal integration.

## Support

For issues or questions, please contact the development team or check the Moodle documentation.
