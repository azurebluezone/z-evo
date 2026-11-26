# Documentation index

This folder holds the documents that are still in use: the ones a program reads,
and the ones that describe the system as it exists today. Dated session logs,
one-off audits and superseded plans were removed in the 2026-11-26 cleanup; they
remain reachable through git history (`git log --diff-filter=D -- docs/`), and
they are not re-added.

## Rules

- A document that a script reads is a contract: changing or deleting it breaks
  the tool. The table below names the reader for each one.
- A document that records the current state is updated in place; do not open a
  new dated copy of the same subject.
- Plans and audits are session material. They belong in the session report, not
  in this folder, unless a tool depends on them.

## Index

| Document | What it covers | Read by |
| --- | --- | --- |
| `COURIER-API-FEATURES-2026-11-15.md` | Courier API surface, settings and webhook contract | `tools/verify/audit-log-courier-dashboard.php` |
| `COURIER-ORDER-FLOW-2026-11-15.md` | Live order to courier to fraud-check verification | human reference |
| `DEMO-IMAGES.md` | Demo product-image provenance and licences | `app/Console/Commands/SeedProductGalleryCommand.php` |
| `DEV-CATALOG-SEED-2026-11-16.md` | Development catalogue seeding | human reference |
| `DEV-ORDER-SEED-2026-11-16.md` | Development order seeding | human reference |
| `IMAGE-INVENTORY-2026-11-12.md` | Readable image inventory | `tools/verify_landing_polish.php` |
| `image-inventory.json` | Machine-readable image inventory | `tools/image_inventory.php`, `tools/verify_landing_polish.php` |
| `LANDING-CAMPAIGNS-2026-11-10.md` | Campaign engine state and content model | human reference |
| `LANDING-ORDER-FORM-2026-11-14.md` | Landing-page order form and backend | human reference |
| `LANDING-PAGES-2026-11-11.md` | Landing-page manager state | human reference |
| `LANDING-VERIFICATION-2026-11-12.md` | Recorded landing-page verification | `tools/verify_landing_polish.php` |
| `PLAN-notifications-2026-11-16.md` | Notification schedule and templates | comment in `app/Console/Kernel.php` |
| `PLAN-reports-analytics-2026-11-16.md` | Reports and analytics upgrade plan | human reference |
| `SECURITY-php2shell-2026-10-24.md` | CVE exposure assessment and shipped hardening | human reference |
| `SEED-ASSETS-ADORA-2026.md` | Portable Adora 2026 demo asset bundle | human reference |
| `visitor-analytics-redesign-plan.md` | Visitor and analytics admin redesign | human reference |
| `admin-form-inputs.md` | Where the admin forms differ from each other | human reference |
| `specs/2026-10-28-job-a-admin-pages-sidebar-courier.md` | Pages sidebar and courier job spec | human reference |
| `specs/2026-10-28-job-b-homepage-events-3h-cache.md` | Homepage events and 3-hour cache job spec | human reference |

## Audience

- `README.md` at the repository root is the entry point for a new developer.
- `STATUS.md` at the root is the longer onboarding document.
- This folder is the reference layer behind both.
