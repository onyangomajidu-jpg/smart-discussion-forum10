# Data Dictionary — Smart Discussion Forum Management System
> **Document Reference:** SDD Chapter 4 — Data Design  
> **Database:** MySQL · `smart_discussion_forum` · Charset: `utf8mb4_unicode_ci`  
> **ORM:** Laravel Eloquent · Migrations: `database/migrations/` · Models: `app/Models/`

---

## 4.1 Overview

The Smart Discussion Forum transforms the information requirements of administrators,
lecturers, and members into structured data entities that support communication,
assessment, analytics, moderation, and recommendation functionalities.

The major system entities are represented as object-oriented classes containing
attributes and methods, which interact through service components to support discussion
management, assessment, moderation, analytics, synchronisation, and recommendation
functionalities.

### Data Storage and Organisation

| Storage Layer | Purpose |
|---|---|
| **Relational Database (MySQL)** | Stores structured data requiring transactional consistency: users, roles, quizzes, participation records, warnings, blacklist records, and reports |
| **NoSQL Database** | Handles high-volume communication data: discussion topics, posts, replies, and chat messages |
| **Local Desktop Cache (SQLite/IndexedDB)** | Temporarily stores offline data such as unsent messages and recently accessed discussions; synchronises with central databases on connectivity restoration |

---

## 4.2 Data Dictionary

### Table 1 — `users` · User Entity

**Description:** The User entity stores information for all users of the platform. It serves as the parent entity for Administrators, Lecturers, and Members. User information is used during authentication, authorisation, communication, and participation monitoring.

**Model:** `app/Models/User.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique user identifier |
| `name` | VARCHAR(255) | NOT NULL | Full display name |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Login and contact email |
| `password` | VARCHAR(255) | NOT NULL | Bcrypt-hashed password |
| `role` | ENUM('member','lecturer','admin') | NOT NULL, DEFAULT 'member' | Determines profile sub-table |
| `avatar` | VARCHAR(255) | NULL | Relative path to profile image |
| `bio` | TEXT | NULL | Short user biography |
| `is_active` | TINYINT(1) | NOT NULL, DEFAULT 1 | `0` = suspended account |
| `email_verified_at` | TIMESTAMP | NULL | Set on email confirmation |
| `remember_token` | VARCHAR(100) | NULL | "Remember me" cookie token |
| `created_at` | TIMESTAMP | NULL | Record creation time |
| `updated_at` | TIMESTAMP | NULL | Last modification time |

**Key Methods:** `login(email, password)`, `register(name, email, password)`

---

### Table 2 — `admins` · Administrator Entity

**Description:** Stores administrator-specific information and supports management activities such as warning issuance, user blacklisting, and report generation.

**Model:** `app/Models/Admin.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique administrator record identifier |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Owning user |
| `super_admin` | TINYINT(1) | NOT NULL, DEFAULT 0 | `1` = unrestricted system control |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Key Methods:** `issueWarning(userId)`, `blacklistUser(userId)`, `generateReport()`

---

### Table 3 — `lecturers` · Lecturer Entity

**Description:** Stores lecturer information and supports quiz creation, participation grading, and performance monitoring.

**Model:** `app/Models/Lecturer.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique lecturer record identifier |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Owning user |
| `staff_id` | VARCHAR(255) | NULL, UNIQUE | Institutional staff ID |
| `department` | VARCHAR(255) | NULL | Faculty / department name |
| `specialisation` | VARCHAR(255) | NULL | Subject area expertise |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Key Methods:** `createQuiz(title, startTime, duration, category)`, `assignMarks(memberId)`, `publishQuiz(quizId)`

---

### Table 4 — `members` · Member Entity

**Description:** Stores member information used during discussions, quizzes, recommendations, participation evaluation, and forum activities.

**Model:** `app/Models/Member.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique member record identifier |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Owning user |
| `student_id` | VARCHAR(255) | NULL, UNIQUE | Institutional student ID |
| `programme` | VARCHAR(255) | NULL | Degree programme (e.g. BSc Computer Science) |
| `year_of_study` | INT | NULL | Current academic year (1–6) |
| `reputation` | INT | NOT NULL, DEFAULT 0 | Points earned via upvotes and activity |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Key Methods:** `participateDiscussion(topicId, message)`, `answerQuestion(postId, response)`, `attemptQuiz(quizId)`

