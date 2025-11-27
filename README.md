# Create Assign - Moodle Plugin

A Moodle plugin that extends Moodle functionality to allow the [Registry Portal](https://github.com/ntholi/registry-web) to create assignments using a REST API.

This plugin provides a simple web service endpoint for programmatically creating assignments in Moodle courses.

## Installation

1. Copy this folder to your Moodle installation's `local/` directory
2. Log in as admin and go to Site Administration > Notifications
3. Complete the installation

## Setup

**1. Enable Web Services**
- Site Administration > Advanced features
- Check "Enable web services"

**2. Enable REST Protocol**
- Site Administration > Plugins > Web services > Manage protocols
- Enable "REST protocol"

**3. Add the Service Function**
- Site Administration > Plugins > Web services > External services
- Create a new service or edit an existing one
- Click "Add functions"
- Add: `local_createassign_create_assessment`

**4. Create an API Token**
- Site Administration > Plugins > Web services > Manage tokens
- Create a token for your API user and service
- Save the token for use in API requests

## API Usage

**Endpoint:**
```
POST https://yourmoodle.com/webservice/rest/server.php
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `wstoken` | string | Yes | Your web service token |
| `wsfunction` | string | Yes | Must be `local_createassign_create_assessment` |
| `moodlewsrestformat` | string | Yes | Response format (`json`) |
| `courseid` | integer | Yes | The course ID where the assignment will be created |
| `name` | string | Yes | Assignment name/title |
| `intro` | string | No | Assignment description or instructions |
| `duedate` | integer | No | Due date (Unix timestamp) |
| `cutoffdate` | integer | No | Cut-off date (Unix timestamp) |
| `section` | integer | No | Course section number (default: 0) |

**Example Request:**
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_createassign_create_assessment" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Weekly Assignment" \
  -d "intro=Complete the exercises" \
  -d "duedate=1735689600"
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

## Permissions

The API user needs these capabilities:
- `local/createassign:createassessment` (granted to editing teachers and managers by default)
- `mod/assign:addinstance` (standard Moodle capability)

## Troubleshooting

- Ensure web services are enabled in Moodle
- Verify your token has the correct permissions
- Check that the user has the required capabilities in the course
- Confirm the course ID and section number are valid
