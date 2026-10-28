# JOB A — Admin "Pages" Sidebar Dropdown + Courier "No API Connect" + STATUS.md correction

Owner: implementation agent (job A).
Written by: zombiecoder (arbiter), 2026-10-28.
Project: `/home/sahon/Desktop/new evo` — Laravel 10.50.3, MySQL `z_evo`.
All file:line evidence below was verified live before this doc was written. If a claim is
wrong, STOP and report — do not "fix" the spec silently.

## 0. Ground rules (non-negotiable)

- Read this whole file before touching anything.
- Touch ONLY files in section 6 (ownership). Everything else is read-only for you.
- Do NOT run `php artisan migrate` (live `z_evo` DB). Do NOT run `view:clear`/`config:cache`/
  `route:clear`/`optimize` (live site caches).
- Comments in code: English only. User-facing UI text: English (matches existing admin UI).
- No mock/demo/placeholder. Evidence before every success claim: command + exit code + key
  line. Labels: SOURCE_VERIFIED (code says so) / NOT_TESTED (no runtime proof). Never claim more.
- Three-strike rule: same failure 3x → stop, report blocker, do not improvise a rewrite.
- A dev server may be running on port 8123 (arbiter's). Do not kill it. Do not browse.
  Use `php -l` + `route:list` + `grep` for proof.

## 1. Goal (what "done" means)

1. The admin sidebar gets a **Pages** dropdown exposing the EXISTING `pages.*` CRUD
   (currently orphaned — reachable only by typing the URL).
2. Courier admin shows a clear **"No API Connect"** label when a courier is not configured
   (today it only says "Incomplete"/"Disabled").
3. `STATUS.md` corrected: it currently documents the WRONG project path
   (`/home/sahon/Desktop/arafat`); fix the stale path references so it describes THIS repo.

## 2. Verified current state (evidence)

| Fact | Evidence |
| --- | --- |
| Pages CRUD fully exists and works (verified end-to-end by arbiter in browser) | routes `admin/page/manage`→`pages.index`, `admin/page/create`→`pages.create`, `admin/page/save`→`pages.store`, `admin/page/{id}/edit`→`pages.edit`, `admin/page/update`→`pages.update`, `admin/page/inactive`→`pages.inactive`, `admin/page/active`→`pages.active`, `admin/page/destroy`→`pages.destroy` — `routes/web.php:1374-1381`; controller `app/Http/Controllers/Admin/CreatePageController.php`; views `resources/views/backEnd/createpage/{index,create,edit}.blade.php` |
| Pages routes are auth+permission guarded | `route:list --json` → `pages.*` middleware `['web','Authenticate','LockAccount','CheckReffer', PermissionMiddleware:page-list\|page-create\|page-edit\|page-delete]` |
| Public render works | `FrontendController::page($slug)` at `app/Http/Controllers/Frontend/FrontendController.php:662-671`, route `page/{slug}` named `page` at `routes/web.php:799`; view `resources/views/frontend/pages/page.blade.php:34` `{!! $page->description !!}`. Arbiter created id=2 and confirmed public render + 404 on missing slug |
| **ACTIVE sidebar has NO Pages link** (the gap) | `resources/views/backEnd/layouts/partials/sidebar.blade.php:178-202` is the "Landing Page" dropdown (`landing-pages.*`, `campaign.*`); grep `pages\.` in this file → only landing-pages. The `pages.index` link lives ONLY in the DEAD `resources/views/backEnd/layouts/master.blade.php:387` (0 files `@extends('backEnd.layouts.master')` — verified: `grep -rln "backEnd.layouts.master" resources/views/backEnd` → 0) |
| Active admin layout is `backEnd.layouts.app` | `resources/views/backEnd/courier/index.blade.php:1` and `createpage/index.blade.php:1` → `@extends('backEnd.layouts.app')`; that layout includes `partials/sidebar.blade.php` |
| Sidebar dropdown pattern to copy | `sidebar.blade.php:178-202` — `<li class="menu-item ...">` + `menu-link menu-toggle` + `<ul class="menu-sub">` + child `<li class="menu-item ..."><a href="{{ route(...) }}" class="menu-link"><div>Label</div></a></li>`; active state via `request()->routeIs('pages.*') ? 'active open' : ''` |
| Courier config gate exists | `app/Models/Courierapi.php:75 isConfigured()`, `:97 isReady()` (returns `status && isConfigured()`), `:103 label()` |
| Courier admin badges (current wording) | `resources/views/backEnd/courier/index.blade.php:57-71` — `@if($courier->isConfigured()) <span class="badge bg-label-success">Complete</span> @else <span class="badge bg-label-danger">Incomplete</span>`; then Enabled/Disabled. NO "No API Connect" text anywhere (repo grep `No API Connect\|NoApiConnect\|no_api_connect` → 0 hits) |
| Courier order-send view also gates on isReady | `resources/views/backEnd/order/courier_send.blade.php:86` `@if($courier->isReady())` |
| STATUS.md documents the wrong project | `STATUS.md:5` `**প্রকল্প:** /home/sahon/Desktop/arafat`; `:180` `cd /home/sahon/Desktop/arafat`; `:787` `cd /home/sahon/Desktop/arafat && php artisan serve ...`; `:396,772-774` backup paths under `/home/sahon/uds/hi/arafat_bak/`. Actual repo = `/home/sahon/Desktop/new evo` (git remote `azurebluezone/z-evo`, MySQL `z_evo`, brand "GS Corporation") |

## 3. Build spec

### 3.1 Sidebar Pages dropdown — `resources/views/backEnd/layouts/partials/sidebar.blade.php`

Insert a NEW `<li>` block (Pages dropdown) immediately AFTER the Landing Page block
(`sidebar.blade.php:202`, the `</li>` that closes Landing Page). Copy the exact markup
pattern of the Landing Page block. Required shape:

```blade
<!-- Pages (static CMS) -->
<li class="menu-item {{ request()->routeIs('pages.*') ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ri ri-file-text-line"></i>
        <div>Pages</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('pages.index') ? 'active' : '' }}">
            <a href="{{ route('pages.index') }}" class="menu-link"><div>Manage Pages</div></a>
        </li>
        <li class="menu-item {{ request()->routeIs('pages.create') ? 'active' : '' }}">
            <a href="{{ route('pages.create') }}" class="menu-link"><div>Create Page</div></a>
        </li>
    </ul>
</li>
```

- Icon: `ri-file-text-line` (verify the remixicon set has it; if unsure pick another `ri-*`
  already used in this file — report which).
- Only add the two links above. Do NOT add edit/update/destroy to the sidebar.

### 3.2 Courier "No API Connect" label — `resources/views/backEnd/courier/index.blade.php`

In the status `<td>` (around `:57-63`), change the `@else` branch so it reads
"No API Connect" instead of "Incomplete", keeping the danger badge:

```blade
@if($courier->isConfigured())
    <span class="badge bg-label-success">Complete</span>
@else
    <span class="badge bg-label-danger">No API Connect</span>
@endif
```

- Also, in the SAME file, the Enabled/Disabled `<td>` (`:65-71`): when
  `! $courier->isConfigured()`, the courier cannot work — append a muted hint under the
  Disabled badge: `<div class="text-muted small">Set API credentials to connect</div>`
  (wrap in `@if(! $courier->isConfigured()) ... @endif` so it only shows when unconfigured).
- `resources/views/backEnd/order/courier_send.blade.php:86` `@if($courier->isReady())` —
  read the `@else` branch; if it shows a generic disabled message, change its text to
  include "No API Connect" when `! $courier->isConfigured()`. If the else branch already
  explains credentials, leave it and note so in the report.

### 3.3 STATUS.md correction — `STATUS.md`

- Fix ONLY stale project-path references so they point to THIS repo
  (`/home/sahon/Desktop/new evo`). Specifically lines `:5`, `:180`, `:787`.
- Add a short dated note at the top (after the title) that the project path was corrected
  from `/home/sahon/Desktop/arafat` to `/home/sahon/Desktop/new evo` on 2026-10-28, and that
  the brand shown in-app is "GS Corporation".
- Do NOT rewrite the whole document. Do NOT touch the `/home/sahon/uds/hi/arafat_bak/`
  backup-path lines (`:396,772-774`) — those are historical backup locations, leave as-is
  but note in your report they were left unchanged.
- Do NOT translate the Bengali body text.

## 4. Acceptance checks (run all, paste output + exit code)

1. `php -l resources/views/backEnd/layouts/partials/sidebar.blade.php` is NOT applicable
   (blade). Instead: `php artisan view:clear` is FORBIDDEN. Do a manual balanced-tag re-read
   of the inserted `<li>...</li>` block. For the courier blade edits, same.
2. `grep -c "menu-item" resources/views/backEnd/layouts/partials/sidebar.blade.php` → capture
   BEFORE (baseline) and AFTER; must be +3 (one parent + two children for the Pages block).
3. `grep -n "pages.index\|pages.create" resources/views/backEnd/layouts/partials/sidebar.blade.php`
   → both route names present.
4. `grep -rn "No API Connect" resources/views/backEnd/courier/index.blade.php` → 1 hit.
5. `grep -n "arafat" STATUS.md | grep -v "uds/hi/arafat_bak"` → the project-path lines
   (`:5`, `:180`, `:787`) no longer reference arafat; backup-path lines may remain.
6. `git status --short` → only your owned files (section 6). Ignore other agents' files.

## 5. Runtime note

- The admin pages were ALREADY verified working end-to-end by the arbiter (created
  Terms-and-Conditions page id=2, saw it render publicly, saw 404 on bad slug). You are NOT
  asked to re-verify CRUD at runtime — only to wire the sidebar + courier label + doc fix.
- Mark runtime sidebar click-through as NOT_TESTED unless you have a browser; static proof
  (route exists + link present) is sufficient for this job.

## 6. Ownership (files you may create/edit)

- `resources/views/backEnd/layouts/partials/sidebar.blade.php` (edit: add Pages dropdown only)
- `resources/views/backEnd/courier/index.blade.php` (edit: status label + hint)
- `resources/views/backEnd/order/courier_send.blade.php` (edit: only the @else text, if needed)
- `STATUS.md` (edit: stale path fix + dated note)

Forbidden: any controller, any model, any migration, `routes/*.php`, `bootstrap/app.php`,
anything under `app/`, `resources/views/frontend/*`, `createpage/*` views (they already work),
`resources/views/backEnd/layouts/app.blade.php` or `0app.blade.php` (do not restructure layout).

## 7. Report format

Final message must contain: files edited (paths), each acceptance check with command + exit
code + key output line, evidence labels, anything NOT_TESTED, deviations from this spec with
reason, one open question max. Then append a lesson via `append_syllabus` only if you
discovered a project-wide rule.
