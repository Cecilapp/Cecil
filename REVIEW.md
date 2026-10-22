# Cecil - Review Guidelines

Instructions for code review agents reviewing changes in this repository.
Project conventions are defined in [AGENTS.md](AGENTS.md): apply them, do not restate them.

## Focus

Review the diff with emphasis on:

- behavioral regressions
- architecture boundary violations (Builder -> Steps -> Generators -> Renderer -> Output)
- project convention mismatches
- missing or weak test coverage
- operational and release risks

## Severity

Default profile: strict.

- Report blocking issues and also non-blocking but meaningful gaps: style deviations, incomplete validation, missing docs updates, weak test coverage.
- Report uncertain risks as explicit "potential issues" instead of silently dropping them.

## Checklist

1. Map affected architecture zones (`src/Step/`, `src/Generator/`, `src/Renderer/`, `config/`).
2. Check PHP conventions from [AGENTS.md](AGENTS.md): strict types, Cecil header, PSR-12, `\`-prefixed native function calls, `?Type` for nullable parameters, no unrelated reformatting.
3. Check generator priorities stay coherent with the expected extraction order.
4. Check tests: new Generators, Steps and Commands need a test; CLI-facing behavior needs `composer test:cli` coverage.
5. Check documentation impact for behavior or architecture changes:
   - [docs/](docs/) and [README.md](README.md) are updated
   - each edited `.md` doc has its `.fr.md` counterpart updated
   - the frontmatter `updated` date is bumped
   - agent skills in [skills/](skills/) stay consistent
6. Check `*.twig` files do not end with a trailing newline, and Markdown trailing spaces are preserved.

Do not request `CHANGELOG.md` updates: it is generated automatically.

## Output

- Findings first, ordered by severity.
- For each finding, give file/line evidence, the impact and an actionable recommendation.
- Keep the summary brief and after the findings.
- If there are no findings, say so explicitly and list residual risks and test gaps.
