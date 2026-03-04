# TFMS - Database Schema Documentation

## Database Diagram (ERD)

```
┌──────────────────┐
│     users        │
├──────────────────┤
│ id (PK)          │
│ username         │
│ email            │
│ password         │
│ role             │
│ status           │
│ created_at       │
│ updated_at       │
└────────┬─────────┘
         │
         │ 1:1
         │
         ▼
┌──────────────────┐
│    teachers      │
├──────────────────┤
│ id (PK)          │
│ user_id (FK)     │
│ first_name       │
│ last_name        │
│ email            │
│ phone            │
│ department       │
│ qualification    │
│ hire_date        │
│ bio              │
│ profile_image    │
│ status           │
│ created_at       │
│ updated_at       │
└────┬────────┬────┘
     │        │
     │ 1:N    │ 1:N
     │        │
     ▼        ▼
┌──────────────────────┐  ┌──────────────────┐
│ teacher_subjects     │  │    schedules     │
├──────────────────────┤  ├──────────────────┤
│ id (PK)              │  │ id (PK)          │
│ teacher_id (FK)      │  │ teacher_id (FK)  │
│ subject_id (FK)      │  │ subject_id (FK)  │
│ academic_year        │  │ classroom_id (FK)│
│ semester             │  │ time_slot_id (FK)│
│ status               │  │ day_of_week      │
│ created_at           │  │ semester         │
│ updated_at           │  │ academic_year    │
└──────────────────────┘  │ status           │
                         │ created_at       │
                         │ updated_at       │
                         └────┬────────┬────┘
                              │        │
                              │ FK     │ FK
                              │        │
                              ▼        ▼
                        ┌──────────────────┐
                        │    subjects      │
                        ├──────────────────┤
                        │ id (PK)          │
                        │ subject_code     │
                        │ subject_name     │
                        │ department       │
                        │ description      │
                        │ credits          │
                        │ status           │
                        │ created_at       │
                        │ updated_at       │
                        └──────────────────┘
                        
                    ┌──────────────────┐
                    │   classrooms     │
                    ├──────────────────┤
                    │ id (PK)          │
                    │ room_number      │
                    │ room_name        │
                    │ building         │
                    │ capacity         │
                    │ room_type        │
                    │ equipment        │
                    │ floor            │
                    │ status           │
                    │ created_at       │
                    │ updated_at       │
                    └──────────────────┘
                    
                ┌──────────────────┐
                │   time_slots     │
                ├──────────────────┤
                │ id (PK)          │
                │ slot_name        │
                │ start_time       │
                │ end_time         │
                │ status           │
                │ created_at       │
                └──────────────────┘
```

## Table Details

### users
User accounts for system access.

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | User ID |
| username | VARCHAR(100) | No | UNIQUE | Login username |
| email | VARCHAR(100) | No | UNIQUE | User email |
| password | VARCHAR(255) | No | | Hashed password |
| role | ENUM | No | | admin, teacher, student |
| status | ENUM | No | | active, inactive |
| created_at | TIMESTAMP | No | | Creation timestamp |
| updated_at | TIMESTAMP | No | | Update timestamp |

**Indexes**: username, email

### teachers
Teacher profile information.

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | Teacher ID |
| user_id | INT | No | FK | Reference to users table |
| first_name | VARCHAR(100) | No | | Teacher first name |
| last_name | VARCHAR(100) | No | | Teacher last name |
| email | VARCHAR(100) | No | UNIQUE | Teacher email |
| phone | VARCHAR(20) | Yes | | Phone number |
| department | VARCHAR(100) | Yes | | Department |
| qualification | VARCHAR(150) | Yes | | Educational qualification |
| hire_date | DATE | Yes | | Employment date |
| bio | TEXT | Yes | | Biography |
| profile_image | VARCHAR(255) | Yes | | Profile image path |
| status | ENUM | No | | active, inactive, on_leave |
| created_at | TIMESTAMP | No | | Creation timestamp |
| updated_at | TIMESTAMP | No | | Update timestamp |

**Indexes**: user_id, department, status

**Foreign Keys**: user_id → users(id)

### subjects
Subject/course information.

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | Subject ID |
| subject_code | VARCHAR(50) | No | UNIQUE | Subject code (e.g., CS101) |
| subject_name | VARCHAR(150) | No | | Subject name |
| department | VARCHAR(100) | Yes | | Department |
| description | TEXT | Yes | | Subject description |
| credits | INT | Yes | | Credit hours (default: 3) |
| status | ENUM | No | | active, inactive |
| created_at | TIMESTAMP | No | | Creation timestamp |
| updated_at | TIMESTAMP | No | | Update timestamp |

