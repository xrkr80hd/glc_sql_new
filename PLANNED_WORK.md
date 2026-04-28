# Planned Work

## Workflow

- Active repo root: `D:\GLC_OG_UPDATING`.
- Do not build in or modify `D:\GLC_LOCAL_LACIE_MAIN`; that repo is the reference artifact only.
- LICL before acting: inspect the current local code/context first.
- Read this file before starting or resuming work.
- Implement the current priority before jumping to newer steering unless the user explicitly says `STOP` and reprioritizes.
- If the user raises a new issue but does not explicitly say `STOP`, add it here and keep working through the current operation.
- After finishing a task, re-check this file for anything missed.
- Log finished work in `COMPLETED_WORK.md`.
- Do not push, commit, or deploy automatically. Work is served/tested locally, then the user will SSH/upload manually.

## Current Priority

- Convert the current `GLC_LOCAL_LACIE_MAIN` Postgres/Supabase site behavior into this `GLC_OG_UPDATING` PHP/static/MySQL project.
- Target stack: PHP/static screens, MySQL/MariaDB SQL for cPanel, filesystem uploads under this project, no Supabase/Postgres runtime.
- Convert all screens and admin/public behavior, not just a partial public-site pass.

## Active Queue

- Map all current `GLC_LOCAL_LACIE_MAIN` public screens, admin screens, API routes, database tables, uploads, and auth behavior to PHP/MySQL equivalents in `GLC_OG_UPDATING`.
- Convert the Postgres/Supabase schema into MySQL/MariaDB-compatible SQL in `database/setup.sql` or a clearly named migration SQL file under `database/`.
- Replace Supabase-style data access with PHP PDO queries using the existing `php/config.php` database bootstrap.
- Replace Supabase Storage upload behavior with cPanel-compatible filesystem upload paths under `uploads/`.
- Preserve and extend existing PHP/static pages where practical instead of introducing a Node/Next.js runtime.
- Bring the top-level public pages onto the same top-bar/header/nav layout language as the reference site, including `index.html`, `visit.html`, `beliefs.html`, `give.html`, `prayer.html`, `sermons.html`, `live.html`, `live2.html`, and `youth.html`.
- Validate public pages, admin pages, JSON API endpoints, uploads, login/session behavior, and cPanel SQL compatibility before marking the conversion complete.

## Remaining Conversion Notes

- Build the missing PHP admin modules for the newly translated MySQL tables: social links, ministries/service times, seasonal features, youth banners, sermons, archived sermons, service song lists, ministry order requests, bookkeeping reports, team roles, team members, and role assignments.
- Add or adapt admin upload flows for announcement images, ministry images, livestream fallback videos, gallery thumbnails, youth album media, and profile photos using filesystem paths under `uploads/`.
- Convert the remaining Supabase role/access behavior into PHP session and MySQL role checks.
- Run the full schema on an actual MySQL/MariaDB database; no local MySQL CLI is available in this environment.
