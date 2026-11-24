# Architecture — Real-Time Ticketing System

> **Purpose:** Tell an agent *where code belongs* and *how a request travels*, so it does not have to re-discover the structure each session.
> **Scope:** This describes the **target** architecture from `docs/PRD.md` §5. Sections marked ⚠️ **NOT BUILT** do not exist in the code yet.
> **Current state:** see `docs/STATUS.md`.

---

## 1. High-Level Diagram

```
┌───────────────────────────  frontend/  ────────────────────────────┐
│  Vue 3 + TypeScript + Pinia + Vue Router + Tailwind CSS            │
│                                                                    │
│  Apollo Client (@vue/apollo-composable)      laravel-echo + ws     │
│        │  GraphQL query/mutation                │  subscribe       │
└────────┼────────────────────────────────────────┼──────────────────┘
         │ HTTPS                                  │ WebSocket (Reverb)
         ▼                                        ▼                   
┌────────────────────────────  backend/  ────────────────────────────┐
│  Laravel 12                                                        │
│                                                                    │
│  /graphql ─► Lighthouse ─► Resolver ─► Policy ─► Eloquent ─► DB    │
│                                   (authorization)                  │
│                                                                    │
│  GraphQL mutation ─► Eloquent event ─► Event (ShouldBroadcast)     │
│                                                              │     │
│                                                              ▼     │
│                                   Reverb server ─► broadcast       │
│                                   channels (private)  events       │
│                                   notifications table (database)   │
│                                                                    │
│  GET /api/tickets/{id}/export-pdf ─► DOMPDF ─► PDF download        │
└────────────────────────────────────────────────────────────────────┘
```

Three separate transports, per PRD §5.1:

| Transport | Purpose | Libraries |
| :--- | :--- | :--- |
| GraphQL over HTTPS | All reads and writes (CRUD) | `nuwave/lighthouse` ↔ `@vue/apollo-composable` |
| WebSocket | Chat messages, typing, read receipts, notifications | `laravel/reverb` ↔ `laravel-echo` + `pusher-js` |
| REST over HTTPS | Binary file download (PDF), because GraphQL returns JSON not bytes | Laravel Controller ↔ Axios with `responseType: 'blob'` |

**Rule:** never return a PDF through GraphQL, and never return a list of tickets through REST. Keep each transport to its purpose.

---

## 2. Repository Layout

```
Ticketing/
├── AGENTS.md                  # Behavior rules + docs index (read first)
├── README.md
├── docs/                      # Specification — source of truth
│   ├── PRD.md                 # Requirements, roles, state machine, GraphQL contract
│   ├── ROADMAP.MD             # 26-day plan + §0 binding architecture decisions
│   ├── UI_SPECIFICATION.md    # Design tokens, layout, per-role pages
│   ├── STATUS.md              # Where we are now + next actions
│   ├── ARCHITECTURE.md        # This file
│   ├── DATABASE.MD            # Schema draft — ⚠️ conflicts with PRD (see STATUS.md D3)
│   └── LEARNING_RULES.MD      # Mentor/student rules
├── learning/                  # Shared learning logs (mistakes, concepts, questions, review)
├── backend/                   # Laravel 12
│   ├── graphql/schema.graphql # Single schema file (Lighthouse convention here)
│   ├── app/
│   │   ├── Models/            # Eloquent models
│   │   ├── GraphQL/           # ⚠️ NOT BUILT — custom resolvers if directives are not enough
│   │   ├── Policies/          # ⚠️ NOT BUILT — per-model authorization
│   │   ├── Events/            # ⚠️ NOT BUILT — ShouldBroadcast events
│   │   ├── Notifications/     # ⚠️ NOT BUILT — database + broadcast channels
│   │   └── Http/Controllers/  # REST only (PDF export)
│   ├── database/migrations/   # ⚠️ only framework defaults exist
│   ├── routes/
│   │   ├── web.php            # SPA catch-all + REST PDF endpoint
│   │   ├── channels.php       # ⚠️ NOT BUILT — WebSocket channel authorization
│   │   └── console.php
│   └── config/                # ⚠️ lighthouse.php not published yet
└── frontend/                  # Vue 3 + Vite + TypeScript
    ├── src/
    │   ├── main.ts            # App entry — currently mounts HelloWorld
    │   ├── App.vue
    │   ├── router/            # ⚠️ NOT BUILT — role guards live here
    │   ├── stores/            # ⚠️ NOT BUILT — Pinia
    │   ├── apollo/            # ⚠️ NOT BUILT — Apollo Client setup
    │   ├── echo/              # ⚠️ NOT BUILT — Laravel Echo setup
    │   ├── components/        # MetricCard, ChatBox, MilestonePanel, ...
    │   ├── views/             # Per-role pages
    │   └── graphql/           # ⚠️ NOT BUILT — .graphql documents / typed operations
    └── vite.config.ts
```

> **Layout:** `backend/` (Laravel) and `frontend/` (Vue 3) sit side by side at the repo root. There is no `apps/` wrapper.

