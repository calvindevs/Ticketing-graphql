# Project Status — Real-Time Ticketing System

> **Purpose:** Answer "where are we, and what is next?" in under a minute.
> **Rule for agents:** Read this *before* starting work. Update it *in the same commit* that changes the state below.
> **Last updated:** 2026-11-24

---

## 1. Current Phase

The roadmap is now **4 weeks / 26 days** (`docs/ROADMAP.MD`). Current position: **Day 0 — nothing started.**

| Week | Days | Focus | PRD Phase | Status |
| :--- | :--- | :--- | :--- | :--- |
| **1** | Day 1–6 | Backend, Auth & GraphQL Core | 1 + 2 | ⚪ Not started |
| **2** | Day 7–13 | Vue 3 foundation, layout, 3 role dashboards | 2 | ⚪ Not started |
| **3** | Day 14–20 | Real-time chat & ticket detail | 3 | ⚪ Not started |
| **4** | Day 21–26 | Notifications, PDF export, QA & polish | 4 + 5 | ⚪ Not started |

**Overall: before Day 1.** The repo contains framework scaffolding plus the specification documents. No product feature exists yet.

---

## 2. What Actually Exists (verified in code, not from docs)

### Backend — `backend/`

| Area | State |
| :--- | :--- |
| Framework | Laravel 12, PHP ^8.2, installed and bootable |
| GraphQL | `nuwave/lighthouse` ^6.74 installed. Route `GET|POST /graphql` is registered |
| GraphQL schema | `graphql/schema.graphql` — **default Lighthouse example only** (`user`, `users`). No ticket/chat/milestone types |
| Lighthouse config | **Not published** (`config/lighthouse.php` missing — vendor defaults in use) |
| Auth packages | ❌ No `laravel/fortify`, no `laravel/sanctum` (both required Day 1, D4) |
| Real-time | ❌ No `laravel/reverb`. `BROADCAST_CONNECTION=log` |
| PDF | ❌ No `barryvdh/laravel-dompdf` (D5 chosen, not installed yet) |
| Migrations | 3 files: `users`, `cache`, `jobs` (framework defaults). **None of the 6 PRD tables exist** |
| Models | `app/Models/User.php` only |
| Seeders | `DatabaseSeeder.php` (default, empty of product data) |
| Tests | `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` (framework defaults) |
| Routes | `routes/web.php` (default `/` view), `routes/console.php`. **No `routes/channels.php`** |
| Database | `DB_CONNECTION=sqlite` — `backend/database/database.sqlite` (must switch to MySQL, D1) |

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
| `docs/ROADMAP.MD` | ✅ Complete — 26-day plan (4 weeks), daily Definition of Done, plus §0 architecture decisions that override other docs |
| `docs/UI_SPECIFICATION.md` | ✅ Complete — design tokens, layout, per-role pages |
| `docs/DATABASE.MD` | ⚠️ **Draft updated 2026-11-24, mostly aligned with PRD §5.2.** Remaining conflicts: (1) `conversations.type` = `agent_admin` vs PRD `admin_agent`; (2) milestone uses `status` enum instead of PRD's `is_completed` boolean; (3) table named `message` (singular) vs PRD `messages` (plural); (4) no foreign keys on `admin_id`/`agent_id`/`created_by`/`sender_id`; (5) no indexes; (6) no timestamps on most tables; (7) `resolved_at` missing from tickets; (8) no status-transition table. **PRD §5.2 is still authoritative.** See D3b. |
| `docs/LEARNING_RULES.MD` | ✅ Complete |
| `AGENTS.md` | ✅ Docs index present |

---

## 3. Gaps Between Spec and Code (do not assume these work)

These are specified in the PRD but **have zero implementation**. Treat every one as not-yet-built:

1. **All 6 database tables** from PRD §5.2 (`tickets`, `ticket_milestones`, `conversations`, `messages`, plus role column on `users`).
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
| D3 | **`DATABASE.MD` content** | ⚠️ **Draft updated 2026-11-24, mostly aligned with PRD §5.2 but still has conflicts.** | Remaining conflicts listed in D3b. Until fully resolved, **PRD §5.2 wins**. Scheduled for Day 3. |
| D3b | **Reconcile `DATABASE.MD` draft with PRD §5.2** | ❌ **Open — needs your ruling.** Remaining conflicts: (1) `conversations.type` = `agent_admin` vs PRD `admin_agent`; (2) milestone uses `status` enum(pending,completed) vs PRD `is_completed` boolean + `completed_at`; (3) table `message` singular vs PRD `messages` plural; (4) missing FKs on `admin_id`/`agent_id`/`created_by`/`sender_id`; (5) no indexes; (6) no timestamps on most tables; (7) `resolved_at` missing from tickets; (8) no status-transition table. | Day 3 blocks on this. |
| D4 | **Auth style: cookie vs token** | ✅ **Decided 2026-11-24 — Fortify + Sanctum cookie/session.** PRD §4.1 reworded to match. | `login`/`logout` are Fortify REST endpoints; only `me` is a GraphQL query. Never send `Authorization: Bearer` to first-party endpoints. |
| D5 | **PDF library** | ✅ **Decided 2026-11-24 — `barryvdh/laravel-dompdf`.** Not installed yet. | PRD §5.1 lists two options; the choice is now fixed for Day 23. |
| D6 | **Tailwind placement** | ✅ **Decided 2026-11-24 — belongs in `frontend/`.** Currently installed in `backend/package.json`. | Move it during Day 7 setup. |
| D7 | **Status `closed`** | ❌ **Still open.** PRD §3 defines `closed` in the enum but no transition ever reaches it. | **Do not implement `closed` until this is answered.** Blocking question: does Admin set it after `resolved`, does it happen automatically, or is it out of scope for v1? |

---

## 5. Next Actions (ordered — mirrors `docs/ROADMAP.MD` Day 1…)

- [ ] **A1 (Day 1)** — Switch `.env` to **MySQL** (D1), confirm `php artisan migrate` works against it.
- [ ] **A2 (Day 1)** — Install Fortify + Sanctum: `composer require laravel/fortify`, `fortify:install`, `install:api`, set `'views' => false`, enable `$middleware->statefulApi()` (D4).
- [ ] **A3 (Day 1)** — Add `role` column to `users` (`user`/`admin`/`agent`) + seeder: 1 Admin, 2 Agent, 2 User.
- [ ] **A4 (Day 2)** — Publish Lighthouse config, install Reverb (D2), verify `/graphiql`.
- [ ] **A5 (Day 2)** — Auth wiring: `POST /login` + `POST /logout` via Fortify REST; GraphQL `me` query with role; confirm `@guard`/`@can` work against the **cookie session** (no `Bearer` tokens).
- [ ] **A6 (Day 3)** — Migrations + models for `tickets`, `ticket_milestones`, `conversations`, `messages` (FKs + indexes).
- [ ] **A7 (Day 3)** — Reconcile `docs/DATABASE.MD` draft against PRD §5.2 (**D3b — blocked on your ruling**), then add what the draft is missing: foreign keys, indexes, and the legal status-transition table with which role triggers each (closes D3).
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
