# SKD CAT-BKN - Admin Manual

## Table of Contents
1. [Admin Dashboard Overview](#admin-dashboard-overview)
2. [User Management](#user-management)
3. [Question Management](#question-management)
4. [Material Management](#material-management)
5. [Tips Management](#tips-management)
6. [Media Library](#media-library)
7. [Tryout Management](#tryout-management)
8. [Analytics](#analytics)
9. [Feedback Management](#feedback-management)
10. [Moderation](#moderation)
11. [Revision Queue](#revision-queue)
12. [Reports](#reports)
13. [Best Practices](#best-practices)
14. [Security Guidelines](#security-guidelines)

## Admin Dashboard Overview

The admin dashboard provides comprehensive management tools for the SKD CAT-BKN platform.

### Navigation Tabs
- **Analytics**: View platform statistics and user behavior
- **Feedback**: Manage user feedback and reports
- **Moderasi**: Moderate user-generated content
- **Revision Queue**: Manage question revisions
- **Peserta**: Manage user accounts
- **Riwayat Tryout**: View tryout results
- **Event Tryout**: Manage tryout events
- **Kelola Soal**: Manage question database
- **Kelola Materi**: Manage learning materials
- **Kelola Tips**: Manage tips and tricks
- **Media Library**: Manage media assets
- **Reports**: Generate and view reports
- **Generator Massal**: Bulk generate questions
- **Konfigurasi**: System configuration

## User Management

### Viewing Users
1. Navigate to "Peserta" tab
2. View list of all registered users
3. Filter by search term (name, phone)
4. Filter by status (active/inactive)

### Managing Users
- **View Details**: Click on a user to see detailed information
- **Edit User**: Update user information
- **Deactivate User**: Disable user account
- **Delete User**: Permanently remove user (use with caution)

### User Information
- Name
- Phone number
- Registration date
- Last login
- Account status
- Total tryouts taken
- Average score

## Question Management

### Viewing Questions
1. Navigate to "Kelola Soal" tab
2. Filter by subtes (TWK, TIU, TKP)
3. Filter by difficulty
4. Search by keyword

### Adding Questions
1. Click "Tambah Soal"
2. Fill in question details:
   - Subtes (TWK, TIU, TKP)
   - Question text
   - Options A, B, C, D, E
   - Correct answer
   - Explanation (pembahasan)
   - Difficulty level
   - Tags (optional)
3. Click "Simpan"

### Editing Questions
1. Click "Edit" on a question
2. Modify the question details
3. Click "Update"
4. Version history is automatically saved

### Deleting Questions
1. Click "Hapus" on a question
2. Confirm deletion
3. Question is soft-deleted (can be restored if needed)

### Question Versioning
- Every edit creates a new version
- View version history
- Compare versions with diff view
- Restore previous versions

### Tags Management
- Create custom tags
- Assign tags to questions
- Filter questions by tags
- Tag categories for organization

## Material Management

### Viewing Materials
1. Navigate to "Kelola Materi" tab
2. Filter by subtes
3. Browse material list

### Adding Materials
1. Click "Tambah Materi"
2. Fill in material details:
   - Title
   - Subtes
   - Content (supports HTML)
   - Related topics
3. Click "Simpan"

### Editing Materials
1. Click "Edit" on a material
2. Modify content
3. Click "Update"

### Deleting Materials
1. Click "Hapus" on a material
2. Confirm deletion

## Tips Management

### Viewing Tips
1. Navigate to "Kelola Tips" tab
2. Filter by subtes
3. Browse tips list

### Adding Tips
1. Click "Tambah Tips"
2. Fill in tip details:
   - Title
   - Subtes
   - Content
   - Example question
   - Application tips
3. Click "Simpan"

### Editing Tips
1. Click "Edit" on a tip
2. Modify content
3. Click "Update"

### Deleting Tips
1. Click "Hapus" on a tip
2. Confirm deletion

## Media Library

### Viewing Media
1. Navigate to "Media Library" tab
2. Filter by folder
3. Filter by file type
4. Search by filename

### Uploading Media
1. Click "Upload Media"
2. Select file from your device
3. Choose folder (or create new)
4. Click "Upload"

### Managing Media
- **Copy URL**: Copy media URL for use in content
- **Delete**: Remove media from library
- **Move**: Move media to different folder

### Folder Organization
- Create folders for organization
- Move media between folders
- Delete empty folders

## Tryout Management

### Viewing Tryout Events
1. Navigate to "Event Tryout" tab
2. View list of scheduled tryouts
3. Filter by status (upcoming, ongoing, completed)

### Creating Tryout Events
1. Click "Tambah Event"
2. Fill in event details:
   - Event name
   - Date and time
   - Duration
   - Question count per subtes
   - Passing grade
3. Click "Simpan"

### Managing Tryout Events
- **Edit**: Modify event details
- **Delete**: Cancel event
- **View Results**: See participant results

### Viewing Tryout Results
1. Navigate to "Riwayat Tryout" tab
2. Filter by event
3. Filter by date range
4. View individual results

## Analytics

### Dashboard Metrics
- Total users
- Active users (last 7 days)
- Total tryouts taken
- Average tryout score
- Daily quiz participation
- Material views

### User Behavior Analytics
- Page views
- Time spent per page
- Most accessed materials
- Most repeated questions
- Learning path tracking

### Tryout Analytics
- Completion rate
- Score distribution
- Subtes performance
- Time spent per question

## Feedback Management

### Viewing Feedback
1. Navigate to "Feedback" tab
2. Filter by status (pending, reviewed, resolved)
3. Filter by category
4. View feedback details

### Managing Feedback
- **Mark as Reviewed**: Change status to reviewed
- **Mark as Resolved**: Change status to resolved
- **Reply**: Send response to user
- **Delete**: Remove feedback

## Moderation

### Viewing Moderation Queue
1. Navigate to "Moderasi" tab
2. View flagged content
3. Filter by type
4. Filter by status

### Moderating Content
- **Approve**: Accept content
- **Reject**: Reject content with reason
- **Edit**: Modify content before approval
- **Delete**: Remove inappropriate content

## Revision Queue

### Viewing Revision Queue
1. Navigate to "Revision Queue" tab
2. Filter by status (pending, assigned, in_progress, completed)
3. Filter by priority (urgent, high, medium, low)
4. View queue statistics

### Managing Revisions
- **Assign**: Assign revision to editor
- **Update Status**: Change revision status
- **Update Priority**: Change priority level
- **Add Notes**: Add admin notes
- **Remove**: Remove from queue

### Auto-Detection
- Click "Auto-Detect" to scan for questions needing revision
- View detected candidates with reasons
- Add individual candidates to queue
- Add all candidates at once

## Reports

### Generating Reports
1. Navigate to "Reports" tab
2. Select report type:
   - User Activity
   - Tryout Results
   - Content Performance
   - Revenue
3. Click "Generate CSV"
4. Download generated report

### Scheduled Reports
1. Click "+ Add Schedule"
2. Configure schedule:
   - Report type
   - Schedule title
   - Frequency (daily, weekly, monthly)
   - Time
3. Click "Create"
4. Manage schedules (activate, deactivate, delete)

## Best Practices

### Content Management
- **Quality First**: Ensure all questions are accurate and well-written
- **Consistent Formatting**: Use consistent formatting for questions and options
- **Clear Explanations**: Provide clear and helpful explanations
- **Regular Updates**: Keep content updated with current information
- **Tagging**: Use tags effectively for organization

### User Management
- **Quick Response**: Respond to user feedback promptly
- **Fair Moderation**: Apply moderation rules consistently
- **Communication**: Keep users informed about changes
- **Support**: Provide helpful support when needed

### Security
- **Strong Passwords**: Enforce strong password policies
- **Regular Updates**: Keep the platform updated
- **Backup**: Regularly backup data
- **Monitor**: Monitor for suspicious activity

## Security Guidelines

### Access Control
- Only authorized personnel should have admin access
- Use strong, unique passwords
- Enable two-factor authentication if available
- Log out after each session
- Don't share admin credentials

### Data Protection
- Protect user data at all times
- Don't access user data without valid reason
- Follow data privacy regulations
- Regularly review access logs

### Safe Practices
- Verify all actions before confirming
- Use the revision queue for content changes
- Keep backups before major changes
- Test changes in staging environment first
- Document important changes

### Incident Response
- Report security incidents immediately
- Follow established incident response procedures
- Document all security incidents
- Review and improve security practices regularly
