# Activity Utils

REST API endpoints for programmatic Moodle course content management.

**Version:** 2.13 | **Requirements:** Moodle 4.0+ | **Developed for:** Limkokwing University

## Features

55 web service functions:
- **Sections** (6): create, update, delete sections and subsections
- **Assignments** (3): create, update, delete
- **Pages** (3): create, update, delete
- **Files** (3): create, update, delete
- **URLs** (3): create, update, delete
- **Books** (6): create, update, delete, add/update chapters, get
- **Rubrics** (7): create, get, update, delete, copy, fill (grade), get filling
- **BigBlueButton** (3): create, update, delete
- **Forums** (2): create, delete
- **Quizzes** (10): create, update, delete, get, add/remove/reorder questions, update slots, add feedback, delete attempts
- **Questions** (9): create categories, create questions (multichoice, truefalse, shortanswer, essay, matching, numerical), delete, duplicate

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

### Fill Rubric (Grade Student)
`local_activity_utils_fill_rubric`

Grade a student's assignment submission by selecting levels for each rubric criterion.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cmid` | int | Yes | Course module ID of the assignment |
| `userid` | int | Yes | User ID of the student being graded |
| `fillings` | array | Yes | Array of rubric fillings |
| `overallremark` | string | No | Overall feedback/remark |

**Filling object:** `criterionid` (required), `levelid` (required), `remark` (optional feedback for the criterion)

**Example:**
```json
{
  "cmid": 123,
  "userid": 456,
  "fillings": [
    {
      "criterionid": 1,
      "levelid": 3,
      "remark": "Good work on this criterion"
    },
    {
      "criterionid": 2,
      "levelid": 5,
      "remark": "Excellent performance"
    }
  ],
  "overallremark": "Overall very good submission"
}
```

**Response:**
```json
{
  "instanceid": 789,
  "grade": 85.5,
  "success": true,
  "message": "Rubric filled and grade saved successfully"
}
```

### Get Rubric Filling
`local_activity_utils_get_rubric_filling`

Retrieve how a teacher graded a student using the rubric.

| Parameter | Type | Required |
|-----------|------|----------|
| `cmid` | int | Yes |
| `userid` | int | Yes |

**Response:**
```json
{
  "instanceid": 789,
  "grade": 85.5,
  "grader": "John Teacher",
  "graderid": 10,
  "timecreated": 1701234567,
  "timemodified": 1701234890,
  "fillings": [
    {
      "criterionid": 1,
      "criteriondescription": "Research Quality",
      "levelid": 3,
      "level": {
        "id": 3,
        "score": 8.0,
        "definition": "Good research with minor gaps"
      },
      "remark": "Good work on this criterion"
    }
  ],
  "success": true,
  "message": "Rubric filling retrieved successfully"
}
```

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

## Quizzes

### Create Quiz
`local_activity_utils_create_quiz`

Create a quiz with comprehensive settings for timing, grading, display, and security.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `courseid` | int | Yes | |
| `name` | string | Yes | |
| `intro` | string | No | Description (HTML) |
| `section` | int | No | Default: 0 |
| `idnumber` | string | No | ID number |
| **Timing** |
| `timeopen` | int | No | Open time (Unix timestamp) |
| `timeclose` | int | No | Close time (Unix timestamp) |
| `timelimit` | int | No | Time limit in seconds (0=no limit) |
| `overduehandling` | string | No | autosubmit, graceperiod, autoabandon |
| `graceperiod` | int | No | Grace period in seconds |
| **Grade** |
| `grademax` | int | No | Maximum grade (default: 10) |
| `gradepass` | float | No | Grade to pass |
| `grademethod` | int | No | 1=highest, 2=average, 3=first, 4=last |
| `gradecategory` | int | No | Grade category ID |
| **Layout** |
| `questionsperpage` | int | No | Questions per page (0=all, default: 1) |
| `navmethod` | string | No | free or seq |
| `shuffleanswers` | bool | No | Shuffle within questions |
| `preferredbehaviour` | string | No | Question behaviour (default: deferredfeedback) |
| **Attempts** |
| `attempts` | int | No | Allowed attempts (0=unlimited) |
| `attemptonlast` | bool | No | Each attempt builds on last |
| **Review Options** |
| `reviewattempt` | int | No | Review attempt bitmask |
| `reviewcorrectness` | int | No | Review correctness bitmask |
| `reviewmarks` | int | No | Review marks bitmask |
| `reviewspecificfeedback` | int | No | Review specific feedback bitmask |
| `reviewgeneralfeedback` | int | No | Review general feedback bitmask |
| `reviewrightanswer` | int | No | Review right answer bitmask |
| `reviewoverallfeedback` | int | No | Review overall feedback bitmask |
| **Display** |
| `showuserpicture` | int | No | 0=no, 1=small, 2=large |
| `showblocks` | bool | No | Show blocks during attempts |
| `decimalpoints` | int | No | Decimal places in grades |
| **Security** |
| `password` | string | No | Quiz password |
| `subnet` | string | No | Subnet restriction |
| `delay1` | int | No | Delay between 1st and 2nd attempt (sec) |
| `delay2` | int | No | Delay between later attempts (sec) |
| `browsersecurity` | string | No | -, securewindow, safebrowser |
| **Extra** |
| `canredoquestions` | bool | No | Allow redo within attempt |
| `completionattemptsexhausted` | bool | No | Require all attempts completed |
| `completionminattempts` | int | No | Minimum attempts required |
| `completionpass` | bool | No | Require passing grade |
| `visible` | bool | No | Visible on course page |
| `availability` | string | No | Availability JSON |