---

### Table 5 — `groups` · Group Entity

**Description:** Stores discussion groups created within the forum. Groups contain topics and have members with assigned roles.

**Model:** `app/Models/Group.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique group identifier |
| `name` | VARCHAR(255) | NOT NULL | Human-readable group name |
| `slug` | VARCHAR(255) | NOT NULL, UNIQUE | URL-safe identifier |
| `description` | TEXT | NULL | Group purpose and rules |
| `created_by` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Group creator |
| `is_private` | TINYINT(1) | NOT NULL, DEFAULT 0 | `1` = invite-only membership |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

---

### Table 5a — `group_user` · Group Membership Pivot

**Description:** Many-to-many membership between users and groups with role assignment.

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `group_id` | BIGINT UNSIGNED | PK (composite), FK → `groups.id`, CASCADE | |
| `user_id` | BIGINT UNSIGNED | PK (composite), FK → `users.id`, CASCADE | |
| `role` | ENUM('member','moderator') | NOT NULL, DEFAULT 'member' | Member's role within the group |
| `joined_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Membership start timestamp |

---

### Table 6 — `topics` · Topic Entity

**Description:** Stores discussion topics belonging to groups. Topics contain posts and can be pinned or locked by moderators.

**Model:** `app/Models/Topic.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique topic identifier |
| `group_id` | BIGINT UNSIGNED | FK → `groups.id`, CASCADE DELETE | Parent group |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Topic author |
| `title` | VARCHAR(255) | NOT NULL | Topic heading |
| `slug` | VARCHAR(255) | NOT NULL, UNIQUE | URL-safe title |
| `body` | TEXT | NOT NULL | Opening post content |
| `is_pinned` | TINYINT(1) | NOT NULL, DEFAULT 0 | `1` = pinned to top of group |
| `is_locked` | TINYINT(1) | NOT NULL, DEFAULT 0 | `1` = no new posts allowed |
| `views` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Incremented on each page view |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |
| `deleted_at` | TIMESTAMP | NULL | Soft-delete timestamp |

**Key Methods:** `createTopic(title, category)`, `filterContent(content)`

---

### Table 7 — `posts` · Post Entity

**Description:** Stores questions, messages, and discussion content submitted by users within a topic.

**Model:** `app/Models/Post.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique post identifier |
| `topic_id` | BIGINT UNSIGNED | FK → `topics.id`, CASCADE DELETE | Parent topic |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Post author |
| `body` | TEXT | NOT NULL | Post content (HTML/Markdown) |
| `is_best_answer` | TINYINT(1) | NOT NULL, DEFAULT 0 | `1` = accepted answer |
| `upvotes` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Positive vote tally |
| `downvotes` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Negative vote tally |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |
| `deleted_at` | TIMESTAMP | NULL | Soft-delete timestamp |

---

### Table 8 — `replies` · Reply Entity

**Description:** Stores responses to discussion posts. Supports threaded nesting via self-referencing parent reply.

**Model:** `app/Models/Reply.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique reply identifier |
| `post_id` | BIGINT UNSIGNED | FK → `posts.id`, CASCADE DELETE | Parent post |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Reply author |
| `parent_reply_id` | BIGINT UNSIGNED | FK → `replies.id`, SET NULL | Parent reply (`NULL` = top-level) |
| `body` | TEXT | NOT NULL | Reply content |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |
| `deleted_at` | TIMESTAMP | NULL | Soft-delete timestamp |

**Key Methods:** `answerQuestion(postId, response)`

---

### Table 9 — `notifications` · Notification Entity

**Description:** Stores alerts generated by the system such as quiz announcements, responses, warnings, and reminders.

**Model:** `app/Models/Notification.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | CHAR(36) | PK (UUID) | Unique notification identifier |
| `type` | VARCHAR(255) | NOT NULL | Fully-qualified notification class name |
| `notifiable_type` | VARCHAR(255) | NOT NULL | Polymorphic model type (e.g. `App\Models\User`) |
| `notifiable_id` | BIGINT UNSIGNED | NOT NULL | Polymorphic model ID |
| `data` | TEXT | NOT NULL | JSON notification payload |
| `read_at` | TIMESTAMP | NULL | `NULL` = unread |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

