<div align="center">

# 🏆 Intramural Event Management System

**A full-stack web application for managing intramural events, tracking competition scores, and displaying real-time leaderboards.**

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=flat-square&logo=sqlite&logoColor=white)](https://sqlite.org)
[![Vite](https://img.shields.io/badge/Vite-7-646CFF?style=flat-square&logo=vite&logoColor=white)](https://vitejs.dev)

</div>

---

## Overview

This system is designed for organizing intramural events — managing participating teams, defining competitions across multiple categories (Sports, Cultural, Academic, etc.), entering per-competition scores, and surfacing aggregated leaderboards to both admins and the public.

Each event is self-contained with its own teams and competitions. Scores entered per competition automatically roll up into an event-wide leaderboard ranked by total points. The public leaderboard is designed for live display on screens during events, with auto-refresh and a podium-style UI.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 12 |
| Reactive UI | Livewire 4 (Single File Components) |
| UI Component Library | Flux UI (`livewire/flux`) |
| CSS Framework | Tailwind CSS v4 |
| JS Interop | Alpine.js |
| Frontend Build | Vite 7 + React 19 (Sileo bridge) |
| Database | SQLite |
| Auth Scaffolding | Laravel Fortify |
| Toast Notifications | `superbyte23/sileo-livewire` |

---

## Features

- 📅 **Event CRUD** — create, search, edit, and soft-delete events
- 👥 **Team Management** — per-event teams with avatar uploads, color accents, and `represents` labels
- 🥊 **Competition Management** — categorized competitions per event with inline winner display
- 🎯 **Score Entry** — flyout score panel with upsert logic; live-ranked leaderboard per competition
- 🏅 **Aggregated Leaderboard** — total-score ranking across all competitions, excluding soft-deleted data
- 🌐 **Public Leaderboard** — podium-style display page, 60s auto-refresh, dark mode toggle
- 📊 **Team Stats Modal** — per-team breakdown: total points, win rate, placement distribution, competition history
- 🏆 **Game Summary Modal** — cross-competition results table showing 1st–4th per event
- 🔔 **Toast Notifications** — Sileo toast system integrated across all CRUD actions
- 🗑️ **Soft Delete Cascade** — deleting an event cascades through teams, competitions, and results

---

## Project Structure

```
app/
├── Livewire/
│   ├── Actions/           # Logout action
│   ├── Concerns/
│   │   └── HasSileoToasts.php
│   └── Management/
│       └── EventsManagement.php   # (legacy, superseded by SFC)
├── Models/
│   ├── Event.php
│   ├── Team.php
│   ├── Competition.php
│   └── Result.php
│
resources/views/
├── pages/                          # Routed Livewire page components (⚡ = SFC)
│   ├── ⚡events.blade.php
│   ├── ⚡event-dashboard.blade.php
│   ├── ⚡competition-dashboard.blade.php
│   ├── ⚡event-leaderboard.blade.php
│   └── ⚡users.blade.php
├── components/
│   └── events/                     # Embedded sub-components
│       ├── ⚡leaderboard.blade.php
│       ├── ⚡teams.blade.php
│       └── ⚡competitions.blade.php
└── layouts/
    ├── app.blade.php               # Authenticated layout
    └── guest.blade.php             # Public layout (leaderboard)
```

> **SFC Convention:** All Livewire components are Single File Components — an anonymous PHP class in a `@php` block at the top of the `.blade.php` file, followed by the template. Files are prefixed with `⚡` to distinguish them from plain Blade views.

---

## Data Model

```
Event ──< Team ──< Result
  └──< Competition ──< Result
```

### Schema

**`events`**
| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `name` | string | |
| `description` | text | Nullable |
| `event_date` | dateTime | |
| `deleted_at` | timestamp | Soft delete |

**`teams`**
| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `event_id` | UUID | FK → events |
| `name` | string | |
| `color` | string | Hex color, nullable |
| `avatar` | text | Path on `uploads` disk, nullable |
| `represents` | text | e.g. school/dept name, nullable |
| `deleted_at` | timestamp | Soft delete |

**`competitions`**
| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `event_id` | UUID | FK → events |
| `name` | string | |
| `category` | string | Sports / Cultural / Academic / Creative Arts / Science and Tech / Literary / Speech and Media Arts / Attendance |
| `deleted_at` | timestamp | Soft delete |

**`results`**
| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `competition_id` | UUID | FK → competitions |
| `team_id` | UUID | FK → teams |
| `score` | integer | Default 0 |
| `deleted_at` | timestamp | Soft delete |

---

## Routes

### Authenticated (`auth`, `verified`)

| Method | URI | Component | Description |
|---|---|---|---|
| GET | `/events` | `pages::events` | Event list + CRUD |
| GET | `/event-dashboard/{event}` | `pages::event-dashboard` | Event hub (leaderboard + teams + competitions) |
| GET | `/competition-dashboard/{competition}` | `pages::competition-dashboard` | Score entry + per-competition ranking |
| GET | `/users` | `pages::users` | User management |

### Public (no auth)

| Method | URI | Description |
|---|---|---|
| GET | `/` | Welcome page — lists all events |
| GET | `/leaderboard/{event}` | Public podium leaderboard for an event |

---

## Core Logic

### Leaderboard Aggregation

The leaderboard query uses a raw SQL `LEFT JOIN` to aggregate scores while correctly excluding soft-deleted competitions:

```sql
SELECT
  teams.id, teams.name, teams.color, teams.avatar, teams.represents,
  COALESCE(SUM(CASE WHEN competitions.id IS NOT NULL THEN results.score ELSE 0 END), 0) AS total_score,
  COUNT(DISTINCT CASE WHEN competitions.id IS NOT NULL THEN results.competition_id END) AS competitions_participated
FROM teams
LEFT JOIN results ON teams.id = results.team_id
LEFT JOIN competitions
  ON results.competition_id = competitions.id
  AND competitions.deleted_at IS NULL
WHERE teams.event_id = ?
GROUP BY teams.id, teams.name, teams.color, teams.avatar, teams.represents
ORDER BY total_score DESC, teams.name ASC
```

This ensures:
- Teams with zero scores still appear in rankings
- Scores tied to soft-deleted competitions are excluded from totals
- The query is driven from `teams`, so soft-deleted team results never surface

### Runtime Placement

Per-competition placement (gold/silver/bronze/4th) is computed at runtime — no stored column needed:

```php
$placement = Result::where('competition_id', $result->competition_id)
    ->where('score', '>', $result->score)
    ->count() + 1;
```

### Score Upsert

Score entry uses an upsert pattern — update if a `Result` exists, create if not:

```php
$result = Result::where('team_id', $teamId)
    ->where('competition_id', $this->competition->id)
    ->first();

$result
    ? $result->update(['score' => $newScore])
    : Result::create([...]);
```

### Soft Delete Cascade

All models implement cascade soft-delete via `booted()` hooks, since database-level `ON DELETE CASCADE` does not propagate `deleted_at`:

```
Delete Event
  → soft-deletes each Team   → soft-deletes its Results
  → soft-deletes each Competition → soft-deletes its Results

Restore Event
  → restores Teams (withTrashed) → restores their Results
  → restores Competitions (withTrashed) → restores their Results
```

---

## Public Leaderboard

The public leaderboard page (`/leaderboard/{event}`) uses the guest layout and requires no authentication. It is designed for projection display during live events.

**Features:**
- **Podium cards** for 🥇 1st / 🥈 2nd / 🥉 3rd (displayed in visual podium order: 2nd → 1st → 3rd)
- **Full ranking list** with avatar, name, represents, and total points
- **Team Stats modal** — total points, avg score, win rate, placement breakdown (🥇🥈🥉), full competition history
- **Game Summary modal** — cross-competition results table showing 1st–4th per competition
- **Auto-refresh** every 60 seconds via `wire:poll.60000ms`
- **Dark mode toggle** via Flux UI's `$flux.dark` Alpine property

---

## File Storage

Avatar uploads use a custom `uploads` disk instead of the default `public` disk, to support shared hosting environments where `php artisan storage:link` may not be available.

```php
// Storing
$this->avatar->store('avatars', 'uploads');

// Displaying
asset('uploads/' . $team->avatar)

// Fallback (no avatar)
'https://ui-avatars.com/api/?name=' . urlencode($team->name)
```

---

## Toast Notifications

All CRUD actions fire toast notifications via the `HasSileoToasts` trait from `superbyte23/sileo-livewire`.

```php
use App\Livewire\Concerns\HasSileoToasts;

$this->toastSuccess('Success!', 'Event created successfully.');
$this->toastError('Error!', 'Something went wrong.');
```

The `<livewire:sileo-toaster />` tag must be present in the authenticated layout to render toasts.

---

## Authentication

Powered by **Laravel Fortify**, supporting:

- Login / Logout
- Registration
- Password reset
- Email verification
- Two-factor authentication (TOTP + recovery codes)

User account settings (profile, password, 2FA, appearance, account deletion) are under `/settings`, defined in `routes/settings.php`.

---

## Installation

```bash
# Clone and install dependencies
git clone <repo-url>
cd <project>
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
touch database/database.sqlite
php artisan migrate
php artisan db:seed   # optional: seeds a default admin user

# Build assets
npm run build

# Serve
php artisan serve
```

> For shared hosting, configure the `uploads` disk in `config/filesystems.php` to point to a publicly accessible directory, and skip `storage:link`.

---

## Development Notes

- `wire:change` (not `wire:model.live`) is used for score inputs to avoid triggering DB writes on every keystroke — saves only on blur or Enter.
- The `⚡leaderboards.blade.php` page is a legacy static placeholder from early prototyping and is not actively used.
- `app/Livewire/Management/EventsManagement.php` is a vestigial multi-file component superseded by the SFC `⚡events.blade.php`.
- Placement tallies in the team stats modal involve N+1 queries. This is acceptable at small scale but can be optimized with eager loading or a single aggregation query as data grows.
