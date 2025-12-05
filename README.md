# Activity Utils

REST API endpoints for programmatic Moodle course content management.

**Version:** 2.12 | **Requirements:** Moodle 4.0+ | **Developed for:** Limkokwing University

## Features

34 web service functions:
- **Sections** (6): create, update, delete sections and subsections
- **Assignments** (3): create, update, delete
- **Pages** (3): create, update, delete
- **Files** (3): create, update, delete
- **URLs** (3): create, update, delete
- **Books** (6): create, update, delete, add/update chapters, get
- **Rubrics** (5): create, get, update, delete, copy
- **BigBlueButton** (3): create, update, delete
- **Forums** (2): create, delete

## Quick Setup

1. Copy plugin to `local/activity_utils`
2. Run Moodle upgrade via **Site Administration > Notifications**
3. Enable web services: **Site Administration > Advanced features**
4. Enable REST protocol: **Site Administration > Plugins > Web services > Manage protocols**
5. Create external service with plugin functions
6. Generate API token

## API Usage

```
POST https://yourmoodle.com/webservice/rest/server.php
```

| Parameter | Value |
|-----------|-------|
| `wstoken` | Your API token |
| `wsfunction` | Function name |
| `moodlewsrestformat` | `json` |

---

## Sections

### Create Section
`local_activity_utils_create_section`

| Parameter | Type | Required |
|-----------|------|----------|
| `courseid` | int | Yes |
| `name` | string | No |
| `summary` | string | No |
| `sectionnum` | int | No |

### Create Subsection
`local_activity_utils_create_subsection`

| Parameter | Type | Required |
|-----------|------|----------|
| `courseid` | int | Yes |
| `parentsection` | int | Yes |
| `name` | string | Yes |
| `summary` | string | No |
| `visible` | int | No |

**Note:** Use returned `sectionnum` when adding activities to the subsection.

### Update Section/Subsection
`local_activity_utils_update_section` / `local_activity_utils_update_subsection`

Parameters: `sectionid` (required), `name`, `summary`, `visible`

### Delete Section
`local_activity_utils_delete_section`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | int | Yes | Course ID |
| `sectionnum` | int | Yes | Section number to delete |

**Note:** Cannot delete section 0 (general section). Deletes all content within the section.

### Delete Subsection
`local_activity_utils_delete_subsection`

Parameters: `cmid` (course module ID)

---

## Assignments

### Create Assignment
`local_activity_utils_create_assignment`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | int | Yes | |
| `name` | string | Yes | |
| `intro` | string | No | Description (HTML) |
| `activity` | string | No | Instructions (HTML) |
| `allowsubmissionsfromdate` | int | No | Unix timestamp |
| `duedate` | int | No | Unix timestamp |
| `section` | int | No | Default: 0 |
| `idnumber` | string | No | Gradebook ID |
| `grademax` | int | No | Default: 100 |
| `introfiles` | string | No | JSON array (base64) |

### Update Assignment
`local_activity_utils_update_assignment`

Parameters: `assignmentid` (required), `name`, `intro`, `activity`, `allowsubmissionsfromdate`, `duedate`, `cutoffdate`, `idnumber`, `grademax`, `visible`

### Delete Assignment
`local_activity_utils_delete_assignment`

Parameters: `cmid` (course module ID)

---

## Pages

### Create Page
`local_activity_utils_create_page`

| Parameter | Type | Required |
|-----------|------|----------|
| `courseid` | int | Yes |
| `name` | string | Yes |
| `intro` | string | No |
| `content` | string | No |
| `section` | int | No |
| `visible` | int | No |

### Update Page
`local_activity_utils_update_page`

Parameters: `pageid` (required), `name`, `intro`, `content`, `visible`

### Delete Page
`local_activity_utils_delete_page`

Parameters: `cmid` (course module ID)

---

## Files

### Create File
`local_activity_utils_create_file`

| Parameter | Type | Required |
|-----------|------|----------|
| `courseid` | int | Yes |
| `name` | string | Yes |
| `intro` | string | No |
| `filename` | string | Yes |
| `filecontent` | string | Yes (base64) |
| `section` | int | No |
| `visible` | int | No |

### Update File
`local_activity_utils_update_file`

Parameters: `resourceid` (required), `name`, `intro`, `filename`, `filecontent`, `visible`

### Delete File
`local_activity_utils_delete_file`

Parameters: `cmid` (course module ID)

---

## URLs

### Create URL
`local_activity_utils_create_url`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | int | Yes | |
| `name` | string | Yes | |
| `externalurl` | string | Yes | The external URL |
| `intro` | string | No | Description (HTML) |
| `section` | int | No | Default: 0 |
| `visible` | int | No | Default: 1 |
| `display` | int | No | 0=auto, 1=embed, 2=frame, 5=open, 6=popup |

