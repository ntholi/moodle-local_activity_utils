# Activity Utils

A Moodle local plugin that provides REST API endpoints for programmatically managing course content. Enables external applications to create, update, and manage course sections, assignments, pages, files, and books.

**Version:** 2.8
**Requirements:** Moodle 4.2+
**Developed for:** Limkokwing University Registry Portal

## Features

### Content Management
- **Sections:** Create, update, and manage course sections and subsections (Moodle 4.0+)
- **Assignments:** Create, update, and delete assignments with file attachments
- **Pages:** Create and update HTML page activities
- **Files:** Create and update file resources with base64 upload
- **Books:** Create multi-chapter books with hierarchical structure

### API Endpoints
21 web service functions organized by resource type:
- Assignments (3): create, update, delete
- Sections (4): create section/subsection, update section/subsection
- Pages (2): create, update
- Files (2): create, update
- Books (5): create, update, add chapter, update chapter, get book
- Rubrics (5): create, get, update, delete, copy

## Installation

1. Copy the plugin to `local/activity_utils` in your Moodle installation
2. Log in as admin and navigate to **Site Administration > Notifications**
3. Complete the installation process

## Setup

### 1. Enable Web Services
**Site Administration > Advanced features**
- Enable "Enable web services"

### 2. Enable REST Protocol
**Site Administration > Plugins > Web services > Manage protocols**
- Enable "REST protocol"

### 3. Create External Service
**Site Administration > Plugins > Web services > External services**

Create a new service and add these functions:
```
local_activity_utils_create_assignment
local_activity_utils_update_assignment
local_activity_utils_delete_assignment
local_activity_utils_create_section
local_activity_utils_update_section
local_activity_utils_create_subsection
local_activity_utils_update_subsection
local_activity_utils_create_page
local_activity_utils_update_page
local_activity_utils_create_file
local_activity_utils_update_file
local_activity_utils_create_book
local_activity_utils_update_book
local_activity_utils_add_book_chapter
local_activity_utils_update_book_chapter
local_activity_utils_get_book
local_activity_utils_create_rubric
local_activity_utils_get_rubric
local_activity_utils_update_rubric
local_activity_utils_delete_rubric
local_activity_utils_copy_rubric
```

### 4. Generate API Token
**Site Administration > Plugins > Web services > Manage tokens**
- Create token for your API user and service
- Save the token for API requests

## API Usage

### Base Endpoint
```
POST https://yourmoodle.com/webservice/rest/server.php
```

### Required Parameters
| Parameter | Value | Description |
|-----------|-------|-------------|
| `wstoken` | string | Your web service token |
| `wsfunction` | string | Function name from list below |
| `moodlewsrestformat` | `json` | Response format |

---

## API Reference

### Sections

#### Create Section
**Function:** `local_activity_utils_create_section`

Creates a new section in a course.

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `courseid` | int | Yes | Course ID |
| `name` | string | No | Section name |
| `summary` | string | No | Section description (HTML) |
| `sectionnum` | int | No | Position (auto-assigned if omitted) |

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

#### Create Subsection
**Function:** `local_activity_utils_create_subsection`

Creates a nested subsection within a parent section (Moodle 4.0+).

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `courseid` | int | Yes | Course ID |
| `parentsection` | int | Yes | Parent section number |
| `name` | string | Yes | Subsection name |
| `summary` | string | No | Subsection description (HTML) |
| `visible` | int | No | Visibility: 1=visible, 0=hidden |

**Response:**
```json
{
  "id": 20,
  "sectionnum": 5,
  "coursemoduleid": 156,
  "parentsection": 1,
  "name": "Week 1: Getting Started",
  "success": true,
  "message": "Subsection created successfully"
}
```

**Note:** Use the returned `sectionnum` when adding activities to the subsection.

#### Update Section
**Function:** `local_activity_utils_update_section`

**Parameters:** `sectionid` (required), `name`, `summary`, `visible`

#### Update Subsection
**Function:** `local_activity_utils_update_subsection`

**Parameters:** `sectionid` (required), `name`, `summary`, `visible`

---

### Assignments

#### Create Assignment
**Function:** `local_activity_utils_create_assignment`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `courseid` | int | Yes | Course ID |
| `name` | string | Yes | Assignment title |
| `intro` | string | No | Description (HTML) |
| `activity` | string | No | Instructions (HTML) |
| `allowsubmissionsfromdate` | int | No | Start date (Unix timestamp) |
| `duedate` | int | No | Due date (Unix timestamp) |
| `section` | int | No | Section number (default: 0) |
| `idnumber` | string | No | ID for gradebook |
| `grademax` | int | No | Maximum grade (default: 100) |
| `introfiles` | string | No | Files as JSON array (base64) |

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

