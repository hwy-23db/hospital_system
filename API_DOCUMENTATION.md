# Hospital Management System - API Documentation

## Base URL

All API endpoints are prefixed with `/api`. The base URL depends on your environment configuration:

-   **Development**: `http://localhost/api` (or your configured `APP_URL`)
-   **Production**: `https://your-domain.com/api`

## Authentication

This API uses **Laravel Sanctum** for token-based authentication. Most endpoints require authentication via Bearer token.

### Getting an Access Token

To authenticate, make a POST request to `/api/login` with valid credentials. The response will include a `token` that should be included in subsequent requests.

### Using the Token

Include the token in the `Authorization` header of your requests:

```
Authorization: Bearer {your_token_here}
```

### Token Expiration

Access tokens expire after **24 hours**. After expiration, you'll need to log in again to obtain a new token.

---

## Endpoints

### 1. Login

Authenticate a user and receive an access token.

**Endpoint:** `POST /api/login`

**Authentication:** Not required (public endpoint)

**Rate Limiting:** 5 attempts per email+IP combination per 60 seconds

#### Request Body

```json
{
    "email": "user@example.com",
    "password": "YourPassword123!"
}
```

#### Request Parameters

| Parameter  | Type   | Required | Description                             |
| ---------- | ------ | -------- | --------------------------------------- |
| `email`    | string | Yes      | User's email address (case-insensitive) |
| `password` | string | Yes      | User's password                         |

#### Success Response (200 OK)

```json
{
    "message": "Login successful",
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
    "expires_at": "2024-01-15T10:30:00+00:00",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "doctor"
    }
}
```

#### Error Responses

**401 Unauthorized** - Invalid credentials

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["These credentials do not match our records."]
    }
}
```

**429 Too Many Requests** - Rate limit exceeded

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["Too many login attempts. Please try again in 60 seconds."]
    }
}
```

**422 Unprocessable Entity** - Validation errors

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field must be a valid email address."],
        "password": ["The password field is required."]
    }
}
```

#### Example cURL Request

```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "YourPassword123!"
  }'
```

---

### 2. Get Current User

Retrieve information about the currently authenticated user.

**Endpoint:** `GET /api/user`

**Authentication:** Required (Bearer token)

#### Headers

```
Authorization: Bearer {your_token_here}
```

#### Success Response (200 OK)

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "doctor"
    }
}
```

#### Error Responses

**401 Unauthorized** - Invalid or missing token

```json
{
    "message": "Unauthenticated."
}
```

#### Example cURL Request

```bash
curl -X GET http://localhost/api/user \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890"
```

---

### 3. Update Profile

Update your own profile information (name, email, and/or password). **All authenticated users can update their own profile.**

**Endpoint:** `PUT /api/user/profile` or `PATCH /api/user/profile`

**Authentication:** Required (Bearer token)

#### Headers

```
Authorization: Bearer {your_token_here}
Content-Type: application/json
```

#### Request Body

All fields are optional. Only include the fields you want to update:

```json
{
    "name": "John Updated",
    "email": "newemail@example.com",
    "password": "NewSecurePass123!",
    "password_confirmation": "NewSecurePass123!"
}
```

OR update only specific fields:

```json
{
    "name": "John Updated"
}
```

#### Request Parameters

| Parameter               | Type   | Required | Description                                                                         |
| ----------------------- | ------ | -------- | ----------------------------------------------------------------------------------- |
| `name`                  | string | No       | User's full name (letters and spaces only, max 255 characters)                      |
| `email`                 | string | No       | User's email address (must be unique, case-insensitive)                             |
| `password`              | string | No       | New password (min 8 chars, must contain uppercase, lowercase, numbers, and symbols) |
| `password_confirmation` | string | No\*     | Password confirmation (required if password is provided, must match password)       |

\* `password_confirmation` is required only when `password` is provided.

#### Password Requirements

If updating password:

-   Minimum 8 characters
-   Must contain at least one uppercase letter
-   Must contain at least one lowercase letter
-   Must contain at least one number
-   Must contain at least one symbol
-   Must not be a compromised password (checked against common password lists)

#### Success Response (200 OK)

```json
{
    "message": "Profile updated successfully",
    "user": {
        "id": 1,
        "name": "John Updated",
        "email": "newemail@example.com",
        "role": "doctor",
        "email_verified_at": null,
        "updated_at": "2024-01-15T10:35:00.000000Z"
    }
}
```

**Note:** If email is updated, `email_verified_at` will be set to `null` and the user will need to verify their new email address.

#### Response When No Changes Provided (200 OK)

```json
{
    "message": "No changes provided",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "doctor"
    }
}
```

#### Error Responses

**401 Unauthorized** - Invalid or missing token

```json
{
    "message": "Unauthenticated."
}
```

**422 Unprocessable Entity** - Validation errors

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."],
        "password": [
            "The password must be at least 8 characters.",
            "The password must contain at least one uppercase and one lowercase letter.",
            "The password must contain at least one symbol."
        ],
        "name": ["The name field may only contain letters and spaces."],
        "password_confirmation": ["The password confirmation does not match."]
    }
}
```

#### Notes

-   Users can only update their own profile (not other users' profiles)
-   All fields are optional - only include fields you want to update
-   If email is changed, email verification status is reset
-   Password updates require confirmation
-   All profile updates are logged for audit purposes
-   The `role` field cannot be updated through this endpoint (only root_user can change roles)

#### Example cURL Request

Update name only:

```bash
curl -X PUT http://localhost/api/user/profile \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Updated"
  }'
```

Update email:

```bash
curl -X PATCH http://localhost/api/user/profile \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "newemail@example.com"
  }'
```

Update password:

```bash
curl -X PUT http://localhost/api/user/profile \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "NewSecurePass123!",
    "password_confirmation": "NewSecurePass123!"
  }'
```

Update multiple fields:

```bash
curl -X PUT http://localhost/api/user/profile \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Updated",
    "email": "newemail@example.com"
  }'