### Update URL
`local_activity_utils_update_url`

Parameters: `urlid` (required), `name`, `externalurl`, `intro`, `display`, `visible`

### Delete URL
`local_activity_utils_delete_url`

Parameters: `cmid` (course module ID)

---

## Books

### Create Book
`local_activity_utils_create_book`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | int | Yes | |
| `name` | string | Yes | |
| `intro` | string | No | |
| `section` | int | No | |
| `visible` | int | No | |
| `numbering` | int | No | 0=none, 1=numbers, 2=bullets, 3=indented |
| `navstyle` | int | No | 0=none, 1=images, 2=text |
| `customtitles` | int | No | |
| `chapters` | array | No | Chapter objects |

**Chapter object:** `title` (required), `content`, `subchapter` (0=main, 1=sub), `hidden`, `tags`

### Add Book Chapter
`local_activity_utils_add_book_chapter`

Parameters: `bookid` (required), `title` (required), `content`, `subchapter`, `hidden`, `pagenum`, `tags`

### Get Book
`local_activity_utils_get_book`

Parameters: `bookid`

### Update Book
`local_activity_utils_update_book`

Parameters: `bookid` (required), `name`, `intro`, `numbering`, `navstyle`, `customtitles`, `visible`

### Update Book Chapter
`local_activity_utils_update_book_chapter`

Parameters: `chapterid` (required), `title`, `content`, `subchapter`, `hidden`, `tags`

### Delete Book
`local_activity_utils_delete_book`

Parameters: `cmid` (course module ID)

---

## Rubrics

Simplified rubric management (cleaner than `core_grading_save_definitions`).

### Create Rubric
`local_activity_utils_create_rubric`

| Parameter | Type | Required |
|-----------|------|----------|
| `cmid` | int | Yes |
| `name` | string | Yes |
| `description` | string | No |
| `criteria` | array | Yes |
| `options` | object | No |

**Criterion:** `description` (required), `sortorder`, `levels` (required)
**Level:** `score` (required), `definition` (required)
**Options:** `sortlevelsasc`, `lockzeropoints`, `showdescriptionstudent`, `showdescriptionteacher`, `showscoreteacher`, `showscorestudent`, `enableremarks`, `showremarksstudent`

### Get Rubric
`local_activity_utils_get_rubric`

Parameters: `cmid`

### Update Rubric
`local_activity_utils_update_rubric`

Parameters: `cmid` (required), `name`, `description`, `criteria`, `options`

### Delete Rubric
`local_activity_utils_delete_rubric`

Parameters: `cmid`

### Copy Rubric
`local_activity_utils_copy_rubric`

Parameters: `sourcecmid`, `targetcmid`

---

## BigBlueButton

### Create BigBlueButton
`local_activity_utils_create_bigbluebuttonbn`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | int | Yes | |
| `name` | string | Yes | |
| `intro` | string | No | Description (HTML) |
| `section` | int | No | Default: 0 |
| `visible` | int | No | Default: 1 |
| `type` | int | No | 0=room+recordings, 1=room only, 2=recordings only |
| `welcome` | string | No | Welcome message |
| `voicebridge` | int | No | 4-digit voice bridge number |
| `wait` | int | No | Wait for moderator (1/0) |
| `userlimit` | int | No | Max participants (0=unlimited) |
| `record` | int | No | Enable recording (1/0) |
| `muteonstart` | int | No | Mute on start (1/0) |
| `disablecam` | int | No | Disable webcams (1/0) |
| `disablemic` | int | No | Disable microphones (1/0) |
| `disableprivatechat` | int | No | Disable private chat (1/0) |
| `disablepublicchat` | int | No | Disable public chat (1/0) |
| `disablenote` | int | No | Disable shared notes (1/0) |
| `hideuserlist` | int | No | Hide user list (1/0) |
| `openingtime` | int | No | Unix timestamp (0=no restriction) |
| `closingtime` | int | No | Unix timestamp (0=no restriction) |
| `guestallowed` | int | No | Allow guests (1/0) |
| `mustapproveuser` | int | No | Approve guests (1/0) |
| `recordings_deleted` | int | No | Show deleted recordings (1/0) |
| `recordings_imported` | int | No | Show imported recordings (1/0) |
| `recordings_preview` | int | No | Show preview (1/0) |
| `showpresentation` | int | No | Show presentation on page (1/0) |
| `completionattendance` | int | No | Required attendance minutes |
| `completionengagementchats` | int | No | Required chat messages |
| `completionengagementtalks` | int | No | Required talk time |
| `completionengagementraisehand` | int | No | Required raise hand count |
| `completionengagementpollvotes` | int | No | Required poll votes |
| `completionengagementemojis` | int | No | Required emoji count |

