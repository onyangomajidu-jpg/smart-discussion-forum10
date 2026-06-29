# Data Dictionary — Smart Discussion Forum
> Matches SDD Chapter 4 | Database: MySQL (`smart_discussion_forum`)

---

## Table: `users`
Base identity table for all system actors.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique user identifier |
| name | VARCHAR(255) | NOT NULL | Full display name |
| email | VARCHAR(255) | NOT NULL, UNIQUE | Login email address |
| password | VARCHAR(255) | NOT NULL | Bcrypt-hashed password |
| role | ENUM | NOT NULL, DEFAULT 'member' | 'member' / 'lecturer' / 'admin' |
| avatar | VARCHAR(255) | NULL | Path to profile image |
| bio | TEXT | NULL | Short user biography |
| is_active | TINYINT(1) | DEFAULT 1 | Account active flag |
| email_verified_at | TIMESTAMP | NULL | Email verification timestamp |
| remember_token | VARCHAR(100) | NULL | "Remember me" auth token |
| created_at | TIMESTAMP | NULL | Record creation time |
| updated_at | TIMESTAMP | NULL | Last update time |

---

## Table: `members`
Extended profile for users with role = 'member'.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | Owning user |
| student_id | VARCHAR(255) | NULL, UNIQUE | Institutional student ID |
| programme | VARCHAR(255) | NULL | Degree programme name |
| year_of_study | INT | NULL | Current year (1–6) |
| reputation | INT | DEFAULT 0 | Points earned via upvotes |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `lecturers`
Extended profile for users with role = 'lecturer'.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | Owning user |
| staff_id | VARCHAR(255) | NULL, UNIQUE | Institutional staff ID |
| department | VARCHAR(255) | NULL | Faculty / department name |
| specialisation | VARCHAR(255) | NULL | Subject area expertise |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `admins`
Extended profile for users with role = 'admin'.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | Owning user |
| super_admin | TINYINT(1) | DEFAULT 0 | Full system control flag |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `groups`
Discussion groups / communities.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| name | VARCHAR(255) | NOT NULL | Group display name |
| slug | VARCHAR(255) | NOT NULL, UNIQUE | URL-friendly identifier |
| description | TEXT | NULL | Group purpose / rules |
| created_by | BIGINT UNSIGNED | FK → users.id, CASCADE | Group creator |
| is_private | TINYINT(1) | DEFAULT 0 | Invite-only flag |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `group_user` *(pivot)*
Group membership with roles.

| Column | Type | Constraints | Description |
|---|---|---|---|
| group_id | BIGINT UNSIGNED | FK → groups.id, CASCADE | |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | |
| role | ENUM | DEFAULT 'member' | 'member' / 'moderator' |
| joined_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Membership start time |
| PRIMARY KEY | (group_id, user_id) | | Composite PK |

---

## Table: `topics`
Discussion threads within a group.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| group_id | BIGINT UNSIGNED | FK → groups.id, CASCADE | Parent group |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | Topic author |
| title | VARCHAR(255) | NOT NULL | Topic heading |
| slug | VARCHAR(255) | NOT NULL, UNIQUE | URL-friendly title |
| body | TEXT | NOT NULL | Opening post content |
| is_pinned | TINYINT(1) | DEFAULT 0 | Pinned to top of group |
| is_locked | TINYINT(1) | DEFAULT 0 | No new posts allowed |
| views | INT UNSIGNED | DEFAULT 0 | View counter |
| created_at / updated_at | TIMESTAMP | | |
| deleted_at | TIMESTAMP | NULL | Soft-delete timestamp |

---

## Table: `posts`
Individual contributions within a topic.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| topic_id | BIGINT UNSIGNED | FK → topics.id, CASCADE | Parent topic |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | Post author |
| body | TEXT | NOT NULL | Post content (HTML/Markdown) |
| is_best_answer | TINYINT(1) | DEFAULT 0 | Marked as accepted answer |
| upvotes | INT UNSIGNED | DEFAULT 0 | Positive vote count |
| downvotes | INT UNSIGNED | DEFAULT 0 | Negative vote count |
| created_at / updated_at | TIMESTAMP | | |
| deleted_at | TIMESTAMP | NULL | Soft-delete timestamp |