```

---

### 4. Logout

Revoke the current access token, effectively logging out the user.

**Endpoint:** `POST /api/logout`

**Authentication:** Required (Bearer token)

#### Headers

```
Authorization: Bearer {your_token_here}
```

#### Success Response (200 OK)

```json
{
    "message": "Logged out successfully"
}
```

#### Error Responses

**401 Unauthorized** - Invalid or missing token

```json
{
    "message": "Unauthenticated."
}
```

#### Example cURL Request

```bash
curl -X POST http://localhost/api/logout \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890"
```

---

### 5. Register User

Create a new user account. **Only accessible by root_user role.**

**Endpoint:** `POST /api/register`

**Authentication:** Required (Bearer token with root_user role)

**Authorization:** Requires `root_user` role

#### Headers

```
Authorization: Bearer {your_token_here}
Content-Type: application/json
```

#### Request Body

```json
{
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "role": "doctor"
}
```

#### Request Parameters

| Parameter               | Type   | Required | Description                                                                     |
| ----------------------- | ------ | -------- | ------------------------------------------------------------------------------- |
| `name`                  | string | Yes      | User's full name (letters and spaces only, max 255 characters)                  |
| `email`                 | string | Yes      | User's email address (must be unique, case-insensitive)                         |
| `password`              | string | Yes      | Password (min 8 chars, must contain uppercase, lowercase, numbers, and symbols) |
| `password_confirmation` | string | Yes      | Password confirmation (must match password)                                     |
| `role`                  | string | Yes      | User role. Allowed values: `admission`, `nurse`, `doctor`                       |

#### Password Requirements

-   Minimum 8 characters
-   Must contain at least one uppercase letter
-   Must contain at least one lowercase letter
-   Must contain at least one number
-   Must contain at least one symbol
-   Must not be a compromised password (checked against common password lists)

#### Role Restrictions

-   Root user can only create: `admission`, `nurse`, or `doctor` roles
-   **Cannot create `root_user` role** - root user is only created via database seeding

#### Success Response (201 Created)

```json
{
    "message": "User registered successfully",
    "user": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane.smith@example.com",
        "role": "doctor",
        "created_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

#### Error Responses

**401 Unauthorized** - Invalid or missing token

```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden** - Not a root_user

```json
{
    "message": "This action is unauthorized."
}
```

**403 Forbidden** - Attempting to create root_user

```json
{
    "message": "Root user cannot be created via API. Root user is only created through database seeding."
}
```

**422 Unprocessable Entity** - Validation errors

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."],
        "password": [
            "The password must be at least 8 characters.",
            "The password must contain at least one uppercase and one lowercase letter.",
            "The password must contain at least one symbol."
        ],
        "name": ["The name field may only contain letters and spaces."],
        "role": [
            "Invalid role selected. Root user can only create admission, nurse, or doctor roles. Root user cannot be created."
        ]
    }
}
```

#### Example cURL Request

```bash
curl -X POST http://localhost/api/register \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "role": "doctor"
  }'
```

---

### 6. List All Users

Retrieve a list of all users in the system. **Only accessible by root_user role.**

**Endpoint:** `GET /api/users`

**Authentication:** Required (Bearer token with root_user role)

**Authorization:** Requires `root_user` role

#### Headers

```
Authorization: Bearer {your_token_here}
```

#### Success Response (200 OK)

```json
{
    "message": "Users retrieved successfully",
    "total": 3,
    "users": [
        {
            "id": 1,
            "name": "Root User",
            "email": "root@example.com",
            "role": "root_user",
            "email_verified_at": null,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "doctor",
            "email_verified_at": null,
            "created_at": "2024-01-10T08:00:00.000000Z",
            "updated_at": "2024-01-10T08:00:00.000000Z"
        },
        {
            "id": 3,
            "name": "Jane Smith",
            "email": "jane@example.com",
            "role": "nurse",
            "email_verified_at": "2024-01-12T10:00:00.000000Z",
            "created_at": "2024-01-12T10:00:00.000000Z",
            "updated_at": "2024-01-12T10:00:00.000000Z"
        }
    ]
}
```

#### Response Fields

| Field                       | Type         | Description                                       |
| --------------------------- | ------------ | ------------------------------------------------- |
| `message`                   | string       | Success message                                   |
| `total`                     | integer      | Total number of users                             |
| `users`                     | array        | Array of user objects                             |
| `users[].id`                | integer      | User ID                                           |
| `users[].name`              | string       | User's full name                                  |
| `users[].email`             | string       | User's email address                              |
| `users[].role`              | string       | User's role (root_user, doctor, nurse, admission) |
| `users[].email_verified_at` | string\|null | Email verification timestamp (ISO 8601) or null   |
| `users[].created_at`        | string       | Account creation timestamp (ISO 8601)             |
| `users[].updated_at`        | string       | Last update timestamp (ISO 8601)                  |

**Note:** Sensitive fields (password, remember_token) are excluded from the response.

#### Error Responses

**401 Unauthorized** - Invalid or missing token

```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden** - Not a root_user

```json
{
    "message": "This action is unauthorized."
}
```

#### Example cURL Request

```bash
curl -X GET http://localhost/api/users \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890"
```

---

### 7. Send Password Reset Link

Send a password reset link to a user's email address. **Only accessible by root_user role.**

**Endpoint:** `POST /api/users/forgot-password`

**Authentication:** Required (Bearer token with root_user role)

**Authorization:** Requires `root_user` role

#### Headers

```
Authorization: Bearer {your_token_here}
Content-Type: application/json
```

#### Request Body

You can provide either `user_id` or `email` to identify the user:

```json
{
    "user_id": 2
}
```

OR

```json
{
    "email": "jane.smith@example.com"
}
```

#### Request Parameters

| Parameter | Type    | Required | Description                                                                  |
| --------- | ------- | -------- | ---------------------------------------------------------------------------- |
| `user_id` | integer | Yes\*    | User ID (required if email is not provided)                                  |
| `email`   | string  | Yes\*    | User's email address (required if user_id is not provided, case-insensitive) |

\* Either `user_id` or `email` must be provided, but not both are required.

#### Success Response (200 OK)

```json
{
    "message": "Password reset link has been sent to the user's email address.",
    "user": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane.smith@example.com"
    }
}
```

#### Error Responses

**400 Bad Request** - Missing required parameters

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "user_id": ["Either user_id or email must be provided."],
        "email": ["Either user_id or email must be provided."]
    }
}
```