#### Update Assignment
**Function:** `local_activity_utils_update_assignment`

**Parameters:** `assignmentid` (required), plus optional fields: `name`, `intro`, `activity`, `allowsubmissionsfromdate`, `duedate`, `cutoffdate`, `idnumber`, `grademax`, `visible`

#### Delete Assignment
**Function:** `local_activity_utils_delete_assignment`

**Parameters:** `cmid` (course module ID)

---

### Pages

#### Create Page
**Function:** `local_activity_utils_create_page`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `courseid` | int | Yes | Course ID |
| `name` | string | Yes | Page title |
| `intro` | string | No | Introduction (HTML) |
| `content` | string | No | Page content (HTML) |
| `section` | int | No | Section number (default: 0) |
| `visible` | int | No | Visibility: 1=visible, 0=hidden |

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

#### Update Page
**Function:** `local_activity_utils_update_page`

**Parameters:** `pageid` (required), plus optional fields: `name`, `intro`, `content`, `visible`

---

### Files

#### Create File
**Function:** `local_activity_utils_create_file`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `courseid` | int | Yes | Course ID |
| `name` | string | Yes | Resource title |
| `intro` | string | No | Description (HTML) |
| `filename` | string | Yes | File name (e.g., "document.pdf") |
| `filecontent` | string | Yes | Base64-encoded file content |
| `section` | int | No | Section number (default: 0) |
| `visible` | int | No | Visibility: 1=visible, 0=hidden |

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

#### Update File
**Function:** `local_activity_utils_update_file`

**Parameters:** `resourceid` (required), plus optional fields: `name`, `intro`, `filename`, `filecontent`, `visible`

---

### Books

#### Create Book
**Function:** `local_activity_utils_create_book`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `courseid` | int | Yes | Course ID |
| `name` | string | Yes | Book title |
| `intro` | string | No | Description (HTML) |
| `section` | int | No | Section number (default: 0) |
| `visible` | int | No | Visibility: 1=visible, 0=hidden |
| `numbering` | int | No | Style: 0=none, 1=numbers, 2=bullets, 3=indented |
| `navstyle` | int | No | Navigation: 0=none, 1=images, 2=text |
| `customtitles` | int | No | Custom titles: 0=no, 1=yes |
| `chapters` | array | No | Array of chapter objects |

**Chapter Object:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `title` | string | Yes | Chapter title |
| `content` | string | No | Chapter content (HTML) |
| `subchapter` | int | No | 0=main chapter, 1=subchapter |
| `hidden` | int | No | 0=visible, 1=hidden |
| `tags` | string | No | Comma-separated tags |

**Response:**
```json
{
  "id": 45,
  "coursemoduleid": 165,
  "name": "Introduction to Programming",
  "chaptercount": 3,
  "chapters": [
    {
      "id": 101,
      "pagenum": 1,
      "title": "Getting Started",
      "subchapter": 0
    }
  ],
  "success": true,
  "message": "Book created successfully with 3 chapter(s)"
}
```

#### Add Book Chapter
**Function:** `local_activity_utils_add_book_chapter`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `bookid` | int | Yes | Book instance ID |
| `title` | string | Yes | Chapter title |
| `content` | string | No | Chapter content (HTML) |
| `subchapter` | int | No | 0=main chapter, 1=subchapter |
| `hidden` | int | No | 0=visible, 1=hidden |
| `pagenum` | int | No | Position (0=append at end) |
| `tags` | string | No | Comma-separated tags |

**Response:**
```json
{
  "id": 104,
  "bookid": 45,
  "pagenum": 4,
  "title": "Advanced Topics",
  "subchapter": 0,
  "success": true,
  "message": "Chapter added successfully"
}
```

#### Get Book
**Function:** `local_activity_utils_get_book`

Retrieves complete book details including all chapters and content.

**Parameters:** `bookid` (book instance ID)

**Response:**
```json
{
  "id": 45,
  "coursemoduleid": 165,
  "courseid": 2,
  "coursename": "Introduction to Computer Science",
  "name": "Introduction to Programming",
  "chapters": [
    {
      "id": 101,
      "pagenum": 1,
      "subchapter": 0,
      "title": "Getting Started",
      "content": "<p>Welcome...</p>",
      "tags": []
    }
  ],
  "success": true,
  "message": "Book retrieved successfully with 3 chapter(s)"
}
```

