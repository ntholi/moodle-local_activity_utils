# Create Assign - Moodle Plugin

Minimal Moodle plugin for creating assignments via REST API.

## Installation

1. Copy the `local/createassign` folder to your Moodle installation's `local/` directory
2. Log in as admin and visit Site Administration > Notifications
3. Complete the plugin installation

## Configuration

### Enable Web Services
1. Go to Site Administration > Advanced features
2. Enable "Enable web services"

### Enable REST Protocol
1. Go to Site Administration > Plugins > Web services > Manage protocols
2. Enable "REST protocol"

### Create Web Service User
1. Create a dedicated user for API access or use existing user
2. Assign appropriate role with capability: local/createassign:createassessment

### Add Service Function
1. Go to Site Administration > Plugins > Web services > External services
2. Create a custom service or use an existing one
3. Click "Functions"
4. Add function: local_createassign_create_assessment

### Create Token
1. Go to Site Administration > Plugins > Web services > Manage tokens
2. Create token for the user and service

## API Usage

### Endpoint
```
POST https://yourmoodle.com/webservice/rest/server.php
```

### Parameters
- `wstoken`: Your web service token
- `wsfunction`: local_createassign_create_assessment
- `moodlewsrestformat`: json
- `courseid`: Course ID (integer)
- `name`: Assignment name (string)
- `intro`: Assignment description (string, optional)
- `duedate`: Due date unix timestamp (integer, optional)
- `cutoffdate`: Cut-off date unix timestamp (integer, optional)
- `section`: Course section number (integer, optional, default 0)

### Example cURL Request
```bash
curl -X POST "https://yourmoodle.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN_HERE" \
  -d "wsfunction=local_createassign_create_assessment" \
  -d "moodlewsrestformat=json" \
  -d "courseid=2" \
  -d "name=Weekly Assignment 1" \
  -d "intro=Complete the exercises" \
  -d "duedate=1735689600" \
  -d "section=0"
```

### Example Response
```json
{
  "id": 45,
  "coursemoduleid": 123,
  "name": "Weekly Assignment 1",
  "success": true,
  "message": "Assignment created successfully"
}
```

## Required Capabilities
- local/createassign:createassessment (assigned to editing teachers and managers by default)
- mod/assign:addinstance (standard Moodle capability)

## Troubleshooting
- Ensure web services are enabled
- Verify token has correct permissions
- Check user has capability in course context
- Verify course ID exists and section number is valid