---

### Table 10 — `quizzes` · Quiz Entity

**Description:** Stores quiz configuration information created by lecturers. Quizzes lock the student interface during attempts, prevent late access, auto-submit on timeout, and instantly publish performance reports.

**Model:** `app/Models/Quiz.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique quiz identifier |
| `group_id` | BIGINT UNSIGNED | FK → `groups.id`, CASCADE DELETE | Owning group |
| `created_by` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Quiz creator (lecturer/admin) |
| `title` | VARCHAR(255) | NOT NULL | Quiz title |
| `description` | TEXT | NULL | Instructions / overview |
| `starts_at` | TIMESTAMP | NULL | Availability window start |
| `ends_at` | TIMESTAMP | NULL | Availability window end |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Key Methods:** `createQuiz(title, startTime, duration, category)`, `publishQuiz(quizId)`, `attemptQuiz(quizId)`

---

### Table 11 — `quiz_questions` · Quiz Question Entity

**Description:** Stores questions belonging to a quiz. Each question is multiple-choice with a defined correct option and mark value.

**Model:** `app/Models/QuizQuestion.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique question identifier |
| `quiz_id` | BIGINT UNSIGNED | FK → `quizzes.id`, CASCADE DELETE | Parent quiz |
| `question` | TEXT | NOT NULL | Question text |
| `options` | JSON | NOT NULL | Array of answer choice strings |
| `correct_option` | TINYINT UNSIGNED | NOT NULL | Zero-based index of correct answer |
| `marks` | TINYINT UNSIGNED | NOT NULL, DEFAULT 1 | Points awarded for correct answer |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

---

### Table 12 — `quiz_attempts` · Quiz Attempt Entity

**Description:** Stores student quiz submissions and scores. One attempt per user is enforced. Auto-submission occurs on timer expiry.

**Model:** `app/Models/QuizAttempt.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique attempt identifier |
| `quiz_id` | BIGINT UNSIGNED | FK → `quizzes.id`, CASCADE DELETE | Parent quiz |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Attempting student |
| `answers` | JSON | NOT NULL | `{ "question_id": chosen_index, … }` |
| `score` | SMALLINT UNSIGNED | NOT NULL, DEFAULT 0 | Total marks achieved |
| `submitted_at` | TIMESTAMP | NULL | Submission timestamp |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Constraint:** UNIQUE `(quiz_id, user_id)` — one attempt per user per quiz

**Key Methods:** `attemptQuiz(quizId)`, `calculateScore()`

---

### Table 13 — `warnings` · Warning Entity

**Description:** Stores warnings issued to inactive users. After two unresolved warnings, the system automatically blacklists the user temporarily.

**Model:** `app/Models/Warning.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique warning identifier |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Recipient of the warning |
| `issued_by` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Admin / moderator who issued it |
| `reason` | VARCHAR(255) | NOT NULL | Short reason category |
| `details` | TEXT | NULL | Extended explanation |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Key Methods:** `issueWarning(userId)` — checks inactivity period; if threshold exceeded, creates warning and notifies user

---

### Table 14 — `blacklists` · Blacklist Record Entity

**Description:** Stores temporary blacklist information for users who have exceeded the warning threshold or violated platform policies.

**Model:** `app/Models/Blacklist.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique blacklist record identifier |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Banned user |
| `banned_by` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Admin who issued the ban |
| `reason` | VARCHAR(255) | NOT NULL | Ban justification |
| `expires_at` | TIMESTAMP | NULL | `NULL` = permanent ban |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Business Rule:** `isActive()` returns `true` if `expires_at IS NULL OR expires_at > NOW()`

**Key Methods:** `blacklistUser(userId)` — retrieves warning count; if count > 2, blacklists user, sets duration, notifies user

---

### Table 15 — `recommendations` · Recommendation Entity

**Description:** Stores AI-generated recommendations for users. The AI Engine monitors newly created discussion threads, applies machine learning classification algorithms, identifies user interests and engagement patterns, and delivers personalised topic suggestions.

**Model:** `app/Models/Recommendation.php`

| Attribute | Type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique recommendation identifier |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE DELETE | Recipient user |
| `recommendable_type` | VARCHAR(255) | NOT NULL | Polymorphic type (`App\Models\Topic` or `App\Models\Group`) |
| `recommendable_id` | BIGINT UNSIGNED | NOT NULL | Polymorphic record ID |
| `score` | FLOAT | NOT NULL, DEFAULT 0 | Relevance / ranking score |
| `generated_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Algorithm execution timestamp |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Key Methods:** `generateRecommendation(userId)` — retrieves user activity, analyses interests, identifies relevant topics, generates and stores recommendations