#### Update Book
**Function:** `local_activity_utils_update_book`

**Parameters:** `bookid` (required), plus optional fields: `name`, `intro`, `numbering`, `navstyle`, `customtitles`, `visible`

#### Update Book Chapter
**Function:** `local_activity_utils_update_book_chapter`

**Parameters:** `chapterid` (required), plus optional fields: `title`, `content`, `subchapter`, `hidden`, `tags`

---

### Rubrics

Rubric endpoints provide simplified management of assignment rubrics. The official Moodle API has `core_grading_save_definitions` which is complex to use. These endpoints offer a cleaner interface for common rubric operations, plus functions not available in the core API (delete, copy).

#### Create Rubric
**Function:** `local_activity_utils_create_rubric`

Creates a rubric for an assignment.

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `cmid` | int | Yes | Course module ID of the assignment |
| `name` | string | Yes | Rubric name |
| `description` | string | No | Rubric description |
| `criteria` | array | Yes | Array of criteria objects |
| `options` | object | No | Rubric display options |

**Criterion Object:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `description` | string | Yes | Criterion description |
| `sortorder` | int | No | Display order (auto-assigned if omitted) |
| `levels` | array | Yes | Array of level objects |

**Level Object:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `score` | float | Yes | Points for this level |
| `definition` | string | Yes | Level description |

**Options Object (all optional, defaults shown):**
| Field | Default | Description |
|-------|---------|-------------|
| `sortlevelsasc` | 1 | Sort levels ascending by score |
| `lockzeropoints` | 1 | Lock zero points option |
| `showdescriptionstudent` | 1 | Show criterion descriptions to students |
| `showdescriptionteacher` | 1 | Show criterion descriptions to teachers |
| `showscoreteacher` | 1 | Show scores to teachers |
| `showscorestudent` | 1 | Show scores to students |
| `enableremarks` | 1 | Enable remarks per criterion |
| `showremarksstudent` | 1 | Show remarks to students |

**Response:**
```json
{
  "definitionid": 15,
  "success": true,
  "message": "Rubric created successfully"
}
```

#### Get Rubric
**Function:** `local_activity_utils_get_rubric`

Retrieves the rubric definition for an assignment.

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `cmid` | int | Yes | Course module ID of the assignment |

**Response:**
```json
{
  "definitionid": 15,
  "name": "Essay Rubric",
  "description": "Rubric for grading essays",
  "status": 20,
  "criteria": [
    {
      "id": 1,
      "description": "Content Quality",
      "sortorder": 1,
      "levels": [
        {"id": 1, "score": 0, "definition": "Poor - Does not meet requirements"},
        {"id": 2, "score": 5, "definition": "Adequate - Meets basic requirements"},
        {"id": 3, "score": 10, "definition": "Excellent - Exceeds expectations"}
      ]
    },
    {
      "id": 2,
      "description": "Grammar and Style",
      "sortorder": 2,
      "levels": [
        {"id": 4, "score": 0, "definition": "Many errors"},
        {"id": 5, "score": 2.5, "definition": "Some errors"},
        {"id": 6, "score": 5, "definition": "Error-free"}
      ]
    }
  ],
  "options": {
    "sortlevelsasc": 1,
    "showscorestudent": 1,
    "enableremarks": 1
  },
  "maxscore": 15,
  "success": true,
  "message": "Rubric retrieved successfully"
}
```

#### Update Rubric
**Function:** `local_activity_utils_update_rubric`

Updates an existing rubric. Can update name, description, options, or completely replace criteria.

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `cmid` | int | Yes | Course module ID of the assignment |
| `name` | string | No | New rubric name |
| `description` | string | No | New rubric description |
| `criteria` | array | No | New criteria (replaces all existing) |
| `options` | object | No | Options to update (merged with existing) |

**Note:** When providing `criteria`, include `id` for existing criteria/levels to preserve them, or omit `id` to create new ones. All criteria not included will be deleted.

**Response:**
```json
{
  "definitionid": 15,
  "success": true,
  "message": "Rubric updated successfully"
}
```

#### Delete Rubric
**Function:** `local_activity_utils_delete_rubric`

Deletes a rubric from an assignment and reverts to simple grading. **Not available in core Moodle API.**

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `cmid` | int | Yes | Course module ID of the assignment |

**Response:**
```json
{
  "success": true,
  "message": "Rubric deleted successfully"
}
```

**Warning:** This also deletes all existing rubric grades for the assignment.

