# TFMS - API Documentation

## Base URL
```
http://localhost/Teacher%20Faculty%20Management%20website/api/
```

## Authentication
All API endpoints require session authentication. Ensure user is logged in before making requests.

## Response Format
All responses are in JSON format:

### Success Response
```json
{
    "status": true,
    "message": "Operation successful",
    "data": {}
}
```

### Error Response
```json
{
    "status": false,
    "message": "Error description",
    "error": "specific_error_code"
}
```

## Teachers Endpoints

### Get All Teachers
```
GET /teachers
```

**Query Parameters:**
- `status` (optional): 'active', 'inactive', 'on_leave'
- `department` (optional): Filter by department

**Response:**
```json
{
    "status": true,
    "data": [
        {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "email": "john@example.com",
            "department": "Computer Science",
            "status": "active"
        }
    ]
}
```

### Get Teacher by ID
```
GET /teachers/{id}
```

**Response:**
```json
{
    "status": true,
    "data": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "123-456-7890",
        "department": "Computer Science",
        "qualification": "M.Tech",
        "hire_date": "2020-01-15",
        "bio": "...",
        "status": "active"
    }
}
```

### Create Teacher
```
POST /teachers
Content-Type: application/json

{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "123-456-7890",
    "department": "Computer Science",
    "qualification": "M.Tech",
    "hire_date": "2020-01-15",
    "bio": "...",
    "username": "johndoe",
    "password": "secure_password"
}
```

### Update Teacher
```
PUT /teachers/{id}
Content-Type: application/json

{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "123-456-7890",
    "department": "Computer Science",
    "status": "active"
}
```

### Delete Teacher
```
DELETE /teachers/{id}
```

## Subjects Endpoints

### Get All Subjects
```
GET /subjects
```

**Query Parameters:**
- `department` (optional): Filter by department
- `status` (optional): 'active' or 'inactive'

**Response:**
```json
{
    "status": true,
    "data": [
        {
            "id": 1,
            "subject_code": "CS101",
            "subject_name": "Introduction to Programming",
            "department": "Computer Science",
            "credits": 4,
            "status": "active"
        }
    ]
}
```

### Get Subject by ID
```
GET /subjects/{id}
```

### Create Subject
```
POST /subjects
Content-Type: application/json

{
    "subject_code": "CS101",
    "subject_name": "Introduction to Programming",
    "department": "Computer Science",
    "description": "...",
    "credits": 4
}
```

### Update Subject
```
PUT /subjects/{id}
Content-Type: application/json

{
    "subject_code": "CS101",
    "subject_name": "Introduction to Programming",
    "department": "Computer Science",
    "credits": 4,
    "status": "active"
}
```

### Delete Subject
```
DELETE /subjects/{id}
```

## Classrooms Endpoints

### Get All Classrooms
```
GET /classrooms
```

**Query Parameters:**
- `building` (optional): Filter by building
- `type` (optional): 'classroom', 'lab', 'seminar', 'auditorium'
- `status` (optional): 'active', 'maintenance', 'inactive'

**Response:**
```json
{
    "status": true,
    "data": [
        {
            "id": 1,
            "room_number": "A101",
            "room_name": "Room A101",
            "building": "Building A",
            "capacity": 45,
            "room_type": "classroom",
            "equipment": "Projector, Whiteboard",
            "floor": 1,
            "status": "active"
        }
    ]
}
```

### Get Classroom by ID
```
GET /classrooms/{id}
```

### Create Classroom
```
POST /classrooms
Content-Type: application/json

{
    "room_number": "A101",
    "room_name": "Room A101",
    "building": "Building A",
    "capacity": 45,
    "room_type": "classroom",
    "equipment": "Projector, Whiteboard",
    "floor": 1
}
```

### Update Classroom
```
PUT /classrooms/{id}
Content-Type: application/json

{
    "room_number": "A101",
    "room_name": "Room A101",
    "building": "Building A",
    "capacity": 45,
    "room_type": "classroom",
    "equipment": "Projector, Whiteboard",
    "floor": 1,
    "status": "active"
}
```

### Delete Classroom
```
DELETE /classrooms/{id}
```

## Schedules Endpoints

### Get All Schedules
```
GET /schedules
```

**Query Parameters:**
- `teacher_id` (optional): Filter by teacher
- `day` (optional): Filter by day of week
- `academic_year` (optional): Filter by academic year

**Response:**
```json
{
    "status": true,
    "data": [
        {
            "id": 1,
            "teacher_id": 1,
            "subject_id": 1,
            "classroom_id": 1,
            "day_of_week": "Monday",
            "time_slot_id": 1,
            "semester": "1",
            "academic_year": "2024",
            "status": "active",
            "teacher_name": "John Doe",
            "subject_name": "CS101",
            "room_number": "A101",
            "start_time": "08:00:00",
            "end_time": "08:50:00"
        }
    ]
}
```

