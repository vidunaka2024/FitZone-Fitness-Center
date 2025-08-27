# FitZone Fitness Center - API Documentation

This document provides detailed information about the FitZone API endpoints, request/response formats, and authentication methods.

## 📋 Table of Contents

- [Base URL](#base-url)
- [Authentication](#authentication)
- [Request/Response Format](#requestresponse-format)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Authentication Endpoints](#authentication-endpoints)
- [User Management](#user-management)
- [Classes & Schedules](#classes--schedules)
- [Search API](#search-api)
- [Contact & Newsletter](#contact--newsletter)
- [File Uploads](#file-uploads)
- [Admin API](#admin-api)

## 🌐 Base URL

```
Production: https://fitzonecenter.com
Development: http://localhost/FitZone-Fitness-Center
```

All API endpoints are prefixed with `/php/` directory.

## 🔐 Authentication

FitZone uses session-based authentication with CSRF protection for web requests and token-based authentication for API requests.

### Session Authentication
Most endpoints use PHP sessions. Users must be logged in with a valid session.

### CSRF Protection
All POST requests require a CSRF token in the request body:
```javascript
// Include CSRF token in forms
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

// Or in AJAX requests
$.ajaxSetup({
    beforeSend: function(xhr, settings) {
        if (settings.type === 'POST') {
            xhr.setRequestHeader('X-CSRF-Token', csrfToken);
        }
    }
});
```

### API Headers
```http
Content-Type: application/json
X-Requested-With: XMLHttpRequest
X-CSRF-Token: your-csrf-token-here
```

## 📊 Request/Response Format

### Request Format
All POST requests should be sent as:
- `application/x-www-form-urlencoded` for form submissions
- `multipart/form-data` for file uploads
- `application/json` for API requests (when specified)

### Response Format
All responses are returned in JSON format:

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data
    },
    "errors": {},
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total_results": 100,
        "total_pages": 5,
        "has_more": true
    }
}
```

### Success Response
```json
{
    "success": true,
    "message": "Success message",
    "data": {}
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field_name": "Field-specific error message"
    }
}
```

## ❌ Error Handling

### HTTP Status Codes
- `200 OK` - Success
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Access denied
- `404 Not Found` - Resource not found
- `405 Method Not Allowed` - Invalid HTTP method
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

### Error Types
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": "Email is required",
        "password": "Password must be at least 8 characters"
    }
}
```

## 🚦 Rate Limiting

Rate limits are applied per IP address:

| Endpoint | Limit | Window |
|----------|-------|--------|
| Login attempts | 5 requests | 15 minutes |
| Registration | 3 requests | 10 minutes |
| Contact form | 3 requests | 10 minutes |
| Newsletter subscription | 5 requests | 5 minutes |
| Search requests | 20 requests | 1 minute |

When rate limit is exceeded:
```json
{
    "success": false,
    "message": "Too many requests. Please try again later.",
    "retry_after": 900
}
```

## 🔑 Authentication Endpoints

### Login
**POST** `/php/auth/login.php`

Login a user with email and password.

**Request:**
```json
{
    "email": "user@example.com",
    "password": "userpassword",
    "remember_me": true,
    "return_url": "/dashboard.php"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "redirect": "dashboard.php",
        "user": {
            "id": 123,
            "name": "John Doe",
            "email": "user@example.com",
            "role": "member",
            "membership_plan": "premium"
        }
    }
}
```

### Register
**POST** `/php/auth/register.php`

Register a new user account.

**Request:**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "(555) 123-4567",
    "birth_date": "1990-01-15",
    "gender": "male",
    "password": "securepassword123",
    "confirm_password": "securepassword123",
    "membership_plan": "premium",
    "fitness_goals": "Weight loss and muscle building",
    "newsletter": true,
    "terms": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "Registration successful! Welcome to FitZone!",
    "data": {
        "redirect": "dashboard.php",
        "user": {
            "id": 124,
            "name": "John Doe",
            "email": "john@example.com",
            "membership_plan": "premium"
        }
    }
}
```

### Logout
**GET** `/php/auth/logout.php`

Logout the current user and destroy session.

**Response:** Redirects to homepage or returns JSON for AJAX requests.

## 👤 User Management

### Get User Profile
**GET** `/php/user/profile.php`

Get current user's profile information.

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "(555) 123-4567",
        "membership_plan": "premium",
        "profile_picture": "uploads/profile-pics/user123.jpg",
        "fitness_goals": "Weight loss",
        "created_at": "2024-01-15T10:30:00Z"
    }
}
```

### Update User Profile
**POST** `/php/user/profile.php`

Update user profile information.

**Request:**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "phone": "(555) 123-4567",
    "fitness_goals": "Muscle building and endurance"
}
```

### Get User Appointments
**GET** `/php/user/appointments.php`

Get user's training appointments.

**Response:**
```json
{
    "success": true,
    "data": {
        "upcoming": [
            {
                "id": 456,
                "trainer_name": "Mike Thompson",
                "date": "2024-01-20",
                "time": "14:00:00",
                "duration": 60,
                "location": "Gym Floor A",
                "status": "confirmed"
            }
        ],
        "past": []
    }
}
```

## 🏃 Classes & Schedules

### Get Classes
**GET** `/php/classes/list.php`

Get list of available fitness classes.

**Parameters:**
- `type` - Filter by class type (cardio, strength, flexibility, etc.)
- `level` - Filter by difficulty level (beginner, intermediate, advanced)
- `date` - Filter by date (YYYY-MM-DD)
- `limit` - Number of results per page (default: 20)
- `page` - Page number (default: 1)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Zumba Dance Fitness",
            "description": "High-energy dance fitness...",
            "type": "dance",
            "difficulty_level": "intermediate",
            "duration_minutes": 45,
            "max_capacity": 25,
            "image_url": "images/classes/zumba.jpg",
            "upcoming_schedules": [
                {
                    "id": 101,
                    "date": "2024-01-20",
                    "start_time": "19:00:00",
                    "end_time": "19:45:00",
                    "trainer_name": "Maria Rodriguez",
                    "room": "Studio A",
                    "current_capacity": 18,
                    "available_spots": 7
                }
            ]
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total_results": 6,
        "total_pages": 1,
        "has_more": false
    }
}
```

### Book Class
**POST** `/php/classes/book.php`

Book a spot in a fitness class.

**Request:**
```json
{
    "schedule_id": 101,
    "user_id": 123
}
```

**Response:**
```json
{
    "success": true,
    "message": "Class booked successfully",
    "data": {
        "booking_id": 789,
        "class_name": "Zumba Dance Fitness",
        "date": "2024-01-20",
        "time": "19:00:00",
        "trainer": "Maria Rodriguez"
    }
}
```

## 🔍 Search API

### Search Content
**GET** `/php/ajax/search.php`

Search across classes, trainers, and blog content.

**Parameters:**
- `q` or `query` - Search query string
- `scope` - Search scope: `all`, `classes`, `trainers`, `blog`
- `type` - Filter by type (for classes)
- `level` - Filter by difficulty level (for classes)
- `category` - Filter by category (for blog)
- `limit` - Results per page (1-50, default: 20)
- `page` - Page number (default: 1)

**Request:**
```
GET /php/ajax/search.php?q=yoga&scope=classes&level=beginner&limit=10
```

**Response:**
```json
{
    "success": true,
    "message": "Search completed",
    "data": {
        "results": [
            {
                "id": 2,
                "name": "Hatha Yoga",
                "description": "Gentle yoga practice...",
                "type": "mind_body",
                "difficulty_level": "beginner",
                "result_type": "class",
                "url": "classes.php#class-2",
                "highlight": "<mark>Yoga</mark> practice",
                "upcoming_schedules": []
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 10,
            "total_results": 1,
            "total_pages": 1,
            "has_more": false
        },
        "query": "yoga",
        "scope": "classes",
        "filters": {
            "type": "",
            "level": "beginner",
            "category": ""
        }
    }
}
```

## 📧 Contact & Newsletter

### Contact Form
**POST** `/php/ajax/contact-form.php`

Submit a contact form inquiry.

**Request:**
```json
{
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane@example.com",
    "phone": "(555) 987-6543",
    "subject": "membership",
    "message": "I'm interested in learning about your membership options...",
    "newsletter": true,
    "terms": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "Message sent successfully! We'll get back to you within 24 hours.",
    "data": {
        "message_id": 567,
        "estimated_response_time": "24 hours"
    }
}
```

### Newsletter Subscription
**POST** `/php/ajax/newsletter.php`

Subscribe to the newsletter.

**Request:**
```json
{
    "email": "subscriber@example.com",
    "first_name": "Newsletter",
    "last_name": "Subscriber",
    "source": "homepage"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Thank you for subscribing! Please check your email to confirm your subscription.",
    "data": {
        "subscription_id": 890,
        "requires_confirmation": true
    }
}
```

## 📎 File Uploads

### Profile Picture Upload
**POST** `/php/user/upload-avatar.php`

Upload user profile picture.

**Request:** `multipart/form-data`
```
Content-Type: multipart/form-data

file: [image file]
user_id: 123
```

**Response:**
```json
{
    "success": true,
    "message": "Profile picture updated successfully",
    "data": {
        "filename": "user123_avatar.jpg",
        "url": "uploads/profile-pics/user123_avatar.jpg",
        "size": 156789
    }
}
```

**File Requirements:**
- Maximum size: 5MB
- Allowed formats: JPG, PNG, GIF, WebP
- Recommended dimensions: 400x400px

## 🔧 Admin API

### Get Dashboard Stats
**GET** `/php/admin/dashboard-stats.php`

Get admin dashboard statistics.

**Response:**
```json
{
    "success": true,
    "data": {
        "users": {
            "total": 1250,
            "active": 1180,
            "new_this_month": 85
        },
        "classes": {
            "total": 15,
            "active": 12,
            "bookings_this_month": 450
        },
        "revenue": {
            "this_month": 45670.50,
            "last_month": 42150.00,
            "growth_percentage": 8.4
        }
    }
}
```

### Manage Users
**GET** `/php/admin/users.php` - List users
**POST** `/php/admin/users.php` - Create user
**PUT** `/php/admin/users.php` - Update user
**DELETE** `/php/admin/users.php` - Delete user

### Manage Classes
**GET** `/php/admin/classes.php` - List classes
**POST** `/php/admin/classes.php` - Create class
**PUT** `/php/admin/classes.php` - Update class
**DELETE** `/php/admin/classes.php` - Delete class

## 📝 Code Examples

### JavaScript/jQuery Examples

```javascript
// Login request
$.ajax({
    url: '/php/auth/login.php',
    method: 'POST',
    data: {
        email: 'user@example.com',
        password: 'password123',
        csrf_token: csrfToken
    },
    success: function(response) {
        if (response.success) {
            window.location.href = response.data.redirect;
        } else {
            showErrors(response.errors);
        }
    }
});

// Search request
$.ajax({
    url: '/php/ajax/search.php',
    method: 'GET',
    data: {
        q: 'yoga',
        scope: 'classes',
        limit: 10
    },
    success: function(response) {
        displaySearchResults(response.data.results);
    }
});

// File upload
var formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('user_id', userId);

$.ajax({
    url: '/php/user/upload-avatar.php',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
        if (response.success) {
            updateProfilePicture(response.data.url);
        }
    }
});
```

### PHP Examples

```php
// Making API calls from PHP
function callAPI($endpoint, $data = null, $method = 'GET') {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/FitZone-Fitness-Center' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Example usage
$searchResults = callAPI('/php/ajax/search.php?q=yoga&scope=classes');
```

## 🔒 Security Considerations

1. **Always validate CSRF tokens** for state-changing operations
2. **Rate limiting** is enforced - respect the limits
3. **Sanitize all input** data before processing
4. **Use HTTPS** in production environments
5. **Store sensitive data securely** - never log passwords or tokens
6. **Validate file uploads** thoroughly
7. **Check user permissions** before allowing operations

## 📞 Support

For API support:
- **Documentation**: This guide
- **Email**: api-support@fitzonecenter.com
- **Response Time**: 24-48 hours

---

This API documentation is maintained alongside the FitZone codebase and updated with each release.