**Indexes**: subject_code, department

### classrooms
Classroom/room information.

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | Classroom ID |
| room_number | VARCHAR(50) | No | UNIQUE | Room number |
| room_name | VARCHAR(100) | No | | Room name |
| building | VARCHAR(100) | Yes | | Building name |
| capacity | INT | Yes | | Student capacity |
| room_type | ENUM | No | | classroom, lab, seminar, auditorium |
| equipment | VARCHAR(255) | Yes | | Available equipment |
| floor | INT | Yes | | Floor number |
| status | ENUM | No | | active, maintenance, inactive |
| created_at | TIMESTAMP | No | | Creation timestamp |
| updated_at | TIMESTAMP | No | | Update timestamp |

**Indexes**: room_number, building

### time_slots
Class time slot definitions.

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | Time slot ID |
| slot_name | VARCHAR(50) | No | UNIQUE | Slot name (e.g., Slot 1) |
| start_time | TIME | No | | Start time |
| end_time | TIME | No | | End time |
| status | ENUM | No | | active, inactive |
| created_at | TIMESTAMP | No | | Creation timestamp |

**No Foreign Keys**

### schedules
Teacher-classroom-subject-time assignments (Timetable).

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | Schedule ID |
| teacher_id | INT | No | FK | Reference to teachers |
| subject_id | INT | No | FK | Reference to subjects |
| classroom_id | INT | No | FK | Reference to classrooms |
| day_of_week | ENUM | No | | Monday-Sunday |
| time_slot_id | INT | No | FK | Reference to time_slots |
| semester | VARCHAR(20) | Yes | | Semester (1, 2, etc.) |
| academic_year | VARCHAR(20) | Yes | | Academic year (2024, etc.) |
| status | ENUM | No | | active, inactive, cancelled |
| notes | TEXT | Yes | | Notes |
| created_at | TIMESTAMP | No | | Creation timestamp |
| updated_at | TIMESTAMP | No | | Update timestamp |

**Indexes**: teacher_id, classroom_id, day_of_week/time_slot_id, academic_year/semester

**Foreign Keys**: 
- teacher_id → teachers(id)
- subject_id → subjects(id)
- classroom_id → classrooms(id)
- time_slot_id → time_slots(id)

**Unique Constraint**: (teacher_id, classroom_id, day_of_week, time_slot_id, academic_year)

### teacher_subjects
Teacher-subject assignment tracking.

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | Assignment ID |
| teacher_id | INT | No | FK | Reference to teachers |
| subject_id | INT | No | FK | Reference to subjects |
| academic_year | VARCHAR(20) | Yes | | Academic year |
| semester | VARCHAR(20) | Yes | | Semester |
| status | ENUM | No | | active, inactive |
| created_at | TIMESTAMP | No | | Creation timestamp |
| updated_at | TIMESTAMP | No | | Update timestamp |

**Foreign Keys**:
- teacher_id → teachers(id)
- subject_id → subjects(id)

**Unique Constraint**: (teacher_id, subject_id, academic_year)

### classes
Class/section information.

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | Class ID |
| class_name | VARCHAR(100) | No | | Class name (e.g., CS-A) |
| class_code | VARCHAR(50) | No | UNIQUE | Class code |
| department | VARCHAR(100) | Yes | | Department |
| semester | INT | Yes | | Semester level |
| strength | INT | Yes | | Number of students |
| status | ENUM | No | | active, inactive |
| created_at | TIMESTAMP | No | | Creation timestamp |
| updated_at | TIMESTAMP | No | | Update timestamp |

**Indexes**: class_code

### class_assignments
Class-subject-teacher assignment mapping.

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | Assignment ID |
| class_id | INT | No | FK | Reference to classes |
| subject_id | INT | No | FK | Reference to subjects |
| teacher_id | INT | No | FK | Reference to teachers |
| semester | VARCHAR(20) | Yes | | Semester |
| academic_year | VARCHAR(20) | Yes | | Academic year |
| status | ENUM | No | | active, inactive |
| created_at | TIMESTAMP | No | | Creation timestamp |
| updated_at | TIMESTAMP | No | | Update timestamp |

**Foreign Keys**:
- class_id → classes(id)
- subject_id → subjects(id)
- teacher_id → teachers(id)

