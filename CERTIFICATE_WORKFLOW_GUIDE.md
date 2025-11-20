# Certificate Generation Workflow - Complete Guide

## Overview
This document outlines the complete certificate generation workflow implemented for the GICT Institute system. The workflow allows admin users to manage the entire process from enrollment approval to certificate generation.

## Workflow Steps

### 1. **Enrollment** 
- **Who**: Students
- **Action**: Students enroll in sub-courses and select payment method
- **Status**: `payment_pending` (waiting for payment verification)
- **Location**: `student/enroll.php`

### 2. **Payment Verification** 
- **Who**: Admin Users
- **Action**: Admin reviews payment details and verifies payment
- **Status**: Changes from `payment_pending` to `enrolled` or `rejected`
- **Location**: `admin/enrollment-approvals.php`

### 3. **Course Completion**
- **Who**: Admin Users
- **Action**: Admin marks course as completed after course duration
- **Status**: Changes from `enrolled` to `completed`
- **Location**: `admin/marks-management.php` (after all marks are entered)

### 4. **Marks Entry**
- **Who**: Admin Users
- **Action**: Admin enters marks for each subject in the completed course
- **Status**: Marks stored in `student_marks` table
- **Location**: `admin/marks-management.php`

### 5. **Certificate Generation**
- **Who**: Admin Users
- **Action**: Admin generates certificate and marksheet for completed courses
- **Status**: Certificate created and stored
- **Location**: `admin/certificate-management.php`

### 6. **Student Access**
- **Who**: Students
- **Action**: Students can view and download their certificates and marksheets
- **Status**: Certificates available in student dashboard
- **Location**: `student/dashboard.php`

## Database Tables

### New Tables Created:
1. **`course_subjects`** - Tracks subjects for each sub-course
2. **`student_marks`** - Stores student marks for each subject
3. **`certificates`** - Stores generated certificate information
4. **`faculty_courses`** - Assigns courses to faculty members

### Updated Tables:
1. **`student_enrollments`** - Added `payment_pending` status for enrollment workflow
2. **`payments`** - Added payment verification fields and tracking
3. **`payment_methods`** - New table for available payment options
4. **`payment_verification`** - New table for payment verification tracking

## Admin Interface Features

### 1. Payment Verification & Enrollment Management (`admin/enrollment-approvals.php`)
- View all payment_pending enrollments
- Verify payments and approve enrollments
- Reject payments and cancel enrollments
- View payment details (transaction ID, payment method, notes)
- Track enrollment and payment statistics

### 2. Marks Management (`admin/marks-management.php`)
- Select students from all courses
- Enter marks for each subject
- Automatic grade calculation (A+, A, B+, B, C, D, F)
- Complete courses after all marks are entered

### 3. Certificate Management (`admin/certificate-management.php`)
- View completed courses
- Generate certificates and marksheets
- Download generated certificates
- Track certificate generation statistics

## Student Interface Features

### 1. Enhanced Dashboard (`student/dashboard.php`)
- View earned certificates
- Download certificates and marksheets
- Track course completion status
- Certificate count display

## Key Features

### 1. **Role-Based Access Control**
- Faculty can only manage courses assigned to them
- Students can only view their own certificates
- Secure authentication and authorization

### 2. **Automatic Grade Calculation**
- Grades calculated based on marks percentage:
  - 90%+ = A+
  - 80-89% = A
  - 70-79% = B+
  - 60-69% = B
  - 50-59% = C
  - 40-49% = D
  - <40% = F

### 3. **Certificate Numbering**
- Format: `GICT-YYYY-XXXXXX`
- Example: `GICT-2024-000001`

### 4. **File Management**
- Certificates stored in `assets/generated_certificates/`
- Marksheets stored in `assets/generated_marksheets/`
- Automatic directory creation

## Installation & Setup

### 1. Database Setup
```bash
php setup_certificate_workflow.php
```

### 2. Faculty Course Assignment
- Assign courses to faculty through `faculty_courses` table
- Faculty can only manage assigned courses

### 3. Course Subjects Setup
- Add subjects for each sub-course in `course_subjects` table
- Set maximum marks for each subject

## Usage Instructions

### For Admin:

1. **Verify Payments & Approve Enrollments**:
   - Go to `admin/enrollment-approvals.php`
   - Review payment_pending enrollments
   - Check payment details (transaction ID, method, notes)
   - Click "Verify Payment & Approve" or "Reject Payment"

2. **Enter Marks**:
   - Go to `admin/marks-management.php`
   - Select a student from the list
   - Enter marks for each subject
   - Click "Save Marks"

3. **Complete Course**:
   - After entering all marks, click "Complete Course"
   - This makes the student eligible for certificate generation

4. **Generate Certificates**:
   - Go to `admin/certificate-management.php`
   - Find completed courses
   - Click "Generate Certificate"

### For Students:

1. **View Certificates**:
   - Go to `student/dashboard.php`
   - Scroll to "My Certificates" section
   - View and download available certificates

## Security Features

1. **Faculty Access Control**: Faculty can only manage courses assigned to them
2. **Student Data Protection**: Students can only access their own data
3. **Secure File Generation**: Certificates generated with unique identifiers
4. **Input Validation**: All forms include proper validation and sanitization

## File Structure

```
admin/
├── enrollment-approvals.php    # Enrollment approval interface
├── marks-management.php        # Marks management interface
├── certificate-management.php  # Certificate generation interface
└── dashboard.php               # Updated admin dashboard

student/
└── dashboard.php               # Updated with certificates section

database_certificate_workflow.sql  # Database schema
setup_certificate_workflow.php     # Database setup script
```

## Future Enhancements

1. **PDF Generation**: Implement actual PDF certificate generation
2. **Email Notifications**: Send notifications when certificates are generated
3. **Digital Signatures**: Add digital signatures to certificates
4. **QR Codes**: Add QR codes for certificate verification
5. **Bulk Operations**: Allow bulk certificate generation
6. **Certificate Templates**: Customizable certificate templates

## Troubleshooting

### Common Issues:

1. **Faculty can't see enrollments**: Check `faculty_courses` table assignments
2. **Marks not saving**: Verify `course_subjects` table has subjects for the course
3. **Certificate generation fails**: Check file permissions for certificate directories
4. **Students can't see certificates**: Verify enrollment status is `completed`

### Database Queries for Debugging:

```sql
-- Check faculty course assignments
SELECT * FROM faculty_courses WHERE faculty_id = ?;

-- Check course subjects
SELECT * FROM course_subjects WHERE sub_course_id = ?;

-- Check student marks
SELECT * FROM student_marks WHERE enrollment_id = ?;

-- Check certificates
SELECT * FROM certificates WHERE enrollment_id = ?;
```

## Support

For technical support or questions about the certificate workflow, please contact the system administrator or refer to the code documentation in the respective PHP files.
