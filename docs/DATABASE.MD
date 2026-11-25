# Database Schema

 > **Status:** Schema reference for implementation, aligned with `docs/PRD.md` §5.2 as of 2026-11-25.
> Internal conversation type: `agent_admin`. Milestone status: `pending` / `completed`.
> Ticket status `closed` has no defined transition; see `docs/STATUS.md` D7.

## Tables

All primary keys use unsigned big integers unless stated otherwise. Database engine: MySQL.

### `users`

| Column | Definition |
|---|---|
| `id` | Primary key |
| `name` | Required string |
| `email` | Required, unique string |
| `password` | Required string |
| `role` | Enum: `user`, `admin`, `agent` |
| `avatar_url` | Nullable string |
| `created_at`, `updated_at` | Timestamps |

### `tickets`

| Column | Definition |
|---|---|
| `id` | Primary key |
| `ticket_number` | Required, unique string |
| `user_id` | Required FK → `users.id` (reporter) |
| `admin_id` | Nullable FK → `users.id` |
| `agent_id` | Nullable FK → `users.id` |
| `title` | Required string |
| `description` | Required text |
| `priority` | Enum: `low`, `medium`, `high` |
| `status` | Enum: `open`, `in_progress`, `pending_review`, `resolved`, `closed` |
| `resolved_at` | Nullable timestamp |
| `created_at`, `updated_at` | Timestamps |

Indexes: unique `ticket_number`; composite (`status`, `priority`); indexes on `user_id`, `admin_id`, and `agent_id`.

### `ticket_milestones`

| Column | Definition |
|---|---|
| `id` | Primary key |
| `ticket_id` | Required FK → `tickets.id` |
| `created_by` | Required FK → `users.id` |
| `title` | Required string |
| `description` | Nullable text |
| `status` | Enum: `pending`, `completed` |
| `due_date` | Nullable date |
| `completed_at` | Nullable timestamp |
| `created_at`, `updated_at` | Timestamps |

Indexes on `ticket_id` and `created_by`.

### `conversations`

| Column | Definition |
|---|---|
| `id` | Primary key |
| `ticket_id` | Required FK → `tickets.id` |
| `type` | Enum: `user_admin`, `agent_admin` |
| `created_at`, `updated_at` | Timestamps |

Unique constraint (`ticket_id`, `type`) ensures at most one room of each type per ticket.

### `messages`

| Column | Definition |
|---|---|
| `id` | Primary key |
| `conversation_id` | Required FK → `conversations.id` |
| `sender_id` | Required FK → `users.id` |
| `body` | Required text |
| `attachment_url` | Nullable string |
| `read_at` | Nullable timestamp |
| `created_at`, `updated_at` | Timestamps |

Indexes: composite (`conversation_id`, `created_at`) and `sender_id`.

### `notifications`

Use Laravel database notifications schema: UUID `id`, `type`, polymorphic `notifiable_type` / `notifiable_id`, `data`, nullable `read_at`, and timestamps.

## Relationships

- A user reports tickets through `tickets.user_id`; admin and agent references are optional.
- A ticket has many milestones and conversations; each conversation has many messages.
- Each message has one sender; each user has many database notifications.
- A ticket gets a `user_admin` room on creation and an `agent_admin` room when assigned.

## Legal Ticket Status Transitions

This is an application rule matrix, not a persisted database table.

| Current state | Action | Role | New state |
|---|---|---|---|
| No ticket | Create ticket | `user` | `open` |
| `open` | Assign to agent | `admin` | `in_progress` |
| `in_progress` | Submit completion | Assigned `agent` | `pending_review` |
| `pending_review` | Approve completion | `admin` | `resolved` |
| `resolved` | Reopen for revision | `admin` | `in_progress` |

`closed` is in the status enum, but no transition to it is defined. Do not implement one until D7 is decided.

Any transition not listed above must be rejected by the backend.