**Unique Constraint**: (class_id, subject_id, academic_year)

**Indexes**: academic_year

### audit_logs
System activity logging.

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| id | INT | No | PK | Log ID |
| user_id | INT | Yes | FK | Reference to users |
| action | VARCHAR(100) | Yes | | Action performed |
| table_name | VARCHAR(100) | Yes | | Table affected |
| record_id | INT | Yes | | Record ID |
| old_values | LONGTEXT | Yes | | Previous values (JSON) |
| new_values | LONGTEXT | Yes | | New values (JSON) |
| ip_address | VARCHAR(45) | Yes | | IP address |
| created_at | TIMESTAMP | No | | Timestamp |

**Foreign Keys**: user_id → users(id)

## Relationships

### One-to-One (1:1)
- users → teachers: One user has one teacher profile

### One-to-Many (1:N)
- teachers → schedules: One teacher can have multiple schedules
- teachers → teacher_subjects: One teacher can teach multiple subjects
- subjects → schedules: One subject can have multiple schedules
- classrooms → schedules: One classroom can have multiple schedules
- time_slots → schedules: One time slot can have multiple schedules
- classes → class_assignments: One class can have multiple assignments

### Many-to-Many (M:N)
- teachers ↔ subjects (via teacher_subjects): Teachers teach subjects
- classes ↔ subjects ↔ teachers (via class_assignments): Classes have subjects taught by teachers

## Constraints

### NOT NULL Constraints
- All ID fields
- user table: username, email, password, role, status
- teacher table: first_name, last_name, email, status
- subject table: subject_code, subject_name, status
- classroom table: room_number, room_name, capacity, room_type, status
- time_slots table: slot_name, start_time, end_time, status
- schedules table: teacher_id, subject_id, classroom_id, day_of_week, time_slot_id, status

### UNIQUE Constraints
- users: username, email
- teachers: email
- subjects: subject_code
- classrooms: room_number
- time_slots: slot_name
- teacher_subjects: (teacher_id, subject_id, academic_year)
- schedules: (teacher_id, classroom_id, day_of_week, time_slot_id, academic_year)
- classes: class_code
- class_assignments: (class_id, subject_id, academic_year)

### FOREIGN KEY Constraints
All foreign keys use ON DELETE CASCADE or ON DELETE RESTRICT as appropriate

## Indexing Strategy

**Search Optimization**:
- Indexed columns for quick lookups: username, email, department, status
- Composite indexes for common filter combinations

**Query Optimization**:
- Indexes on frequently joined columns
- Indexes on ORDER BY columns
- Indexes on filter columns

## Query Examples

### Find teacher's schedule
```sql
SELECT s.*, ts.slot_name, sub.subject_name, c.room_number
FROM schedules s
JOIN time_slots ts ON s.time_slot_id = ts.id
JOIN subjects sub ON s.subject_id = sub.id
JOIN classrooms c ON s.classroom_id = c.id
WHERE s.teacher_id = ? AND s.status = 'active'
ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', ...), ts.start_time;
```

### Find current class
```sql
SELECT * FROM schedules
WHERE teacher_id = ? 
AND day_of_week = DAYNAME(NOW())
AND TIME(NOW()) BETWEEN start_time AND end_time
AND status = 'active';
```

### Check schedule conflicts
```sql
SELECT COUNT(*) FROM schedules
WHERE (teacher_id = ? OR classroom_id = ?)
AND day_of_week = ?
AND time_slot_id = ?
AND status = 'active';
```

### Get teacher assignments
```sql
SELECT s.*, ts.start_time, ts.end_time, sub.subject_name, c.room_name
FROM schedules s
JOIN time_slots ts ON s.time_slot_id = ts.id
JOIN subjects sub ON s.subject_id = sub.id
JOIN classrooms c ON s.classroom_id = c.id
WHERE s.teacher_id = ?
ORDER BY FIELD(s.day_of_week, ...), ts.start_time;
```

## Performance Considerations

1. **Indexing**: All foreign keys and frequently searched columns are indexed
2. **Query Optimization**: Use of JOIN instead of subqueries
3. **Pagination**: Implement for large result sets
4. **Caching**: Cache time slots and classroom lists
5. **Archive**: Consider archiving old schedules

## Backup & Recovery

- Regular automated backups recommended
- Point-in-time recovery capability
- Test restore procedures regularly
- Monitor log growth

---

**Database Version**: 1.0.0  
**MySQL Version**: 5.7+  
**Last Updated**: January 2026
