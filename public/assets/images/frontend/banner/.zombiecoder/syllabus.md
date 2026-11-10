# Syllabus — agent knowledge for this project

> Seeded automatically from the project fingerprint. Append-only: new lessons are
> added at the bottom, nothing is ever removed.

## 1. Scope

| Field | Value |
| --- | --- |
| Working directory | `/home/sahon/Desktop/new evo/public/assets/images/frontend/banner` |
| Languages | PNG |
| Files scanned | 10 |
| Seeded at | 2026-11-06T01:37:48.959Z |

## 2. Footprint (auto-discovered)

| Field | Value |
| --- | --- |
| Top-level entries | `67189f48992c11729666888.png`, `67178e93dc25b1729597075.png`, `67179c9cb10841729600668.png`, `6718b8ecbd6a11729673452.png`, `67189fe766ad21729667047.png`, `67178fb2b43d91729597362.png`, `67179af0746a01729600240.png`, `67189f3d37d841729666877.png`, `673099d36f6e71731238355.png`, `hero-default.png` |
| Manifests | _none_ |
| Markers | `.zombiecoder` |
| Files / dirs / bytes | 10 / 0 / 2025658 |

### Project map (directory → key files)

| Directory | Files |
| --- | --- |
| `./` | `67178e93dc25b1729597075.png`, `67178fb2b43d91729597362.png`, `67179af0746a01729600240.png`, `67179c9cb10841729600668.png`, `67189f3d37d841729666877.png`, `67189f48992c11729666888.png`, `67189fe766ad21729667047.png`, `6718b8ecbd6a11729673452.png`, `673099d36f6e71731238355.png`, `hero-default.png` |

_This map is the footprint. Do not create a separate footprint file. If it is
missing or stale, explore the project and update this section in place; add
durable lessons to §5._

## 3. Agents bound to this project

- `manu`
- `jarin`
- `brishti`
- `rashed`
- `halim`
- `majnu`
- `nila`
- `tuhin`
- `zombiecoder`

## 4. Standing rules

1. SSOT first, then logic, then code.
2. Evidence before confidence. No evidence means no verified answer.
3. Never state a guess as a fact. Say "not verified" instead.
4. Every claim carries a label: SOURCE_VERIFIED, CONFIG_VALIDATED, LIVE_PROBE_PASSED, END_TO_END_TESTED, NOT_TESTED, UNVERIFIED.
5. Regression mindset: after a fix, re-run the checks that could have broken.
6. Two record-keeping duties only: back up before irreversible work, and log every action.
7. Every response starts with "ভাইয়া"; chat in Bengali, code/shortcuts in English.
8. Never lie. Admit a mistake; an honest "I don't know" beats a confident guess.
9. Read the directory and this syllabus before writing; do not rely on memory.
10. Shortest evidence-backed answer wins. Send only the necessary context to the provider.
11. Every agent is a main agent; delegate with a named teammate and minimal context.
12. Before work, read the input intake (§5) and the SSOT: locate the files bound to the request, then start. Planning stays at the goal-contract level; no long planning loops between syllabus and conversation.

## 5. Input intake

How work starts, for both a new project and an existing one:

- **New project** — record the user's current intent and its expected output here once, at intake, before any code. Do not invent scope beyond that intent.
- **Existing project** — keep the previous proof/footprint (§2), standing rules (§4) and lessons (§6). An intake update adds the new intent; it never erases prior evidence.
- **Every later input** — read the SSOT and this syllabus first, map the request to the exact files from the project map/SSOT, and start there. Filename inventory is not proof of logic; open the files tied to the request.
- **Planning cap** — planning is the goal contract plus the few steps needed to hit the point. No hour-long planning between syllabus and conversation, no exploratory loops for a simple task, and no flattery/brokerage: keep only what the task needs.

## 6. Lessons (append-only)

_Nothing recorded yet. Each new lesson lands below, newest last._

---

_— ZombieCoder Syllabus · v0.1.0 · generated 2026-11-06T01:37:48.960Z · files=10_

### Project onboarding

`onboarding:dde4386cae19`

- Project: `banner` unknown
- Project root: `/home/sahon/Desktop/new evo/public/assets/images/frontend/banner`
- Files / bytes: 10 / 2025658
- Languages: PNG(10)
- Top level: `67189f48992c11729666888.png`, `67178e93dc25b1729597075.png`, `67179c9cb10841729600668.png`, `6718b8ecbd6a11729673452.png`, `67189fe766ad21729667047.png`, `67178fb2b43d91729597362.png`, `67179af0746a01729600240.png`, `67189f3d37d841729666877.png`, `673099d36f6e71731238355.png`, `hero-default.png`
- Scripts: none declared
- Docs: none found
- Tests: none found
- Logs: no .log files found
- Markers: none
- Git: not a git working tree
- Skills: detected `resource-optimization`, `server`, `first-principles`, `evidence-before-confidence`; installed `typescript`, `bootstrap`, `tailwind`, `ollama`, `llama-cpu`, `laravel`, `laravel-market`, `node`, `next`, `python`, `fastapi`, `cpanel` … +8

**Audit failures carried over**

- none recorded

**Evidence-backed gaps**

- No README/docs found; the project's stated purpose is not written down.
- No test files detected; behaviour cannot be re-proved from the repo alone.

**Still unverified**

- Whether the docs match the running behaviour; the agent must read both.
- The model's training cutoff; the agent must check versions against official docs.
- The user's expectation; the agent must record it in the syllabus, not infer it from the tree.

**Agent checklist before the first goal contract**

1. Read the docs above and compare every claim with the current code.
2. Reproduce any log/audit failure against the real input before fixing it.
3. Write the user's expectation, the contradictions and the remaining gaps into this syllabus with `append_syllabus`.
4. If the stack/version is newer than the model's training, `websearch` the official docs, then install the needed skill with `skill_catalog`.
5. Only then open `goal_contract`; the user must approve it. A passing check is not user consent.

_recorded 2026-11-06T01:37:48.973Z_