---

## Table: `replies`
Nested replies to posts (threaded).

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| post_id | BIGINT UNSIGNED | FK → posts.id, CASCADE | Parent post |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | Reply author |
| parent_reply_id | BIGINT UNSIGNED | FK → replies.id, NULL | Parent reply (nesting) |
| body | TEXT | NOT NULL | Reply content |
| created_at / updated_at | TIMESTAMP | | |
| deleted_at | TIMESTAMP | NULL | Soft-delete timestamp |

---

## Table: `quizzes`
Knowledge-check quizzes attached to a group.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| group_id | BIGINT UNSIGNED | FK → groups.id, CASCADE | Owning group |
| created_by | BIGINT UNSIGNED | FK → users.id, CASCADE | Quiz creator (lecturer/admin) |
| title | VARCHAR(255) | NOT NULL | Quiz title |
| description | TEXT | NULL | Instructions |
| starts_at | TIMESTAMP | NULL | Availability start |
| ends_at | TIMESTAMP | NULL | Availability end |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `quiz_questions`
Individual questions within a quiz.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| quiz_id | BIGINT UNSIGNED | FK → quizzes.id, CASCADE | Parent quiz |
| question | TEXT | NOT NULL | Question text |
| options | JSON | NOT NULL | Array of answer choices |
| correct_option | TINYINT UNSIGNED | NOT NULL | Index of correct choice |
| marks | TINYINT UNSIGNED | DEFAULT 1 | Points for correct answer |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `quiz_attempts`
User quiz submission records.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| quiz_id | BIGINT UNSIGNED | FK → quizzes.id, CASCADE | |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | |
| answers | JSON | NOT NULL | {question_id: chosen_index} |
| score | SMALLINT UNSIGNED | DEFAULT 0 | Total marks achieved |
| submitted_at | TIMESTAMP | NULL | Submission timestamp |
| UNIQUE | (quiz_id, user_id) | | One attempt per user |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `warnings`
Moderation warnings issued to users.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | Warned user |
| issued_by | BIGINT UNSIGNED | FK → users.id, CASCADE | Admin / moderator |
| reason | VARCHAR(255) | NOT NULL | Short reason category |
| details | TEXT | NULL | Extended explanation |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `blacklists`
Banned users (temporary or permanent).

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | Banned user |
| banned_by | BIGINT UNSIGNED | FK → users.id, CASCADE | Admin who issued ban |
| reason | VARCHAR(255) | NOT NULL | Ban justification |
| expires_at | TIMESTAMP | NULL | NULL = permanent ban |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `notifications`
Laravel polymorphic notification store.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | CHAR(36) | PK (UUID) | Unique notification ID |
| type | VARCHAR(255) | NOT NULL | Notification class name |
| notifiable_type | VARCHAR(255) | NOT NULL | Polymorphic model type |
| notifiable_id | BIGINT UNSIGNED | NOT NULL | Polymorphic model ID |
| data | TEXT | NOT NULL | JSON notification payload |
| read_at | TIMESTAMP | NULL | NULL = unread |
| created_at / updated_at | TIMESTAMP | | |

---

## Table: `recommendations`
AI/algorithm-generated content suggestions per user.

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | Recipient user |
| recommendable_type | VARCHAR(255) | NOT NULL | Polymorphic type (Topic/Group) |
| recommendable_id | BIGINT UNSIGNED | NOT NULL | Polymorphic ID |
| score | FLOAT | DEFAULT 0 | Relevance / ranking score |
| generated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Algorithm run time |
| created_at / updated_at | TIMESTAMP | | |

---

## Relationships Summary

```
users ──< members          (1:1)
users ──< lecturers        (1:1)
users ──< admins           (1:1)
users >──< groups          (M:M via group_user)
groups ──< topics          (1:M)
topics ──< posts           (1:M)
posts  ──< replies         (1:M, self-referencing for nesting)
groups ──< quizzes         (1:M)
quizzes ──< quiz_questions (1:M)
quizzes ──< quiz_attempts  (1:M)
users  ──< warnings        (1:M)
users  ──< blacklists      (1:M)
users  ──< notifications   (polymorphic)
users  ──< recommendations (polymorphic)
```
