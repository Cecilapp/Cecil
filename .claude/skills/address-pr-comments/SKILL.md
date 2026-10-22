---
name: address-pr-comments
description: Standard workflow to address pull request review comments in Cecil. Use when responding to review feedback, fixing requested changes, or preparing a clear reviewer reply with reproduction, fix, tests, and response.
---

# Address PR Comments

Use this workflow to process review feedback consistently.

## Scope

- Applies to PR comments and requested changes.
- Goal: produce a correct fix and a reviewer-ready response.

## Required Sequence

0. Fetch comments
- General comments and reviews: `gh pr view <number> --comments`
- Inline comments (with file and line): `gh api repos/{owner}/{repo}/pulls/<number>/comments`
- If no PR number is given, use the PR of the current branch (`gh pr view --json number`).

1. Reproduction
- Identify the exact issue described by the reviewer.
- Reproduce with the smallest possible scenario.
- Confirm expected vs. actual behavior.

2. Fix
- Implement the minimal safe change.
- Preserve existing public behavior unless the review explicitly requests a behavior change.
- Follow project conventions in `AGENTS.md`.

3. Tests and checks
- Run the smallest relevant checks first.
- Use project commands as needed: `composer code:style`, `composer code:analyse`, `composer test`, `composer test:cli`.
- If a check is skipped, state it explicitly with reason.

4. Reviewer response
- Explain what was changed and why.
- Reference files and key lines.
- Summarize validation performed and outcomes.
- Call out risks, assumptions, and follow-ups if any.
- Keep details concise but sufficient for asynchronous review (what changed, why, and proof of validation).
- Show the response to the user and post it only after confirmation:
  - general reply: `gh pr comment <number> --body-file <file>`
  - inline reply: `gh api repos/{owner}/{repo}/pulls/<number>/comments/<comment_id>/replies -F body=@<file>`

## Output Template

Use this structure in your final PR comment response:

- Issue reproduced: yes/no + short evidence
- Fix applied: concise summary + impacted files/components
- Validation: commands run + result (passed/failed/skipped)
- Notes: assumptions, limitations, risks, or next steps

## Guardrails

- Do not include unrelated refactors.
- Do not revert user changes outside the scope.
- Keep diffs focused and reviewable.