**Question Behaviours:** `deferredfeedback`, `adaptive`, `adaptivenopenalty`, `immediatefeedback`, `immediateadaptive`, `interactive`, `interactivecountback`

### Update Quiz
`local_activity_utils_update_quiz`

Parameters: `quizid` (required), plus any field from create (all optional)

### Delete Quiz
`local_activity_utils_delete_quiz`

Parameters: `cmid` (course module ID)

### Get Quiz
`local_activity_utils_get_quiz`

Retrieve complete quiz details including questions and feedback.

Parameters: `quizid`

**Response:**
```json
{
  "quiz": {
    "id": 123,
    "course": 2,
    "name": "Week 1 Quiz",
    "intro": "Test your knowledge",
    "timeopen": 1701234567,
    "timeclose": 1701321000,
    "grademax": 100,
    "sumgrades": 10.0,
    "questions": [
      {
        "slot": 1,
        "questionid": 456,
        "name": "Question 1",
        "qtype": "multichoice",
        "maxmark": 1.0,
        "page": 1
      }
    ],
    "feedback": [
      {
        "id": 1,
        "feedbacktext": "Excellent work!",
        "mingrade": 90.0,
        "maxgrade": 100.0
      }
    ]
  },
  "success": true,
  "message": "Quiz retrieved successfully"
}
```

### Add Question to Quiz
`local_activity_utils_add_question_to_quiz`

Add an existing question to a quiz.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `quizid` | int | Yes | Quiz ID |
| `questionid` | int | Yes | Question ID |
| `page` | int | No | Page number (default: 1) |
| `maxmark` | float | No | Max mark for this question |

### Remove Question from Quiz
`local_activity_utils_remove_question_from_quiz`

Parameters: `slotid` (quiz slot ID)

### Reorder Quiz Questions
`local_activity_utils_reorder_quiz_questions`

Reorder questions in a quiz.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `quizid` | int | Yes | Quiz ID |
| `slots` | array | Yes | Array of slot IDs in desired order |

### Update Quiz Slot
`local_activity_utils_update_quiz_slot`

Update individual question slot settings.

| Parameter | Type | Required |
|-----------|------|----------|
| `slotid` | int | Yes |
| `maxmark` | float | No |
| `page` | int | No |
| `requireprevious` | bool | No |

### Add Quiz Feedback
`local_activity_utils_add_quiz_feedback`

Add overall feedback based on grade range.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `quizid` | int | Yes | Quiz ID |
| `feedbacktext` | string | Yes | Feedback message (HTML) |
| `mingrade` | float | Yes | Minimum grade percentage |
| `maxgrade` | float | Yes | Maximum grade percentage |

### Delete Quiz Attempt
`local_activity_utils_delete_quiz_attempt`

Delete a student's quiz attempt.

Parameters: `attemptid`

---

## Questions

Create questions in question banks for use in quizzes.

### Create Question Category
`local_activity_utils_create_question_category`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `contextid` | int | Yes | Context ID (course or system) |
| `name` | string | Yes | Category name |
| `info` | string | No | Category description |
| `parent` | int | No | Parent category ID (0=top level) |
| `idnumber` | string | No | ID number |

