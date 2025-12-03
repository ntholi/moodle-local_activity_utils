# Activity Utils - Moodle Plugin

A Moodle plugin that extends Moodle functionality to allow external applications (such as the [Registry Portal](https://github.com/ntholi/registry-web)) to programmatically create course sections, assignments, and various activities/resources using a REST API.

**Requirements:** Moodle 4.2 or higher

This plugin provides web service endpoints for:
- Creating course sections
- Creating subsections
- Creating assignments
- Deleting assignments
- Creating page activities
- Creating file resources
- Creating book resources with chapters
- Adding chapters to existing books
- Updating assignments
- Updating page activities
- Updating file resources
- Updating book resources
- Updating book chapters
- Updating course sections
- Updating subsections

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
  - `local_activity_utils_create_book`
  - `local_activity_utils_add_book_chapter`
  - `local_activity_utils_get_book`
  - `local_activity_utils_update_assignment`
  - `local_activity_utils_update_page`
  - `local_activity_utils_update_file`
  - `local_activity_utils_update_book`
  - `local_activity_utils_update_book_chapter`
  - `local_activity_utils_update_section`
  - `local_activity_utils_update_subsection`

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

## 7. Create Book Resource

Creates a new book resource in a Moodle course with optional chapters. The Book module allows creating multi-page resources with chapters and subchapters for organizing lengthy content.

**Function:** `local_activity_utils_create_book`

**Required Capability:** `local/activity_utils:createbook` and `mod/book:addinstance`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | integer | Yes | The course ID |
| `name` | string | Yes | Book name/title |
| `intro` | string | No | Book introduction/description (supports HTML) |
| `section` | integer | No | Course section number (default: 0) |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden (default: 1) |
| `numbering` | integer | No | Chapter numbering style (default: 1) |
| `navstyle` | integer | No | Navigation style (default: 1) |
| `customtitles` | integer | No | Use custom titles: 0=no, 1=yes (default: 0) |
| `chapters` | array | No | Array of chapter objects (see below) |

**Numbering Styles:**
| Value | Style |
|-------|-------|
| 0 | None |
| 1 | Numbers (1, 1.1, 1.2, 2, ...) |
| 2 | Bullets |
| 3 | Indented |

**Navigation Styles:**
| Value | Style |
|-------|-------|
| 0 | Not displayed |
| 1 | Images (arrows) |
| 2 | Text |

**Chapter Object Structure:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `title` | string | Yes | Chapter title |
| `content` | string | No | Chapter content (HTML) |
| `subchapter` | integer | No | 0=main chapter, 1=subchapter (default: 0) |
| `hidden` | integer | No | 0=visible, 1=hidden (default: 0) |
| `tags` | string | No | Comma-separated tags for the chapter |

**Example Request - Book with Chapters:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_create_book" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Introduction to Programming" \
  -d "intro=<p>A comprehensive guide to programming basics</p>" \
  -d "section=1" \
  -d "numbering=1" \
  -d "navstyle=1" \
  -d "chapters[0][title]=Getting Started" \
  -d "chapters[0][content]=<p>Welcome to the programming course!</p>" \
  -d "chapters[0][subchapter]=0" \
  -d "chapters[1][title]=Installing Tools" \
  -d "chapters[1][content]=<p>First, install the required software...</p>" \
  -d "chapters[1][subchapter]=1" \
  -d "chapters[1][tags]=setup,installation" \
  -d "chapters[2][title]=Your First Program" \
  -d "chapters[2][content]=<p>Let us write our first program...</p>" \
  -d "chapters[2][subchapter]=1"
```

**Example Request - Empty Book:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_create_book" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Course Handbook" \
  -d "intro=<p>Student handbook for this course</p>"
```

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
    },
    {
      "id": 102,
      "pagenum": 2,
      "title": "Installing Tools",
      "subchapter": 1
    },
    {
      "id": 103,
      "pagenum": 3,
      "title": "Your First Program",
      "subchapter": 1
    }
  ],
  "success": true,
  "message": "Book created successfully with 3 chapter(s)"
}
```

---

## 8. Add Book Chapter

Adds a new chapter to an existing book resource. Useful for programmatically building book content over time.

**Function:** `local_activity_utils_add_book_chapter`

**Required Capability:** `local/activity_utils:createbook` and `mod/book:edit`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `bookid` | integer | Yes | The book instance ID (not course module ID) |
| `title` | string | Yes | Chapter title |
| `content` | string | No | Chapter content (supports HTML) |
| `subchapter` | integer | No | 0=main chapter, 1=subchapter (default: 0) |
| `hidden` | integer | No | 0=visible, 1=hidden (default: 0) |
| `pagenum` | integer | No | Position to insert chapter (0=append at end, default: 0) |
| `tags` | string | No | Comma-separated tags for the chapter |

**Example Request - Append Chapter:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_add_book_chapter" \
  -d "moodlewsrestformat=json" \
  -d "bookid=45" \
  -d "title=Advanced Topics" \
  -d "content=<h2>Advanced Programming Concepts</h2><p>In this chapter, we explore advanced topics...</p>" \
  -d "subchapter=0" \
  -d "tags=advanced,programming"
```