**401 Unauthorized** - Invalid or missing token

```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden** - Not a root_user

```json
{
    "message": "Unauthorized. Only root user can access this endpoint."
}
```

**403 Forbidden** - Attempting to send reset link to root user

```json
{
    "message": "Cannot send password reset link to root user through this endpoint."
}
```

**404 Not Found** - User not found

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "user_id": ["The specified user does not exist."],
        "email": ["The specified email does not exist in the system."]
    }
}
```

**422 Unprocessable Entity** - Validation errors

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email must be a valid email address."],
        "user_id": ["The user id must be an integer."]
    }
}
```

**500 Internal Server Error** - Failed to send email

```json
{
    "message": "Failed to send password reset link.",
    "error": "Error message from email service"
}
```

#### Notes

-   The password reset link will be sent to the user's email address using Laravel's built-in password reset system
-   The reset link will be valid for a limited time (configured in Laravel's password reset settings)
-   Root users cannot use this endpoint to reset their own password (they should use the standard forgot password flow)
-   All actions are logged for audit purposes

#### Example cURL Request

Using user_id:

```bash
curl -X POST http://localhost/api/users/forgot-password \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2
  }'
```

Using email:

```bash
curl -X POST http://localhost/api/users/forgot-password \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jane.smith@example.com"
  }'
```

---

### 8. Delete User

Delete a user from the system. **Only accessible by root_user role.**

**Endpoint:** `DELETE /api/users/{id}`

**Authentication:** Required (Bearer token with root_user role)

**Authorization:** Requires `root_user` role

#### Headers

```
Authorization: Bearer {your_token_here}
```

#### URL Parameters

| Parameter | Type    | Required | Description       |
| --------- | ------- | -------- | ----------------- |
| `id`      | integer | Yes      | User ID to delete |

#### Success Response (200 OK)

```json
{
    "message": "User deleted successfully",
    "deleted_user": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane.smith@example.com",
        "role": "doctor"
    }
}
```

#### Response Fields

| Field                | Type    | Description                    |
| -------------------- | ------- | ------------------------------ |
| `message`            | string  | Success message                |
| `deleted_user`       | object  | Information about deleted user |
| `deleted_user.id`    | integer | Deleted user's ID              |
| `deleted_user.name`  | string  | Deleted user's name            |
| `deleted_user.email` | string  | Deleted user's email           |
| `deleted_user.role`  | string  | Deleted user's role            |

#### Error Responses

**400 Bad Request** - Invalid user ID

```json
{
    "message": "Invalid user ID provided."
}
```

**401 Unauthorized** - Invalid or missing token

```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden** - Not a root_user

```json
{
    "message": "Unauthorized. Only root user can access this endpoint."
}
```

**403 Forbidden** - Attempting to delete root user

```json
{
    "message": "Cannot delete root user. Root user cannot be removed from the system."
}
```

**404 Not Found** - User not found

```json
{
    "message": "The specified user does not exist."
}
```

#### Notes

-   Root users cannot be deleted through this endpoint
-   All user deletions are logged for audit purposes
-   This action is permanent and cannot be undone
-   The user's access tokens will be automatically revoked upon deletion

#### Example cURL Request

