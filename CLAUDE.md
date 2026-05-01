# Project notes for Claude Code

## Who you're working with

This project has two contributors:

- **Gordon** — owner, technical, works on `main` and reviews infrastructure.
- **A non-technical collaborator** — works on `dev`, the sandbox branch.
  Treat her as a smart, persistent reader who is not a programmer. Explain in
  plain English. Don't reach for jargon, command-line flags, or git internals
  unless she asks. When you do show a command, say in one sentence what it
  will do before running it.

## Branches

- `main` → production. Do not push here from a non-technical session.
- `dev` → live at https://dev.thefluencyfactor.com, auto-deploys on push.
  This is the working branch for the non-technical collaborator. Direct
  pushes to `dev` are expected and welcome.
- Feature branches (e.g. `add-title-page`) are merged into `dev` when ready.

## The commit-and-push workflow (important)

When the user says any of: **"ship it"**, **"push this"**, **"push to dev"**,
**"commit and push"**, **"make it live"**, or anything clearly meaning the
same thing — that is your cue to:

1. Run `git status` and confirm the changes are real files on disk, not just
   sandbox edits. If `git status` shows no changes despite recent edits, the
   session is in a sandbox that hasn't written through. Tell the user this
   plainly and ask her to switch permission modes via `/permissions` (Accept
   Edits) and redo the change. Do not try to push nothing.
2. Run `git diff` and summarize in one sentence what's about to be committed,
   so she can sanity-check.
3. Stage with `git add <specific files>`. Do not use `git add -A` or
   `git add .` — be explicit about which files are going.
4. Commit with a short, plain-English message describing the *user-visible*
   change. Examples: "Change Play button text to 'Play the Game'", "Hide
   game-name heading on title screen". No conventional-commit prefixes
   needed; keep it readable.
5. Push to `origin/dev`.
6. Confirm success by reading `git log origin/dev -1` and reporting the
   commit hash and message back to the user. Tell her the change should
   appear at dev.thefluencyfactor.com within ~2 minutes after a hard refresh.

If the push is **rejected** (non-fast-forward, conflict, auth failure):
**stop**. Do not force-push, do not reset, do not amend. Tell the user
exactly what the error said and recommend she message Gordon. These cases
require human judgment.

## Tooling rules

- Never run `git push --force`, `git reset --hard`, `git rebase`, or
  `--no-verify` in a non-technical session. If you think one of these is
  needed, stop and escalate to Gordon.
- Never edit `main` from this workflow. If the user asks to change something
  on `main`, gently redirect her to make the change on `dev` first.
- GitHub Desktop is **not** part of this workflow. If the user mentions it,
  remind her that all commits and pushes on this project go through Claude
  Code, and explain why (sandbox edits are invisible to GitHub Desktop).
- Prefer `gh` (GitHub CLI) over raw `git` for anything involving
  authentication or pull requests.

## Verifying changes landed

After every push, before saying "done", actually verify:

```
git log origin/dev -1 --oneline
```

If the latest remote commit isn't the one you just pushed, something is
wrong — investigate before telling the user it shipped.

## Game data lives in the database, not the code

The 6 game names on the title screen are read from the SQLite database at
`var/data/test_results.db`, not from any source file. To rename a game, the
user should log into the admin panel at dev.thefluencyfactor.com and edit
the test record there. Don't go hunting through `.json` or `.php` files for
game names — you won't find them.

## Style

- Default to no comments in code. Names should explain what; commit
  messages explain why.
- Don't add error handling, fallbacks, or "future-proofing" the user didn't
  ask for. This is a small, evolving app — keep changes minimal and
  reversible.
- When a task is done, say what changed in one or two sentences. Don't
  summarize the whole session.