### Where does new code go? Quick map

| You are adding… | Put it in |
| :--- | :--- |
| A field or relationship readable via API | `backend/graphql/schema.graphql` |
| Authorization beyond what a directive can express | `backend/app/Policies/` + a `@can`/`@policy` directive in the schema |
| A status change with side effects | Domain logic in the model / a dedicated class — **not** in the resolver |
| A new table | `backend/database/migrations/` + `backend/app/Models/` |
| A real-time event | `backend/app/Events/` (implements `ShouldBroadcast`) |
| A WebSocket permission rule | `backend/routes/channels.php` |
| A notification | `backend/app/Notifications/` with `channels: ['database', 'broadcast']` |
| PDF rendering | `backend/app/Http/Controllers/` + a Blade view in `resources/views/` |
| A new page | `frontend/src/views/` + a route in `frontend/src/router/` |
| Reusable UI | `frontend/src/components/` |
| Shared client state (current user, filters) | `frontend/src/stores/` (Pinia) |
| A GraphQL operation used by the client | `frontend/src/graphql/` |

---

## 3. Ticket State Machine (PRD §3)

This is the single most important domain rule. It is **server-enforced**, never merely hidden in the UI (PRD §7).

```
              ┌───────────────────────────────────────────┐
              │                                           │
              ▼                                           │
  User creates ──► open ──► in_progress ──► pending_review ──► resolved
  (createTicket)    │           ▲   │            ▲              │
                    │           │   │            │              │
                    │           │   └── Agent    │              │
                    │           │   submits      │              │
                    │           │   completion   │              │
                    │           │               │              │
                    │      Admin reopens ────────┘              │
                    │      (reopenTicket)                      │
                    │                                          │
                    └──────────────────────────────────────────┘
                       Admin may also set: closed (NO TRIGGER DEFINED — STATUS.md D7)
```

| Transition | Trigger | Who may do it | New status |
| :--- | :--- | :--- | :--- |
| Create | `createTicket` | `user` (creator) | `open` |
| Accept & assign | `assignTicket` | `admin` | `in_progress` — also fills `admin_id`, `agent_id`, creates `admin_agent` conversation |
| Submit for review | `submitTicketCompletion` | `agent` (assigned) | `pending_review` |
| Verify | `resolveTicket` | `admin` | `resolved` |
| Send back | `reopenTicket` | `admin` | `in_progress` |
| Close | ❌ **undefined** | ? | `closed` |

> **`closed` is unresolved (STATUS.md D7).** No trigger exists in PRD §3. Do **not** implement a transition into `closed` until the question is answered. The enum value exists in the schema only so that historical data can be read.

Any other transition must be rejected by the backend with a clear error, regardless of what the UI shows.

---

## 4. Data Model & Relationships (PRD §5.2)

```
users 1──* tickets (as reporter: user_id)
users 1──* tickets (as handler: admin_id)
users 1──* tickets (as worker: agent_id)
tickets 1──* ticket_milestones
tickets 1──* conversations
conversations 1──* messages
users 1──* messages (sender_id)
users 1──* notifications (Laravel default)
```

**Two conversation rows per ticket, created at different moments:**

| Type | Created when | Participants |
| :--- | :--- | :--- |
| `user_admin` | ticket is created (`open`) | reporter + admin |
| `admin_agent` | ticket is assigned (`in_progress`) | admin + agent |

The `admin_agent` room must never be readable by the reporter — this is the core privacy requirement (PRD §4.3, §7).

Enums:
- `users.role`: `user` | `admin` | `agent`
- `tickets.priority`: `low` | `medium` | `high`
- `tickets.status`: `open` | `in_progress` | `pending_review` | `resolved` | `closed`
- `conversations.type`: `user_admin` | `admin_agent`

> Full column list: `docs/PRD.md` §5.2. `docs/DATABASE.MD` holds a draft; it must be reconciled with §5.2 (relations, constraints, transition rules) before Day 3 — see `STATUS.md` D3b.

---

## 5. Authorization — Two Places, Both Mandatory

**How a user is identified:** Laravel Fortify handles `/login`, registration, password reset (headless — `config/fortify.php` has `'views' => false`, and the Vue app renders the login page). Laravel Sanctum provides **cookie/session-based SPA auth** (`$middleware->statefulApi()`, frontend host listed in `SANCTUM_STATEFUL_DOMAINS`). No `Authorization: Bearer` tokens are used for first-party requests. Flow: `GET /sanctum/csrf-cookie` → `POST /login` → subsequent GraphQL/broadcast requests carry the session cookie.

An agent must implement **both** layers. Passing one does not satisfy the requirement.

**Layer 1 — GraphQL (REST-equivalent requests)**

- Resolve *whether this user may perform this operation*.
- Mechanism: Lighthouse directives (`@can`, `@policy`, `@guard`) backed by Laravel Policies.
- Applies to every query and mutation in `docs/PRD.md` §6.