**Example Request - Insert at Position:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_add_book_chapter" \
  -d "moodlewsrestformat=json" \
  -d "bookid=45" \
  -d "title=Prerequisites" \
  -d "content=<p>Before starting, ensure you have...</p>" \
  -d "pagenum=1" \
  -d "subchapter=0"
```

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

**Book Structure Example:**

When creating a book with chapters, you can build a hierarchical structure using the `subchapter` parameter:

```
1. Main Chapter (subchapter=0)
   1.1 Subchapter (subchapter=1)
   1.2 Subchapter (subchapter=1)
2. Main Chapter (subchapter=0)
   2.1 Subchapter (subchapter=1)
3. Main Chapter (subchapter=0)
```

Subchapters are always grouped under the preceding main chapter in the table of contents.

---

## 9. Get Book

Retrieves complete book details including all chapters, subchapters, and their content in a single call.

**Function:** `local_activity_utils_get_book`

**Required Capability:** `local/activity_utils:readbook` and `mod/book:read`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `bookid` | integer | Yes | The book instance ID |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_get_book" \
  -d "moodlewsrestformat=json" \
  -d "bookid=45"
```

**Response:**
```json
{
  "id": 45,
  "coursemoduleid": 165,
  "courseid": 2,
  "coursename": "Introduction to Computer Science",
  "name": "Introduction to Programming",
  "intro": "<p>A comprehensive guide to programming basics</p>",
  "introformat": 1,
  "numbering": 1,
  "navstyle": 1,
  "customtitles": 0,
  "revision": 5,
  "timecreated": 1701360000,
  "timemodified": 1701446400,
  "chapters": [
    {
      "id": 101,
      "pagenum": 1,
      "subchapter": 0,
      "title": "Getting Started",
      "content": "<p>Welcome to the programming course!</p>",
      "contentformat": 1,
      "hidden": 0,
      "timecreated": 1701360000,
      "timemodified": 1701360000,
      "importsrc": "",
      "tags": []
    },
    {
      "id": 102,
      "pagenum": 2,
      "subchapter": 1,
      "title": "Installing Tools",
      "content": "<p>First, install the required software...</p>",
      "contentformat": 1,
      "hidden": 0,
      "timecreated": 1701360000,
      "timemodified": 1701360000,
      "importsrc": "",
      "tags": ["setup", "installation"]
    },
    {
      "id": 103,
      "pagenum": 3,
      "subchapter": 1,
      "title": "Your First Program",
      "content": "<p>Let's write our first program...</p>",
      "contentformat": 1,
      "hidden": 0,
      "timecreated": 1701360000,
      "timemodified": 1701360000,
      "importsrc": "",
      "tags": []
    }
  ],
  "success": true,
  "message": "Book retrieved successfully with 3 chapter(s)"
}
```

**Chapter Hierarchy:**
- Chapters are returned in order by `pagenum`
- `subchapter=0` indicates a main chapter
- `subchapter=1` indicates a subchapter (nested under the previous main chapter)
- Hidden chapters are only included if the user has edit permissions
- Each chapter includes full HTML content, tags, and metadata

**Use Cases:**
- Display complete book content in custom interfaces
- Export entire book with all chapters for backup or migration
- Build table of contents with chapter hierarchy
- Analyze book structure and content

---

## 10. Update Assignment

Updates an existing assignment in a Moodle course. Only provided fields are updated.

**Function:** `local_activity_utils_update_assignment`

