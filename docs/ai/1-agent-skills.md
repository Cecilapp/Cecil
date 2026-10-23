<!--
title: "Agent skills"
description: "Install the Cecil skills to help AI coding agents build sites and themes."
date: 2026-10-22
updated: 2026-10-22
-->
# Agent skills

Cecil provides [agent skills](https://agentskills.io): instructions that AI coding agents (Claude Code, GitHub Copilot, Cursor, etc.) load to work with Cecil the right way, following its conventions.

| Skill                                                                                       | Use it to                                                                                                  |
| ------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| [`cecil`](https://github.com/Cecilapp/Cecil/blob/main/skills/cecil/SKILL.md)                 | Create, configure, build and deploy a site: content, front matter, templates, assets, configuration, etc. |
| [`cecil-theme`](https://github.com/Cecilapp/Cecil/blob/main/skills/cecil-theme/SKILL.md)     | Create, structure and package a theme (visual theme or component theme) for Composer.                     |

## Install

With the [GitHub CLI](https://cli.github.com):

```bash
gh skill install cecilapp/cecil
```

Or with [`npx skills`](https://www.skills.sh):

```bash
npx skills add cecilapp/cecil
```

## Usage

Once installed, ask your agent to create, build, configure or deploy a Cecil site, or to create a theme: it uses the matching skill automatically.

_Examples of prompts:_

- “Create a Cecil blog with an English and a French version.”
- “Add a `projects` section with a list of cards on the homepage.”
- “Turn the layouts of this site into a reusable Cecil theme.”

:::tip
Skills are updated with Cecil: reinstall them after upgrading Cecil to get the latest instructions.
:::