**Layer 2 — WebSocket (`routes/channels.php`)**

- Resolve *whether this user may subscribe to this channel*.
- Channel `conversation.{id}`: only a participant of that conversation's ticket.
- Channel `App.Models.User.{id}`: only the matching user.
- Without this layer, any authenticated user can read any chat by guessing an ID.

**Layer 3 — PDF endpoint (REST)**

- `GET /api/tickets/{id}/export-pdf` must run its own policy check before rendering.

Checklist when adding any new data access:

- [ ] Is it behind a policy in the GraphQL layer?
- [ ] If it is broadcast or subscribable, is the channel authorized?
- [ ] If it is a REST route, does it check authorization itself?

---

## 6. Real-Time Flow (PRD §4.3, §4.5)

**Sending a chat message:**

```
Vue ChatBox
  → Apollo mutation sendMessage(conversationId, body)
      → Lighthouse resolver
          → Policy check: sender is a participant?
          → persist messages row
          → broadcast MessageSent  ──►  Reverb  ──►  conversation.{id}
                                                        │
                                other participants ◄────┘  (Echo listener appends bubble)
```

**Typing indicator** is deliberately *not* stored: it uses Echo's `.whisper('typing', ...)` so it never touches the database and leaves no rows behind.

**Read receipts:** `markConversationAsRead` mutation sets `read_at` on unread messages, and broadcasts `MessagesRead` so the sender's ticks update live.

**Notifications:** a single Laravel notification class with `channels: ['database', 'broadcast']`:
- `database` → history for the bell dropdown (persisted, re-readable)
- `broadcast` → instant toast via `App.Models.User.{id}`

Triggers (PRD §4.5): new ticket → admins; assignment → agent; new message → recipient; completion submitted → admin; resolved/milestone updated → reporter.

**Performance acceptance criterion:** message or notification visible on the receiver's screen in **< 1 second without a page reload** (PRD §7).

---

## 7. Design Tokens (from `docs/UI_SPECIFICATION.md`)

Use these exact values; do not invent new blues or greys.

| Role | Hex | Used for |
| :--- | :--- | :--- |
| Dark sidebar surface | `#0f172a` | Sidebar background, headings, metric numbers |
| Main canvas | `#f1f5f9` | Page background, **incoming** chat bubbles |
| Card/modal surface | `#ffffff` | Cards, tables, forms (`rounded-2xl`) |
| Primary brand & CTA | `#2563eb` | Main buttons, active menu/tab, **outgoing** bubbles |
| Secondary accent/hover | `#3b82f6` | Button hover, chart lines |
| Soft outline/border | `#93c5fd` | Focused input border, chart accents |
| Soft tint/icon badge | `#e0f2fe` | Icon badges, light status badges, table row hover |

Layout: sidebar fixed `240px` dark; main canvas light. Radius: `rounded-2xl` for cards, `rounded-xl` for buttons/inputs. Detail pages split ~65% chat / ~35% milestones.

---

## 8. Conventions Worth Following

- **Schema is the contract.** Change `graphql/schema.graphql` first; let Lighthouse generate the plumbing; reach for custom resolvers only when directives cannot express the rule.
- **State transitions belong in the domain, not the resolver.** A mutation resolver should validate permission and delegate; the transition rules themselves live with the model so they cannot be bypassed by a future REST or queue path.
- **Never trust the client for status, role, or ownership.** The UI may hide a button; the backend must still refuse the request.
- **Server-rendered PDF is the only binary path** — everything else is JSON over GraphQL.
- **Keep `docs/` in sync with code.** If behavior changes, update `PRD.md`/`UI_SPECIFICATION.md`/`STATUS.md` in the same commit.

---

## 9. Open Architecture Questions

**Resolved (2026-11-24):**

* ✅ **Auth stack:** Laravel Fortify + Laravel Sanctum, **cookie/session SPA auth** (not API tokens). `login`/`logout` are Fortify REST endpoints; only `me` is GraphQL. See §5 and `ROADMAP.MD` §0.
* ✅ **MySQL vs SQLite:** MySQL. `.env` still says `sqlite` — switch on Day 1 (`docs/STATUS.md` D1).
* ✅ **When to install Reverb:** Day 2 (`docs/STATUS.md` D2).
* ✅ **PDF library:** `barryvdh/laravel-dompdf` (`docs/STATUS.md` D5).
* ✅ **Tailwind placement:** `frontend/` — the copy in `backend/package.json` is misplaced and should be removed on Day 7 (`docs/STATUS.md` D6).

**Unresolved — do not assume an answer:**

1. **Status `closed`.** Enum exists, no transition reaches it. Blocked on `docs/STATUS.md` D7. Implement nothing for it until answered.
2. **`DATABASE.MD` is a draft that conflicts with PRD §5.2** (table names, `type` enum, `is_completed`, plus no FKs/indexes/transition table). PRD §5.2 remains authoritative until D3b is answered (`docs/STATUS.md`).