### Create Multiple Choice Question
`local_activity_utils_create_question_multichoice`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `categoryid` | int | Yes | Question category ID |
| `name` | string | Yes | Question name |
| `questiontext` | string | Yes | Question text (HTML) |
| `questiontextformat` | int | No | Text format (default: HTML) |
| `defaultmark` | float | No | Default mark (default: 1.0) |
| `generalfeedback` | string | No | General feedback |
| `single` | bool | No | Single answer (true) or multiple (false) |
| `shuffleanswers` | bool | No | Shuffle answers |
| `answernumbering` | string | No | abc, ABCD, 123, iii, IIII, none |
| `correctfeedback` | string | No | Feedback for correct response |
| `partiallycorrectfeedback` | string | No | Feedback for partial |
| `incorrectfeedback` | string | No | Feedback for incorrect |
| `shownumcorrect` | bool | No | Show number correct |
| `answers` | string | Yes | JSON array of answer objects |
| `penalty` | float | No | Penalty factor (0-1) |
| `idnumber` | string | No | ID number |

**Answer object:** `{"text": "Answer text", "fraction": 1.0, "feedback": "Correct!"}`
- `fraction`: 1.0 for correct, 0 for incorrect, 0.5 for partial credit

### Create True/False Question
`local_activity_utils_create_question_truefalse`

Parameters: `categoryid`, `name`, `questiontext`, `correctanswer` (true/false), `truefeedback`, `falsefeedback`, plus standard question fields

### Create Short Answer Question
`local_activity_utils_create_question_shortanswer`

Parameters: `categoryid`, `name`, `questiontext`, `usecase` (case sensitive), `answers` (JSON array), plus standard question fields

### Create Essay Question
`local_activity_utils_create_question_essay`

Parameters: `categoryid`, `name`, `questiontext`, `responseformat` (editor, editorfilepicker, plain, monospaced, noinline), `responserequired`, `responsefieldlines`, `attachments`, `attachmentsrequired`, plus standard question fields

### Create Matching Question
`local_activity_utils_create_question_matching`

Parameters: `categoryid`, `name`, `questiontext`, `shuffleanswers`, `subquestions` (JSON array with question and answer pairs), plus standard question fields

**Subquestion:** `{"questiontext": "Question", "answertext": "Answer"}`

### Create Numerical Question
`local_activity_utils_create_question_numerical`

Parameters: `categoryid`, `name`, `questiontext`, `answers` (JSON array with answer and tolerance), plus standard question fields

**Answer:** `{"answer": 42.5, "tolerance": 0.1, "fraction": 1.0, "feedback": "Correct!"}`

### Delete Question
`local_activity_utils_delete_question`

Parameters: `questionid`

**Warning:** Cannot delete if question is used in any quiz.

### Duplicate Question
`local_activity_utils_duplicate_question`

Create a copy of an existing question.

| Parameter | Type | Required |
|-----------|------|----------|
| `questionid` | int | Yes |
| `categoryid` | int | No |
| `name` | string | No |

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

### Create Quiz
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=TOKEN&wsfunction=local_activity_utils_create_quiz&moodlewsrestformat=json" \
  -d "courseid=2&name=Week 1 Quiz&grademax=100&timelimit=3600&attempts=3"
```

### Create Multiple Choice Question
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=TOKEN&wsfunction=local_activity_utils_create_question_multichoice&moodlewsrestformat=json" \
  -d "categoryid=5&name=Question 1&questiontext=What is 2+2?" \
  -d 'answers=[{"text":"3","fraction":0,"feedback":"Incorrect"},{"text":"4","fraction":1,"feedback":"Correct!"}]'
```

### Add Question to Quiz
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=TOKEN&wsfunction=local_activity_utils_add_question_to_quiz&moodlewsrestformat=json" \
  -d "quizid=123&questionid=456&page=1&maxmark=2.0"
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
| `local/activity_utils:createquiz` | Create quizzes |
| `local/activity_utils:updatequiz` | Update quizzes |
| `local/activity_utils:deletequiz` | Delete quizzes |
| `local/activity_utils:viewquiz` | View quiz details |
| `local/activity_utils:managequizquestions` | Add/remove/reorder quiz questions |
| `local/activity_utils:managequizfeedback` | Manage quiz feedback |
| `local/activity_utils:managequizattempts` | Delete quiz attempts |
| `local/activity_utils:managequestioncategories` | Manage question categories |
| `local/activity_utils:createquestions` | Create questions |
| `local/activity_utils:deletequestions` | Delete questions |

---

## Notes

- **Subsection visibility:** Activities inherit visibility from parent subsection
- **File uploads:** Must be base64-encoded
- **Book chapters:** `subchapter=0` for main, `subchapter=1` for nested under previous main
- **BigBlueButton:** Requires mod_bigbluebuttonbn to be installed and enabled
- **Quizzes:** Questions must be created in question bank before adding to quiz
- **Question answers:** Use `fraction` from 0 to 1.0 to indicate correctness (1.0 = 100% correct)

---

## License

Developed for Limkokwing University Registry Portal integration.