**Required Capability:** `local/activity_utils:updateassignment` and `mod/assign:addinstance`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `assignmentid` | integer | Yes | The assignment ID to update |
| `name` | string | No | New assignment name/title |
| `intro` | string | No | New assignment description (supports HTML) |
| `activity` | string | No | New activity instructions (supports HTML) |
| `allowsubmissionsfromdate` | integer | No | New allow submissions from date (Unix timestamp) |
| `duedate` | integer | No | New due date (Unix timestamp) |
| `cutoffdate` | integer | No | New cut-off date (Unix timestamp) |
| `idnumber` | string | No | New ID number for gradebook |
| `grademax` | integer | No | New maximum grade |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_update_assignment" \
  -d "moodlewsrestformat=json" \
  -d "assignmentid=45" \
  -d "name=Updated Assignment Title" \
  -d "duedate=1735776000"
```

**Response:**
```json
{
  "id": 45,
  "coursemoduleid": 123,
  "name": "Updated Assignment Title",
  "success": true,
  "message": "Assignment updated successfully"
}
```

---

## 11. Update Page

Updates an existing page activity in a Moodle course.

**Function:** `local_activity_utils_update_page`

**Required Capability:** `local/activity_utils:updatepage` and `mod/page:addinstance`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `pageid` | integer | Yes | The page ID to update |
| `name` | string | No | New page name/title |
| `intro` | string | No | New page introduction (supports HTML) |
| `content` | string | No | New page content (supports HTML) |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_update_page" \
  -d "moodlewsrestformat=json" \
  -d "pageid=28" \
  -d "name=Updated Syllabus" \
  -d "content=<h1>Updated Course Syllabus</h1><p>New content here...</p>"
```

**Response:**
```json
{
  "id": 28,
  "coursemoduleid": 145,
  "name": "Updated Syllabus",
  "success": true,
  "message": "Page updated successfully"
}
```

---

## 12. Update File Resource

Updates an existing file resource in a Moodle course. Can update metadata, replace file content, or rename the file.

**Function:** `local_activity_utils_update_file`

**Required Capability:** `local/activity_utils:updatefile` and `mod/resource:addinstance`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `resourceid` | integer | Yes | The resource ID to update |
| `name` | string | No | New file resource name/title |
| `intro` | string | No | New introduction/description (supports HTML) |
| `filename` | string | No | New file name (requires filecontent for full replacement) |
| `filecontent` | string | No | New file content (base64 encoded) |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden |

**Example Request - Update metadata:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_update_file" \
  -d "moodlewsrestformat=json" \
  -d "resourceid=32" \
  -d "name=Updated Course Textbook" \
  -d "intro=<p>Download the updated textbook</p>"
```

**Example Request - Replace file:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_update_file" \
  -d "moodlewsrestformat=json" \
  -d "resourceid=32" \
  -d "filename=textbook_v2.pdf" \
  -d "filecontent=JVBERi0xLjQKJeLjz9MKMSAwIG9iago8..."
```

**Response:**
```json
{
  "id": 32,
  "coursemoduleid": 150,
  "name": "Updated Course Textbook",
  "filename": "textbook_v2.pdf",
  "success": true,
  "message": "File resource updated successfully"
}
```

---

## 13. Update Book

Updates an existing book resource in a Moodle course.

**Function:** `local_activity_utils_update_book`

**Required Capability:** `local/activity_utils:updatebook` and `mod/book:edit`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `bookid` | integer | Yes | The book ID to update |
| `name` | string | No | New book name/title |
| `intro` | string | No | New book introduction (supports HTML) |
| `numbering` | integer | No | Chapter numbering style (0=none, 1=numbers, 2=bullets, 3=indented) |
| `navstyle` | integer | No | Navigation style (0=none, 1=images, 2=text) |
| `customtitles` | integer | No | Use custom titles: 0=no, 1=yes |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_update_book" \
  -d "moodlewsrestformat=json" \
  -d "bookid=45" \
  -d "name=Updated Programming Guide" \
  -d "numbering=2"