```bash
curl -X DELETE http://localhost/api/users/2 \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890"
```

---

## User Roles

The system supports the following user roles:

| Role        | Description          | Permissions                                                                                |
| ----------- | -------------------- | ------------------------------------------------------------------------------------------ |
| `root_user` | System administrator | Full access, can create users, view all users, send password reset links, and delete users |
| `doctor`    | Medical doctor       | Access to doctor-specific features                                                         |
| `nurse`     | Nursing staff        | Access to nurse-specific features                                                          |
| `admission` | Admission staff      | Access to admission-specific features                                                      |

---

## Error Handling

All errors follow a consistent format:

### Validation Errors (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

### Authentication Errors (401)

```json
{
    "message": "Unauthenticated."
}
```

### Authorization Errors (403)

```json
{
    "message": "This action is unauthorized."
}
```

### Rate Limiting Errors (429)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["Too many login attempts. Please try again in 60 seconds."]
    }
}
```

---

## Rate Limiting

### Login Endpoint

-   **Limit:** 5 attempts per email+IP combination
-   **Window:** 60 seconds
-   **Action on exceed:** Account locked for the remaining time in the window

### Other Endpoints

-   Standard API rate limiting applies (configured via Laravel's throttle middleware)
-   Protected endpoints require authentication, which provides additional security

---

## Security Features

1. **Token-based Authentication:** All protected endpoints require a valid Bearer token
2. **Password Hashing:** Passwords are automatically hashed using bcrypt
3. **Rate Limiting:** Login endpoint has rate limiting to prevent brute force attacks
4. **Role-based Access Control:** Endpoints are protected by role-based middleware
5. **Input Validation:** All inputs are validated and sanitized
6. **Audit Logging:** All authentication and user management actions (login, logout, registration, password resets, user deletion) are logged
7. **Password Requirements:** Strong password requirements enforced
8. **Email Normalization:** Email addresses are normalized to lowercase
9. **Root User Protection:** Root users cannot be deleted or have their passwords reset through admin endpoints
10. **Secure Password Reset:** Password reset links are sent via email using Laravel's secure token system

---

## Best Practices

1. **Store tokens securely:** Never expose tokens in client-side code or logs
2. **Handle token expiration:** Implement token refresh logic or re-authentication
3. **Use HTTPS in production:** Always use HTTPS for API requests in production
4. **Validate responses:** Always check response status codes and handle errors appropriately
5. **Respect rate limits:** Implement exponential backoff for rate-limited requests
6. **Keep tokens private:** Never share tokens between users or expose them publicly

---

## Example Workflow

### Complete Authentication Flow

1. **Login** to get an access token:

    ```bash
    POST /api/login
    ```

2. **Use the token** for authenticated requests:

    ```bash
    GET /api/user
    Authorization: Bearer {token}
    ```

3. **Update your profile** (optional):

    ```bash
    PUT /api/user/profile
    Authorization: Bearer {token}
    # Body: {"name": "New Name", "email": "new@example.com"}
    ```

4. **Logout** when done:
    ```bash
    POST /api/logout
    Authorization: Bearer {token}
    ```

### Root User Workflow

1. **Login** as root_user:

    ```bash
    POST /api/login
    # Returns token for root_user
    ```

2. **Create a new user**:

    ```bash
    POST /api/register
    Authorization: Bearer {root_user_token}
    ```

3. **List all users**:

    ```bash
    GET /api/users
    Authorization: Bearer {root_user_token}
    ```

4. **Send password reset link to a user**:

    ```bash
    POST /api/users/forgot-password
    Authorization: Bearer {root_user_token}
    # Body: {"user_id": 2} or {"email": "user@example.com"}
    ```

5. **Delete a user**:

    ```bash
    DELETE /api/users/{id}
    Authorization: Bearer {root_user_token}
    ```

---

## Support

For issues or questions regarding the API, please contact the development team or refer to the project documentation.

---

**Last Updated:** November 2024  
**API Version:** 1.0
