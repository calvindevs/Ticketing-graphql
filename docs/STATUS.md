# Project Status — Real-Time Ticketing System

> **Purpose:** Answer "where are we, and what is next?" in under a minute.
> **Rule for agents:** Read this *before* starting work. Update it *in the same commit* that changes the state below.
> **Handoff:** before you stop, rewrite **§7 Session Handoff** — that is how the other agent (OpenCode or Antigravity) picks up.
> **Last updated:** 2026-11-25

---

## 1. Current Phase

The roadmap is now **4 weeks / 26 days** (`docs/ROADMAP.md`). Current position: **Day 1 complete — Day 2 next.**

| Week | Days | Focus | PRD Phase | Status |
| :--- | :--- | :--- | :--- | :--- |
| **1** | Day 1–6 | Backend, Auth & GraphQL Core | 1 + 2 | 🟡 In progress — A1–A3 done |
| **2** | Day 7–13 | Vue 3 foundation, layout, 3 role dashboards | 2 | ⚪ Not started |
| **3** | Day 14–20 | Real-time chat & ticket detail | 3 | ⚪ Not started |
| **4** | Day 21–26 | Notifications, PDF export, QA & polish | 4 + 5 | ⚪ Not started |

**Overall: Day 1 done.** MySQL is connected, auth is installed and proven by a passing feature test, and 5 seed accounts exist. No product feature exists yet.

---

## 2. What Actually Exists (verified in code, not from docs)

### Backend — `backend/`

| Area | State |
| :--- | :--- |
| Framework | Laravel 12, PHP ^8.2, installed and bootable |
| GraphQL | `nuwave/lighthouse` ^6.74 installed. Route `GET|POST /graphql` is registered |
| GraphQL schema | `graphql/schema.graphql` — **default Lighthouse example only** (`user`, `users`). No ticket/chat/milestone types |
| Lighthouse config | **Not published** (`config/lighthouse.php` missing — vendor defaults in use) |
| Auth packages | ✅ `laravel/fortify` v1.44 + `laravel/sanctum` v4.5 — **cookie/session** mode. `'views' => false`, `$middleware->statefulApi()` enabled |
| Real-time | ❌ No `laravel/reverb`. `BROADCAST_CONNECTION=log` |
| PDF | ❌ No `barryvdh/laravel-dompdf` (D5 chosen, not installed yet) |
| Migrations | `users` (with `role` enum `->default('user')`, `avatar_url`), `sessions`, `password_reset_tokens`, `personal_access_tokens`, `add_two_factor_columns_to_users`, `passkeys`, plus cache/jobs. **None of the PRD product tables exist** |
| Models | `app/Models/User.php` — `role` is fillable. No other product models |
| Seeders | `UserSeeder` → 1 admin, 2 agent, 2 user (all with timestamps) |
| Tests | `tests/Feature/Auth/LoginTest.php` — 3 tests / 10 assertions, all passing (valid login, wrong password, role matches) |
| Routes | `web.php`, `api.php`, `console.php`. **No `routes/channels.php`** |
| Database | ✅ MySQL, database `ticketing`. **Not** SQLite anymore (D1) |

### Frontend — `frontend/`

| Area | State |
| :--- | :--- |
| Stack | Vue 3.5 + Vite 8 + TypeScript 6 |
| App entry | `src/main.ts` mounts `App.vue`, which renders the Vite `HelloWorld` template |
| Router | ❌ No `vue-router` — the 3 role routes from PRD §4.1 do not exist |
| State | ❌ No Pinia |
| GraphQL client | ❌ No `@vue/apollo-composable`, no Apollo Client |
| Real-time | ❌ No `laravel-echo`, no `pusher-js` |
| Styling | ❌ No Tailwind in `frontend/`. (Tailwind 4 sits in `backend/package.json`, unused by the SPA) |
| Components | `src/components/HelloWorld.vue` only. `MetricCard`, `ChatBox`, `MilestonePanel` do not exist |

### Documentation

| File | State |
| :--- | :--- |
| `docs/PRD.md` | ✅ Complete — source of truth for requirements |
| `docs/ROADMAP.md` | ✅ Complete — 26-day plan (4 weeks), daily Definition of Done, plus §0 architecture decisions that override other docs |
| `docs/UI_SPECIFICATION.md` | ✅ Complete — design tokens, layout, per-role pages |
| `docs/DATABASE.md` | ✅ Detailed schema reference aligned with PRD §5.2; includes foreign keys, indexes, timestamps, and the legal status-transition matrix. |
| `docs/LEARNING_RULES.md` | ✅ Complete |
| `AGENTS.md` | ✅ Docs index present |

---

## 3. Gaps Between Spec and Code (do not assume these work)

These are specified in the PRD but **have zero implementation**. Treat every one as not-yet-built:

1. **Product schema is incomplete:** `users` is done (`role`, `avatar_url`); `tickets`, `ticket_milestones`, `conversations`, `messages`, and `notifications` are not implemented.
2. **The entire GraphQL contract** in PRD §6 — none of the listed queries/mutations exist.
3. **The ticket state machine** (`open → in_progress → pending_review → resolved/closed`) — no server-side validation exists anywhere.
4. **Role-based authorization** — no policies, no Lighthouse directives, no `routes/channels.php`.
5. **Real-time everything** — chat, typing indicator, read receipts, notifications.
6. **PDF export endpoint** `GET /api/tickets/{id}/export-pdf`.
7. **The whole UI** — no pages, no layout, no design tokens applied.

---

## 4. Decisions & Open Questions

Decisions marked ✅ are **settled — follow them, do not reopen**. Items marked ❌ still block work:

