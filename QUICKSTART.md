# TFMS - Quick Setup Guide

## ⚡ Quick Start (5 Minutes)

### Step 1: Install XAMPP
- Download XAMPP from: https://www.apachefriends.org/
- Install with default settings
- Launch XAMPP Control Panel
- Start **Apache** and **MySQL**

### Step 2: Extract Project
- Extract the project to: `C:\xampp\htdocs\Teacher Faculty Management website\`
- Ensure all files and folders are present

### Step 3: Create Database
1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click on "Import" tab
3. Choose `database.sql` file from the project folder
4. Click "Go" to import
5. Database is created with all tables and sample data

### Step 4: Access Application
- Open browser and go to: `http://localhost/Teacher%20Faculty%20Management%20website/login.php`
- Or: `http://localhost/Teacher%20Faculty%20Management%20website/`

### Step 5: Login
- **Username**: `admin`
- **Password**: `admin123`

## 🎯 First Steps After Login

### As Admin:

1. **Explore Dashboard**
   - See system statistics
   - Overview of all data

2. **Add Time Slots** (if not already present)
   - Go to: Time Slots Management
   - Add standard class time slots (08:00-08:50, etc.)

3. **Add Classrooms**
   - Go to: Classrooms Management
   - Add your institution's classrooms
   - Include room number, capacity, equipment

4. **Add Subjects**
   - Go to: Subjects Management
   - Add all courses offered
   - Organize by department

5. **Add Teachers**
   - Go to: Teachers Management
   - Add teachers with login credentials
   - Assign department and qualifications

6. **Create Assignments**
   - Go to: Teacher Assignments
   - Assign subjects to teachers
   - Set academic year and semester

7. **Create Schedules**
   - Go to: Schedules Management
   - Create teacher-classroom-subject-time assignments
   - System prevents scheduling conflicts

### As Teacher:

1. **View Dashboard**
   - See personal statistics
   - Check assigned subjects

2. **View Schedule**
   - Go to: My Schedule
   - See all weekly classes
   - Check room assignments

3. **Check Current Assignment**
   - Go to: Current Assignment
   - See current class details (if in session)
   - Auto-updates every 30 seconds

## 📁 Important Files

```
login.php                       - Main login page
database.sql                    - Database schema (import this)
README.md                       - Full documentation
admin/dashboard.php             - Admin main page
admin/teachers.php              - Teacher management
admin/subjects.php              - Subject management
admin/classrooms.php            - Classroom management
admin/schedules.php             - Schedule management
teacher/dashboard.php           - Teacher main page
teacher/schedule.php            - Teacher schedule view
teacher/current.php             - Current assignment
config/Database.php             - Database configuration
```

## 🔧 Troubleshooting

### Can't access the site?
- Check XAMPP Apache is running (green indicator)
- Check MySQL is running (green indicator)
- Try: `http://localhost/phpmyadmin` to verify MySQL works

### Database import failed?
- Make sure MySQL is running in XAMPP
- Check you have internet connectivity
- Try importing in smaller chunks if file is large

### Login doesn't work?
- Check database was imported (should have 'users' table)
- Use credentials: admin / admin123
- Clear browser cache (Ctrl+Shift+Delete)

### Schedules show conflict but shouldn't?
- Check time slot times are correct
- Verify teacher/classroom aren't assigned elsewhere
- Check academic year matches

### Can't see teacher schedule?
- Make sure schedules are created for that teacher
- Check schedule status is 'active'
- Verify teacher has login access

## 🚀 Common Tasks

### Add a New Teacher
1. Admin Dashboard → Teachers Management
2. Click "Add New Teacher"
3. Fill form with details
4. Create username and password
5. Click "Add Teacher"
6. Teacher can now login

### Create a Class Schedule
1. Admin Dashboard → Schedules Management
2. Click "Create Schedule"
3. Select: Teacher, Subject, Classroom, Day, Time Slot
4. Click "Create Schedule"
5. System checks for conflicts automatically

### Assign Subject to Teacher
1. Admin Dashboard → Teacher Assignments
2. Click "Create Assignment"
3. Select Teacher and Subject
4. Set Academic Year (e.g., 2024)
5. Set Semester (1 or 2)
6. Click "Create Assignment"

### Search for a Teacher
1. Admin Dashboard → Teachers Management
2. Use search box to find by:
   - First/Last name
   - Email address
   - Department
3. Results filter automatically

## 📊 Sample Data Included

The database includes:
- 1 Admin user (admin/admin123)
- 5 Sample Classrooms (A101, A102, B101, B201, C101)
- 8 Time Slots (08:00-16:20)
- 5 Sample Subjects (CS, Math, Physics, English)
- 3 Sample Classes (CS-A, CS-B, MATH-A)

You can modify or delete these as needed.

## 🔐 Security Tips

1. **Change admin password immediately**
   - After first login, change password
   - Use strong passwords (8+ characters, mix of upper/lowercase/numbers)

2. **Create separate teacher accounts**
   - Don't share login credentials
   - Each teacher gets unique username/password

3. **Backup database regularly**
   - Use phpMyAdmin Export feature
   - Save backups in safe location

4. **Monitor access logs**
   - Check audit logs regularly
   - Review suspicious activities

## 📱 Mobile Access

The system is fully responsive:
- Access from tablet: `http://192.168.1.x/Teacher%20Faculty%20Management%20website/`
- (Replace x with your computer's IP address)
- Works on mobile browsers
- Touch-friendly interface

## 🆘 Need Help?

1. **Check README.md** - Full documentation with examples
2. **Review database.sql** - Table structure and relationships
3. **Check class files** - PHP classes with detailed comments
4. **Browser console** - Check for JavaScript errors (F12)
5. **PHP error logs** - Check XAMPP logs folder

## ✅ Verification Checklist

- [ ] XAMPP Apache running?
- [ ] XAMPP MySQL running?
- [ ] Project in htdocs folder?
- [ ] Database imported successfully?
- [ ] Can login with admin/admin123?
- [ ] Can see Dashboard with statistics?
- [ ] Can access Teachers Management?
- [ ] Can add new classroom?
- [ ] Can create schedule?

## 🎓 Next Steps

1. **Familiarize** with all admin features
2. **Add your institution's** data
3. **Test with teacher account** - create one and login
4. **Setup backup** schedule
5. **Train staff** on how to use system
6. **Go live** with real data

## 📞 Support

For issues:
1. Check browser console (F12 → Console)
2. Review error messages carefully
3. Check error logs in XAMPP folder
4. Ensure all files are properly extracted
5. Verify database connection

---

**Good luck with your TFMS implementation!** 🎉
