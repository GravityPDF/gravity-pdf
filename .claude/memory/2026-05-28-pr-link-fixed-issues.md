---
name: Link fixed issues when opening a PR
description: Before running `gh pr create`, search the repo's issues for any the PR fixes/closes and add `Closes #N` / `Fixes #N` / `Resolves #N` lines so GitHub auto-closes them on merge.
type: feedback
---

When opening a PR, first check whether any open issues in the repo are resolved by the change. If so, link them with GitHub's closing keywords (`Closes #N`, `Fixes #N`, `Resolves #N`) in the PR body so the issues auto-close on merge.

**Why:** Issues that get fixed quietly in a PR stay open after merge, clog the backlog, and need a manual cleanup pass later. Linking at open-time also tells reviewers what user-visible problem the PR is solving, which often shapes their review.

**How to apply:**
- Before `gh pr create`, run `gh issue list --repo <owner>/<repo> --state open --search "<keywords>"` (or filter by label) using terms drawn from the diff / commit messages / PR topic to surface candidates. Read the candidate issue bodies to confirm the PR genuinely resolves them (not just touches the same code area).
- Add the closing line at the **end of the Summary section**, before "Try it" / "Test plan" / the `<details>` toggle. Format: `Closes #N — <one-line description of the issue>` (or `Fixes` / `Resolves`). One per line if there are several.
- If the PR *relates to* but doesn't *close* an issue, use `Refs #N` (no closing keyword) so it shows up cross-linked without being auto-closed.
- Applies to every new PR regardless of branch or scope. For existing PRs that missed this at open-time, retro-add via `gh pr edit --body` rather than dropping a separate comment — the closing keyword only works from the PR body or commit messages, not from comments.
- This is purely about GitHub issues. Don't auto-close sibling PRs or external trackers.
