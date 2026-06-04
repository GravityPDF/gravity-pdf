---
name: PR descriptions stay human-friendly
description: Lead with prose Summary paragraphs (not bulleted Highlights with bold prefixes), "Test it", and a Test plan. Push technical details and the Claude Code attribution into a collapsed `<details><summary>More info</summary>` block. Avoid em dashes and stock LLM filler in the Summary.
type: feedback
---

When generating or updating a PR description, the visible body should read like a human wrote it for a human reviewer:

1. **Summary** as plain prose paragraphs explaining what the PR does and why it matters. Not a bulleted "Highlights:" list with `**Bold Label**` prefixes — that pattern reads as AI-written. No nested matrices, no per-file hook lists.
2. **Try it** — a copy-pasteable code block showing how to exercise the change locally.
3. **Test plan** — a short checklist a reviewer can tick off.

Everything else — coverage tables, technical hook breakdowns, architecture notes, the `🤖 Generated with AI` attribution line — goes inside a `<details>` toggle:

```markdown
<details>
<summary>More info</summary>

...all the AI-dense detail here...

🤖 Generated with AI

</details>
```

**Why:** PR reviewers should be able to skim the body and know what's going on without wading through an LLM-style breakdown of every file touched. The detail is useful to *have* (especially when triaging later) but not useful *on the surface*. Hiding it inside `<details>` keeps both audiences happy.

**How to apply:**
- Applies to any `gh pr create` / `gh pr edit --body` output you generate, both at PR creation time and when updating the description later.
- Re-flow the existing body when a PR description is already AI-dense — collapse the technical content into the toggle and write a human-friendly opener above it.
- Don't move the Test Plan checklist into the toggle — reviewers tick it off without expanding the section.
- In Summary text, prefer periods/colons over em dashes (`—`) and use direct verbs ("adds", "fixes", "replaces") instead of LLM filler like "proper support for" or "in lock-step with". Em dashes and richer prose are fine inside the `<details>` toggle.