**Response:**
```json
{
  "id": 1,
  "coursemoduleid": 123,
  "meetingid": "unique-meeting-id",
  "name": "Weekly Meeting",
  "success": true,
  "message": "BigBlueButton activity created successfully"
}
```

### Update BigBlueButton
`local_activity_utils_update_bigbluebuttonbn`

Parameters: `bigbluebuttonbnid` (required), plus any field from create (all optional)

### Delete BigBlueButton
`local_activity_utils_delete_bigbluebuttonbn`

Parameters: `cmid` (course module ID)

---

## Forums

### Create Forum
`local_activity_utils_create_forum`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | int | Yes | |
| `name` | string | Yes | |
| `intro` | string | No | Forum description (HTML) |
| `type` | string | No | Forum type: general, news, social, eachuser, single, qanda, blog (default: general) |
| `section` | int | No | Default: 0 |
| `idnumber` | string | No | ID number |

**Forum Types:**
- `general` - Standard forum for general use
- `news` - News forum (announcements)
- `social` - Social forum for off-topic discussions
- `eachuser` - Each person posts one discussion
- `single` - A single simple discussion
- `qanda` - Q&A forum (students see other responses after posting)
- `blog` - Blog-like format

**Response:**
```json
{
  "id": 1,
  "coursemoduleid": 123,
  "name": "General Discussion",
  "success": true,
  "message": "Forum created successfully"
}
```

### Delete Forum
`local_activity_utils_delete_forum`

Parameters: `cmid` (course module ID)

---

## Examples

### Create Assignment
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=TOKEN&wsfunction=local_activity_utils_create_assignment&moodlewsrestformat=json" \
  -d "courseid=2&name=Week 1 Assignment&duedate=1735689600"
```

### Create BigBlueButton Session
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=TOKEN&wsfunction=local_activity_utils_create_bigbluebuttonbn&moodlewsrestformat=json" \
  -d "courseid=2&name=Live Class&type=0&record=1&wait=1&openingtime=1735689600"
```

### Create Forum
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=TOKEN&wsfunction=local_activity_utils_create_forum&moodlewsrestformat=json" \
  -d "courseid=2&name=General Discussion&type=general&section=0"
```

### Create Rubric
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=TOKEN&wsfunction=local_activity_utils_create_rubric&moodlewsrestformat=json" \
  -d "cmid=123&name=Essay Rubric" \
  -d "criteria[0][description]=Content&criteria[0][levels][0][score]=0&criteria[0][levels][0][definition]=Poor" \
  -d "criteria[0][levels][1][score]=10&criteria[0][levels][1][definition]=Excellent"
```

---

## Capabilities

All capabilities granted to **editing teachers** and **managers** by default.

| Capability | Description |
|------------|-------------|
| `local/activity_utils:createassignment` | Create assignments |
| `local/activity_utils:updateassignment` | Update assignments |
| `local/activity_utils:deleteassignment` | Delete assignments |
| `local/activity_utils:createsection` | Create sections |
| `local/activity_utils:updatesection` | Update sections |
| `local/activity_utils:deletesection` | Delete sections |
| `local/activity_utils:createsubsection` | Create subsections |
| `local/activity_utils:updatesubsection` | Update subsections |
| `local/activity_utils:deletesubsection` | Delete subsections |
| `local/activity_utils:createpage` | Create pages |
| `local/activity_utils:updatepage` | Update pages |
| `local/activity_utils:deletepage` | Delete pages |
| `local/activity_utils:createfile` | Create files |
| `local/activity_utils:updatefile` | Update files |
| `local/activity_utils:deletefile` | Delete files |
| `local/activity_utils:createurl` | Create URLs |
| `local/activity_utils:updateurl` | Update URLs |
| `local/activity_utils:deleteurl` | Delete URLs |
| `local/activity_utils:createbook` | Create books |
| `local/activity_utils:updatebook` | Update books |
| `local/activity_utils:deletebook` | Delete books |
| `local/activity_utils:readbook` | Read books (includes students) |
| `local/activity_utils:managerubric` | Manage rubrics |
| `local/activity_utils:createbigbluebuttonbn` | Create BigBlueButton |
| `local/activity_utils:updatebigbluebuttonbn` | Update BigBlueButton |
| `local/activity_utils:deletebigbluebuttonbn` | Delete BigBlueButton |
| `local/activity_utils:createforum` | Create forums |
| `local/activity_utils:deleteforum` | Delete forums |

---

## Notes

- **Subsection visibility:** Activities inherit visibility from parent subsection
- **File uploads:** Must be base64-encoded
- **Book chapters:** `subchapter=0` for main, `subchapter=1` for nested under previous main
- **BigBlueButton:** Requires mod_bigbluebuttonbn to be installed and enabled

---

## License

Developed for Limkokwing University Registry Portal integration.