---

## 4.3 Service Entity Reference

The following service entities are defined in SDD Chapter 4 as logical components that
operate on the data tables above. They do not have independent database tables but
interact through the relational schema.

| Service Entity | Responsibility | Operates On |
|---|---|---|
| **User Management Service** | Manages registration, login, logout, and password recovery. Enforces platform rule acceptance on onboarding | `users`, `members`, `lecturers`, `admins` |
| **Content Management Service** | Detects and filters irrelevant or spam content posted in discussions, organises conversations into structured threads, generates PDF exports, integrates with external social media platforms | `topics`, `posts`, `replies` |
| **Real-time Sync Service** | Maintains continuous communication using WebSockets, delivers instant message updates across web and desktop clients, synchronises offline data on reconnection | `topics`, `posts`, `replies`, `notifications` |
| **Assessment Service** | Configures and manages quizzes, sends announcements and reminders, controls lockdown interface during quiz, tracks individual countdown timers, performs auto-submission on expiry, produces performance reports | `quizzes`, `quiz_questions`, `quiz_attempts` |
| **Analytics Service** | Collects and analyses activity data from across the platform, produces reports and dashboards tailored to lecturers, administrators, and group managers | `quiz_attempts`, `warnings`, `posts`, `topics` |
| **AI Engine / Recommendation Service** | Executes machine learning algorithms in the background, automatically categorises discussion topics based on content, generates personalised recommendations using historic engagement data and user behaviour patterns | `recommendations`, `topics`, `posts` |
| **Discussion Forum Entity** | Top-level structural container; Discussion Forum contains Groups, each Group contains Topics, Topics contain Posts, and Posts contain Replies | `groups`, `topics`, `posts`, `replies` |

---

## 4.4 Entity Relationship Summary

```
users ──────────< members              (1 : 0..1)
users ──────────< lecturers            (1 : 0..1)
users ──────────< admins               (1 : 0..1)
users >────────< groups                (M : M  via group_user)
groups ─────────< topics               (1 : M)
topics ─────────< posts                (1 : M)
posts ──────────< replies              (1 : M, self-referencing for nesting)
groups ─────────< quizzes              (1 : M)
quizzes ────────< quiz_questions       (1 : M)
quizzes ────────< quiz_attempts        (1 : M)
users ──────────< warnings             (1 : M  — warned user)
users ──────────< warnings             (1 : M  — issuing admin)
users ──────────< blacklists           (1 : M  — banned user)
users ──────────< blacklists           (1 : M  — banning admin)
users ──────────< notifications        (polymorphic notifiable)
users ──────────< recommendations      (1 : M  → Topic | Group)
```

---

## 4.5 Migration and Model File Map

| Entity (SDD Ch.4) | Laravel Model | Migration File |
|---|---|---|
| User | `app/Models/User.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Administrator | `app/Models/Admin.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Lecturer | `app/Models/Lecturer.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Member | `app/Models/Member.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Group | `app/Models/Group.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Topic | `app/Models/Topic.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Post | `app/Models/Post.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Reply | `app/Models/Reply.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Notification | `app/Models/Notification.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Quiz | `app/Models/Quiz.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Quiz Question | `app/Models/QuizQuestion.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Quiz Attempt | `app/Models/QuizAttempt.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Warning | `app/Models/Warning.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Blacklist Record | `app/Models/Blacklist.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Recommendation | `app/Models/Recommendation.php` | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Participation Record | *(via quiz_attempts + analytics)* | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Assessment Report | *(generated at runtime)* | — |
| Social Share Record | *(future implementation)* | — |