| # | Question | Current state | Impact |
| :--- | :--- | :--- | :--- |
| D1 | **MySQL or SQLite?** | ✅ **Decided 2026-11-24 — MySQL.** Code still says `DB_CONNECTION=sqlite`. | Must switch `.env` on Day 1 before writing product migrations. |
| D2 | **Is Reverb in scope at the start?** | ✅ **Decided 2026-11-24 — install on Day 2.** Not installed yet. | Configured early so Week 3 (chat) has no surprises. |
| D3 | **Database schema convention** | ✅ **Decided 2026-11-25:** use `agent_admin` and milestone `status` (`pending` / `completed`) as specified in `docs/DATABASE.md`; PRD terminology has been aligned. | Use `docs/DATABASE.md` for database implementation. |
| D3b | **Align database schema documentation** | ✅ **Resolved 2026-11-25.** `docs/DATABASE.md`, `docs/PRD.md`, and `docs/ARCHITECTURE.md` agree on table names, conversation type, milestone status, keys, indexes, timestamps, and legal transitions. | Build migrations from `docs/DATABASE.md`; D7 (`closed`) remains open. |
| D4 | **Auth style: cookie vs token** | ✅ **Decided 2026-11-24 — Fortify + Sanctum cookie/session.** PRD §4.1 reworded to match. | `login`/`logout` are Fortify REST endpoints; only `me` is a GraphQL query. Never send `Authorization: Bearer` to first-party endpoints. |
| D5 | **PDF library** | ✅ **Decided 2026-11-24 — `barryvdh/laravel-dompdf`.** Not installed yet. | The selected library is recorded in PRD §5.1 and scheduled for Day 23. |
| D6 | **Tailwind placement** | ✅ **Decided 2026-11-24 — belongs in `frontend/`.** Currently installed in `backend/package.json`. | Move it during Day 7 setup. |
| D7 | **Status `closed`** | ❌ **Still open.** PRD §3 defines `closed` in the enum but no transition ever reaches it. | **Do not implement `closed` until this is answered.** Blocking question: does Admin set it after `resolved`, does it happen automatically, or is it out of scope for v1? |

---

## 5. Next Actions (ordered — mirrors `docs/ROADMAP.md` Day 1…)

- [x] **A1 (Day 1)** — ✅ `.env` on **MySQL** (`ticketing`); `php artisan migrate` works against it.
- [x] **A2 (Day 1)** — ✅ Fortify + Sanctum installed, `'views' => false`, `statefulApi()` enabled, `GET /login` correctly returns 405.
- [x] **A3 (Day 1)** — ✅ `role` + `avatar_url` on `users`, `role` defaults to `user`, `UserSeeder` gives 1 admin / 2 agent / 2 user, and `LoginTest` passes.
- [ ] **A4 (Day 2)** — Publish `config/lighthouse.php`, install Reverb, verify `/graphiql`.
- [ ] **A5 (Day 2)** — Auth wiring: `POST /login` + `POST /logout` via Fortify REST; GraphQL `me` query with role; confirm `@guard`/`@can` work against the **cookie session** (no `Bearer` tokens).
- [ ] **A6 (Day 3)** — Migrations + models for `tickets`, `ticket_milestones`, `conversations`, `messages` (FKs + indexes).
- [ ] **A7 (Day 3)** — Implement migrations and models from `docs/DATABASE.md`, including documented foreign keys, indexes, timestamps, and server-enforced transition rules.
- [ ] **A8 (Day 7)** — Move Tailwind into `frontend/` (D6); configure Apollo with `credentials: 'include'` (no `Authorization: Bearer`); add Vite proxy for `/login`, `/logout`, `/sanctum`, `/graphql`, `/broadcasting`.
- [ ] **BLOCKED — D7** — Answer the `closed` status question before Day 5. Until answered, implement only the transitions PRD §3 defines a trigger for (`open → in_progress → pending_review → resolved`, plus `resolved → in_progress`).

---

## 6. How to Update This File

When your change alters any row above:

1. Move the phase/status marker.
2. Add or remove entries in §2 (must match the code — verify with a search, do not recall from memory).
3. If you implemented something from §3, delete that line.
4. Check off the corresponding item in §5 and add newly discovered next actions.
5. Set **Last updated** to today.

If you cannot verify a claim by reading the code, do not write it here.

---

## 7. Session Handoff

The slot the **next** agent reads first. **Rewrite this table — do not append to it.** One table, always current.

| Field | Value |
| :--- | :--- |
| **Last agent** | OpenCode (DeepSeek) |
| **Session ended** | 2026-11-25 |
| **Last action taken** | Day 1 complete: MySQL wired, Fortify + Sanctum cookie auth (views=false, statefulApi), `role` + `avatar_url` on users, `UserSeeder` (1 admin / 2 agent / 2 user), `LoginTest` passing (3 tests / 10 assertions) |
| **Work in progress (uncommitted / unfinished)** | Day 1 code + docs **not committed yet** at the time of writing. No GQL types, no Reverb, no `channels.php` |
| **Next action for the next agent** | A4 (Day 2) — publish `config/lighthouse.php`, install Reverb, verify `/graphiql` |

Rules:

1. Fill this in on your **last** change of the session, before you stop — not at the start of the next one.
2. "Work in progress" must be true: a half-written migration, a failing test, an uncommitted file. Describe what is *actually* on disk.
3. The commit is the territory, this table is the map. **Commit before switching agents** — a handoff without a commit loses the work.
4. If what you wrote here and what the code shows disagree, the code wins. Fix the table, do not trust the note.