```

**Response:**
```json
{
  "id": 45,
  "coursemoduleid": 165,
  "name": "Updated Programming Guide",
  "success": true,
  "message": "Book updated successfully"
}
```

---

## 14. Update Book Chapter

Updates an existing chapter in a book resource.

**Function:** `local_activity_utils_update_book_chapter`

**Required Capability:** `local/activity_utils:updatebook` and `mod/book:edit`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `chapterid` | integer | Yes | The chapter ID to update |
| `title` | string | No | New chapter title |
| `content` | string | No | New chapter content (supports HTML) |
| `subchapter` | integer | No | Is subchapter: 0=main chapter, 1=subchapter |
| `hidden` | integer | No | Hidden: 0=visible, 1=hidden |
| `tags` | string | No | Comma-separated tags (empty string to clear all tags) |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_update_book_chapter" \
  -d "moodlewsrestformat=json" \
  -d "chapterid=101" \
  -d "title=Updated Chapter Title" \
  -d "content=<p>Updated chapter content...</p>" \
  -d "tags=programming,basics,updated"
```

**Response:**
```json
{
  "id": 101,
  "bookid": 45,
  "pagenum": 1,
  "title": "Updated Chapter Title",
  "subchapter": 0,
  "success": true,
  "message": "Chapter updated successfully"
}
```

---

## 15. Update Section

Updates an existing course section.

**Function:** `local_activity_utils_update_section`

**Required Capability:** `local/activity_utils:updatesection` and `moodle/course:update`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `sectionid` | integer | Yes | The section ID to update |
| `name` | string | No | New section name |
| `summary` | string | No | New section summary (supports HTML) |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_update_section" \
  -d "moodlewsrestformat=json" \
  -d "sectionid=15" \
  -d "name=Week 1: Updated Introduction" \
  -d "summary=<p>Updated section description</p>"
```

**Response:**
```json
{
  "id": 15,
  "sectionnum": 1,
  "name": "Week 1: Updated Introduction",
  "success": true,
  "message": "Section updated successfully"
}
```

---

## 16. Update Subsection

Updates an existing subsection (delegated section).

**Function:** `local_activity_utils_update_subsection`

**Required Capability:** `local/activity_utils:updatesubsection` and `moodle/course:update`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `sectionid` | integer | Yes | The subsection section ID to update |
| `name` | string | No | New subsection name |
| `summary` | string | No | New subsection summary (supports HTML) |
| `visible` | integer | No | Visibility: 1=visible, 0=hidden |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_activity_utils_update_subsection" \
  -d "moodlewsrestformat=json" \
  -d "sectionid=20" \
  -d "name=Week 1.1: Updated Getting Started" \
  -d "visible=0"
```

**Response:**
```json
{
  "id": 20,
  "sectionnum": 5,
  "name": "Week 1.1: Updated Getting Started",
  "success": true,
  "message": "Subsection updated successfully"
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

### Create Book
- `local/activity_utils:createbook` (granted to editing teachers and managers by default)
- `mod/book:addinstance` (standard Moodle capability)

### Add Book Chapter
- `local/activity_utils:createbook` (granted to editing teachers and managers by default)
- `mod/book:edit` (standard Moodle capability)

### Get Book
- `local/activity_utils:readbook` (granted to students, teachers, editing teachers, and managers by default)
- `mod/book:read` (standard Moodle capability)

### Update Assignment
- `local/activity_utils:updateassignment` (granted to editing teachers and managers by default)
- `mod/assign:addinstance` (standard Moodle capability)

### Update Page
- `local/activity_utils:updatepage` (granted to editing teachers and managers by default)
- `mod/page:addinstance` (standard Moodle capability)

### Update File
- `local/activity_utils:updatefile` (granted to editing teachers and managers by default)
- `mod/resource:addinstance` (standard Moodle capability)

### Update Book
- `local/activity_utils:updatebook` (granted to editing teachers and managers by default)
- `mod/book:edit` (standard Moodle capability)

### Update Book Chapter
- `local/activity_utils:updatebook` (granted to editing teachers and managers by default)
- `mod/book:edit` (standard Moodle capability)

### Update Section
- `local/activity_utils:updatesection` (granted to editing teachers and managers by default)
- `moodle/course:update` (standard Moodle capability)

### Update Subsection
- `local/activity_utils:updatesubsection` (granted to editing teachers and managers by default)
- `moodle/course:update` (standard Moodle capability)

---

## Activity Placement in Subsections

#### How It Works
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

#### Visibility Rules
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
```

---

## License

This plugin is developed for the Limkokwing University Registry Portal integration.

## Support

For issues or questions, please contact the development team or check the Moodle documentation.