#### Copy Rubric
**Function:** `local_activity_utils_copy_rubric`

Copies a rubric from one assignment to another. **Not available in core Moodle API.**

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `sourcecmid` | int | Yes | Source assignment course module ID |
| `targetcmid` | int | Yes | Target assignment course module ID |

**Response:**
```json
{
  "definitionid": 20,
  "success": true,
  "message": "Rubric copied successfully"
}
```

**Note:** The target assignment must not already have a rubric defined.

---

## Examples

### Create a Section with Subsection and Page
```bash
# 1. Create section
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_activity_utils_create_section" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Week 1"

# 2. Create subsection (returns sectionnum=5)
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_activity_utils_create_subsection" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "parentsection=1" \
  -d "name=Week 1.1: Introduction"

# 3. Add page to subsection
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_activity_utils_create_page" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "section=5" \
  -d "name=Welcome Page" \
  -d "content=<h1>Welcome</h1>"
```

### Create Book with Chapters
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_activity_utils_create_book" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Programming Guide" \
  -d "numbering=1" \
  -d "chapters[0][title]=Introduction" \
  -d "chapters[0][content]=<p>Getting started...</p>" \
  -d "chapters[0][subchapter]=0" \
  -d "chapters[1][title]=Setup" \
  -d "chapters[1][content]=<p>Installation steps...</p>" \
  -d "chapters[1][subchapter]=1"
```

### Create Rubric for Assignment
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_activity_utils_create_rubric" \
  -d "moodlewsrestformat=json" \
  -d "cmid=123" \
  -d "name=Essay Rubric" \
  -d "description=Rubric for grading essays" \
  -d "criteria[0][description]=Content Quality" \
  -d "criteria[0][levels][0][score]=0" \
  -d "criteria[0][levels][0][definition]=Poor" \
  -d "criteria[0][levels][1][score]=5" \
  -d "criteria[0][levels][1][definition]=Adequate" \
  -d "criteria[0][levels][2][score]=10" \
  -d "criteria[0][levels][2][definition]=Excellent" \
  -d "criteria[1][description]=Grammar" \
  -d "criteria[1][levels][0][score]=0" \
  -d "criteria[1][levels][0][definition]=Many errors" \
  -d "criteria[1][levels][1][score]=5" \
  -d "criteria[1][levels][1][definition]=Error-free"
```

### Copy Rubric to Another Assignment
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_activity_utils_copy_rubric" \
  -d "moodlewsrestformat=json" \
  -d "sourcecmid=123" \
  -d "targetcmid=456"
```

---

## Permissions

Plugin capabilities are granted to **editing teachers** and **managers** by default (except `readbook` which includes students).

### Plugin Capabilities
- `local/activity_utils:createassignment`
- `local/activity_utils:updateassignment`
- `local/activity_utils:deleteassignment`
- `local/activity_utils:createsection`
- `local/activity_utils:updatesection`
- `local/activity_utils:createsubsection`
- `local/activity_utils:updatesubsection`
- `local/activity_utils:createpage`
- `local/activity_utils:updatepage`
- `local/activity_utils:createfile`
- `local/activity_utils:updatefile`
- `local/activity_utils:createbook`
- `local/activity_utils:updatebook`
- `local/activity_utils:readbook` (granted to students, teachers, editing teachers, managers)
- `local/activity_utils:managerubric`

### Standard Moodle Capabilities
Functions also require relevant Moodle capabilities:
- `mod/assign:addinstance` (assignments)
- `mod/page:addinstance` (pages)
- `mod/resource:addinstance` (files)
- `mod/book:addinstance`, `mod/book:edit`, `mod/book:read` (books)
- `moodle/course:update`, `moodle/course:manageactivities` (sections)
- `moodle/grade:managegradingforms` (rubrics)

---

## Important Notes

### Subsection Visibility
Activities inherit visibility from their parent subsection. If a subsection is hidden, all activities within it are automatically hidden, even if created with `visible=1`.

### File Upload Format
Files must be base64-encoded:
```json
[
  {
    "filename": "document.pdf",
    "content": "JVBERi0xLjQK...",
    "base64": true
  }
]
```

### Book Chapter Hierarchy
- `subchapter=0`: Main chapter
- `subchapter=1`: Subchapter (nested under previous main chapter)
- Chapters are ordered by `pagenum`

---

## License

Developed for Limkokwing University Registry Portal integration.

## Support

For issues or questions, refer to the [Moodle documentation](https://docs.moodle.org) or contact the development team.