### Get Schedule by ID
```
GET /schedules/{id}
```

### Create Schedule
```
POST /schedules
Content-Type: application/json

{
    "teacher_id": 1,
    "subject_id": 1,
    "classroom_id": 1,
    "day_of_week": "Monday",
    "time_slot_id": 1,
    "semester": "1",
    "academic_year": "2024"
}
```

**Note:** System automatically checks for scheduling conflicts

### Update Schedule
```
PUT /schedules/{id}
Content-Type: application/json

{
    "teacher_id": 1,
    "subject_id": 1,
    "classroom_id": 1,
    "day_of_week": "Monday",
    "time_slot_id": 1,
    "status": "active"
}
```

### Delete Schedule
```
DELETE /schedules/{id}
```

## Time Slots Endpoints

### Get All Time Slots
```
GET /time-slots
```

**Response:**
```json
{
    "status": true,
    "data": [
        {
            "id": 1,
            "slot_name": "Slot 1",
            "start_time": "08:00:00",
            "end_time": "08:50:00",
            "status": "active"
        }
    ]
}
```

### Get Time Slot by ID
```
GET /time-slots/{id}
```

### Create Time Slot
```
POST /time-slots
Content-Type: application/json

{
    "slot_name": "Slot 1",
    "start_time": "08:00:00",
    "end_time": "08:50:00"
}
```

### Update Time Slot
```
PUT /time-slots/{id}
Content-Type: application/json

{
    "slot_name": "Slot 1",
    "start_time": "08:00:00",
    "end_time": "08:50:00",
    "status": "active"
}
```

### Delete Time Slot
```
DELETE /time-slots/{id}
```

## Teacher Assignments Endpoints

### Get All Assignments
```
GET /assignments
```

**Response:**
```json
{
    "status": true,
    "data": [
        {
            "id": 1,
            "teacher_id": 1,
            "subject_id": 1,
            "academic_year": "2024",
            "semester": "1",
            "status": "active",
            "teacher_name": "John Doe",
            "subject_name": "CS101"
        }
    ]
}
```

### Create Assignment
```
POST /assignments
Content-Type: application/json

{
    "teacher_id": 1,
    "subject_id": 1,
    "academic_year": "2024",
    "semester": "1"
}
```

### Delete Assignment
```
DELETE /assignments/{id}
```

## Search Endpoints

### Search Teachers
```
GET /search/teachers?q=keyword
```

**Query Parameters:**
- `q`: Search keyword (name, email, department)

### Search Subjects
```
GET /search/subjects?q=keyword
```

### Search Classrooms
```
GET /search/classrooms?q=keyword
```

## Statistics Endpoints

### Get Dashboard Statistics
```
GET /statistics
```

**Response:**
```json
{
    "status": true,
    "data": {
        "total_teachers": 15,
        "active_teachers": 12,
        "total_subjects": 25,
        "total_classrooms": 20,
        "total_schedules": 80,
        "total_assignments": 50
    }
}
```

### Get Teacher Statistics
```
GET /statistics/teacher/{id}
```

**Response:**
```json
{
    "status": true,
    "data": {
        "total_subjects": 3,
        "total_schedules": 12,
        "total_classrooms": 4,
        "classes_today": 2,
        "current_assignment": null
    }
}
```

## Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 400 | Invalid request | Missing or invalid parameters |
| 401 | Unauthorized | User not logged in |
| 403 | Forbidden | User doesn't have permission |
| 404 | Not found | Resource doesn't exist |
| 409 | Conflict | Schedule conflict detected |
| 500 | Server error | Internal server error |

## Rate Limiting

No rate limiting is currently implemented. Consider adding for production:
- Limit: 100 requests per minute per IP
- Headers: X-RateLimit-Limit, X-RateLimit-Remaining

## CORS

CORS is not enabled. Enable if needed for cross-origin requests:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
```

## Examples

### Using cURL

```bash
# Get all teachers
curl -X GET "http://localhost/Teacher%20Faculty%20Management%20website/api/teachers"

# Create a teacher
curl -X POST "http://localhost/Teacher%20Faculty%20Management%20website/api/teachers" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "password": "secure_password"
  }'
```

### Using JavaScript/Fetch

```javascript
// Get all teachers
fetch('http://localhost/Teacher%20Faculty%20Management%20website/api/teachers')
  .then(response => response.json())
  .then(data => console.log(data));

// Create a teacher
fetch('http://localhost/Teacher%20Faculty%20Management%20website/api/teachers', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com',
    username: 'johndoe',
    password: 'secure_password'
  })
})
  .then(response => response.json())
  .then(data => console.log(data));
```

## Webhooks (Future)

Coming in version 2.0:
- Schedule change notifications
- Teacher assignment alerts
- Room availability updates

---

**API Version**: 1.0.0  
**Last Updated**: January 2026
