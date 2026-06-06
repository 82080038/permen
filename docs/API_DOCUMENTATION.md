# SKD CAT-BKN - API Documentation

## Base URL
```
https://yourdomain.com/permen/api
```

## Authentication
Most endpoints require authentication via session cookie. Admin endpoints require admin role.

## Response Format
All API responses return JSON:
```json
{
  "success": true/false,
  "data": {},
  "error": "Error message (if success is false)"
}
```

## Rate Limiting
- Public endpoints: 100 requests per minute
- Authenticated endpoints: 200 requests per minute
- Admin endpoints: 500 requests per minute

---

## Authentication Endpoints

### POST /login.php
Login user with phone number and password.

**Request:**
```json
{
  "no_hp": "081234567890",
  "password": "userpassword"
}
```

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "nama": "John Doe",
    "no_hp": "081234567890",
    "role": "user"
  }
}
```

### POST /register.php
Register new user.

**Request:**
```json
{
  "nama": "John Doe",
  "no_hp": "081234567890",
  "password": "userpassword"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful"
}
```

### POST /logout.php
Logout current user.

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Question Endpoints

### GET /list_soal.php
List questions with optional filters.

**Query Parameters:**
- `subtes` (optional): Filter by subtes (TWK, TIU, TKP)
- `tipe` (optional): Filter by type
- `limit` (optional): Number of results (default: 50)
- `offset` (optional): Pagination offset

**Response:**
```json
{
  "success": true,
  "soal": [
    {
      "id": 1,
      "pertanyaan": "Question text",
      "pilihan_a": "Option A",
      "pilihan_b": "Option B",
      "pilihan_c": "Option C",
      "pilihan_d": "Option D",
      "pilihan_e": "Option E",
      "jawaban_benar": "A",
      "subtes": "TWK",
      "tipe": "sejarah"
    }
  ],
  "total": 100
}
```

### POST /admin_soal_crud.php
Create, update, or delete questions (Admin only).

**Actions:**
- `create`: Create new question
- `update`: Update existing question
- `delete`: Delete question
- `get_soal_versions`: Get question version history
- `get_version_diff`: Compare two versions

**Request (create):**
```json
{
  "action": "create",
  "subtes": "TWK",
  "tipe": "sejarah",
  "pertanyaan": "Question text",
  "pilihan_a": "Option A",
  "pilihan_b": "Option B",
  "pilihan_c": "Option C",
  "pilihan_d": "Option D",
  "pilihan_e": "Option E",
  "jawaban_benar": "A",
  "pembahasan": "Explanation",
  "tips": "Tips",
  "related_links": "https://example.com",
  "materi": "Related material",
  "bobot_tkp": 5,
  "tags": [1, 2, 3]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Question created successfully",
  "soal_id": 123
}
```

---

## Answer Endpoints

### POST /save_answer.php
Save user answer.

**Request:**
```json
{
  "soal_id": 1,
  "jawaban_user": "A",
  "ragu_ragu": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Answer saved"
}
```

### GET /get_user_answers.php
Get user answers for a tryout.

**Query Parameters:**
- `tryout_id`: Tryout ID

**Response:**
```json
{
  "success": true,
  "answers": [
    {
      "soal_id": 1,
      "jawaban_user": "A",
      "is_correct": true
    }
  ]
}
```

---

## Tryout Endpoints

### POST /start_tryout.php
Start a new tryout session.

**Request:**
```json
{
  "event_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "tryout_id": 456,
  "questions": [...],
  "duration": 2700
}
```

### POST /submit_tryout.php
Submit tryout answers.

**Request:**
```json
{
  "tryout_id": 456,
  "answers": [...]
}
```

**Response:**
```json
{
  "success": true,
  "score": 450,
  "twk_score": 150,
  "tiu_score": 150,
  "tkp_score": 150,
  "passed": true
}
```

### GET /get_tryout_results.php
Get tryout results.

**Query Parameters:**
- `tryout_id`: Tryout ID

**Response:**
```json
{
  "success": true,
  "result": {
    "score": 450,
    "twk_score": 150,
    "tiu_score": 150,
    "tkp_score": 150,
    "passed": true,
    "rank": 10
  }
}
```

---

## Daily Quiz Endpoints

### GET /daily_quiz.php
Get daily quiz questions.

**Response:**
```json
{
  "success": true,
  "quiz_available": true,
  "questions": [...],
  "duration": 600
}
```

### POST /submit_daily_quiz.php
Submit daily quiz answers.

**Request:**
```json
{
  "answers": [...]
}
```

**Response:**
```json
{
  "success": true,
  "score": 8,
  "streak": 5
}
```

---

## Material Endpoints

### GET /materi.php
Get learning materials.

**Query Parameters:**
- `subtes` (optional): Filter by subtes

**Response:**
```json
{
  "success": true,
  "materi": [
    {
      "id": 1,
      "judul": "Material Title",
      "subtes": "TWK",
      "konten": "HTML content",
      "created_at": "2024-01-01"
    }
  ]
}
```

### POST /admin_materi_crud.php
Create, update, or delete materials (Admin only).

**Actions:**
- `create`: Create new material
- `update`: Update existing material
- `delete`: Delete material

---

## Tips Endpoints

### GET /tips.php
Get tips and tricks.

**Query Parameters:**
- `subtes` (optional): Filter by subtes

**Response:**
```json
{
  "success": true,
  "tips": [
    {
      "id": 1,
      "judul": "Tip Title",
      "subtes": "TWK",
      "konten": "Tip content",
      "contoh_soal": "Example question",
      "penerapan": "Application tips"
    }
  ]
}
```

### POST /admin_tips_crud.php
Create, update, or delete tips (Admin only).

**Actions:**
- `create`: Create new tip
- `update`: Update existing tip
- `delete`: Delete tip

---

## Media Library Endpoints

### POST /admin_media_library.php
Manage media library (Admin only).

**Actions:**
- `upload`: Upload new media
- `delete`: Delete media
- `list`: List media with filters
- `folders`: Get distinct folders

**Request (upload):**
```json
{
  "action": "upload",
  "file": [binary data],
  "folder": "soal"
}
```

**Response:**
```json
{
  "success": true,
  "url": "/permen/uploads/soal/image.jpg",
  "media_id": 789
}
```

---

## Revision Queue Endpoints

### POST /admin_revision_queue.php
Manage revision queue (Admin only).

**Actions:**
- `add_to_queue`: Add question to queue
- `assign_revision`: Assign revision to user
- `update_status`: Update revision status
- `update_priority`: Update priority
- `remove_from_queue`: Remove from queue
- `get_queue`: Get queue items
- `get_revision_stats`: Get queue statistics

**Request (add_to_queue):**
```json
{
  "action": "add_to_queue",
  "soal_id": 1,
  "priority": "high",
  "reason": "Ambiguous question"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Added to queue",
  "queue_id": 100
}
```

---

## Auto-Detect Revision Endpoints

### GET /auto_detect_revision.php
Detect questions needing revision (Admin only).

**Actions:**
- `detect_revision_candidates`: Scan for candidates
- `add_candidate_to_queue`: Add single candidate
- `add_all_candidates`: Add all candidates

**Response (detect):**
```json
{
  "success": true,
  "candidates": [
    {
      "soal_id": 1,
      "pertanyaan": "Question text",
      "reason": "Low answer rate: 15%",
      "priority": "high"
    }
  ]
}
```

---

## Learning Analytics Endpoints

### POST /learning_analytics.php
Track learning events.

**Actions:**
- `track_event`: Log learning event
- `get_learning_insights`: Get user insights
- `mark_insight_read`: Mark insight as read
- `get_learning_stats`: Get learning statistics

**Request (track_event):**
```json
{
  "action": "track_event",
  "event_type": "soal_view",
  "soal_id": 1,
  "subtes": "TWK",
  "topik": "sejarah"
}
```

**Response:**
```json
{
  "success": true
}
```

---

## Admin Reports Endpoints

### POST /admin_reports.php
Generate and manage reports (Admin only).

**Actions:**
- `generate_report`: Generate new report
- `get_reports`: Get generated reports
- `get_schedules`: Get report schedules
- `create_schedule`: Create new schedule
- `toggle_schedule`: Activate/deactivate schedule
- `delete_schedule`: Delete schedule

**Request (generate_report):**
```json
{
  "action": "generate_report",
  "report_type": "user_activity",
  "filters": {}
}
```

**Response:**
```json
{
  "success": true,
  "message": "Report generated",
  "file": "/permen/reports/user_activity_2024-01-01.csv"
}
```

---

## Push Notifications Endpoints

### POST /push_notifications.php
Manage push notifications.

**Actions:**
- `subscribe`: Subscribe to push notifications
- `unsubscribe`: Unsubscribe from push notifications
- `update_preferences`: Update notification preferences
- `get_preferences`: Get user preferences
- `check_subscription`: Check subscription status

**Request (subscribe):**
```json
{
  "action": "subscribe",
  "endpoint": "https://push.endpoint.com",
  "p256dh": "key",
  "auth": "auth"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription saved"
}
```

---

## Feedback Endpoints

### POST /feedback.php
Submit user feedback.

**Request:**
```json
{
  "category": "bug",
  "message": "Bug description",
  "page_url": "/permen/pages/latihan.php"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Feedback submitted"
}
```

### POST /admin_feedback.php
Manage feedback (Admin only).

**Actions:**
- `update_status`: Update feedback status
- `delete`: Delete feedback

---

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Not logged in |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

---

## Common Error Response

```json
{
  "success": false,
  "error": "Error message describing the issue"
}
```

---

## Best Practices

1. **Always check the `success` field** before processing the response
2. **Handle errors gracefully** - Display user-friendly error messages
3. **Use rate limiting** - Implement exponential backoff for retries
4. **Validate input** - Always validate data before sending
5. **Use HTTPS** - All API calls should use HTTPS
6. **Keep sessions secure** - Don't expose session tokens
7. **Cache responses** - Cache static data when appropriate
8. **Monitor usage** - Track API usage for optimization

---

## Changelog

### Version 1.0 (2024-01-01)
- Initial API documentation
- Core endpoints documented
- Authentication flow documented
