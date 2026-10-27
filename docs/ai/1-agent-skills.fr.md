<!--
title: "Skills d’agents"
description: "Installez les skills Cecil pour aider les agents de code IA à créer des sites et des thèmes."
date: 2026-10-22
updated: 2026-10-27
path: documentation/ia/skills-agents
-->
# Skills d’agents

Cecil fournit des [skills d’agents](https://agentskills.io) : des instructions que les agents de code IA (Claude Code, GitHub Copilot, Cursor, etc.) chargent pour travailler correctement avec Cecil, en suivant ses conventions.

| Skill                                                                                       | Utilisez-la pour                                                                                                      |
| ------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| [`cecil`](https://github.com/Cecilapp/Cecil/blob/main/skills/cecil/SKILL.md)                 | Créer, configurer, générer et déployer un site : contenu, front matter, templates, assets, configuration, etc.        |
| [`cecil-theme`](https://github.com/Cecilapp/Cecil/blob/main/skills/cecil-theme/SKILL.md)     | Créer, structurer et empaqueter un thème (thème visuel ou thème composant) pour Composer.                             |

## Installation

Avec [GitHub CLI](https://cli.github.com) :

```bash
gh skill install cecilapp/cecil
```

Ou avec [`npx skills`](https://www.skills.sh) :

```bash
npx skills add cecilapp/cecil
```

### Plugin Claude Code

Avec [Claude Code](https://code.claude.com), vous pouvez aussi installer les skills sous forme de plugin, depuis la [marketplace Cecil](https://github.com/Cecilapp/claude-plugins) :

```text
/plugin marketplace add Cecilapp/claude-plugins
/plugin install cecil@cecil
```

Le plugin récupère uniquement les skills depuis le dépôt Cecil et les maintient à jour. Elles sont alors aussi disponibles via `/cecil:cecil` et `/cecil:cecil-theme`.

## Utilisation

Une fois installées, demandez à votre agent de créer, générer, configurer ou déployer un site Cecil, ou de créer un thème : il utilise automatiquement la skill correspondante.

_Exemples de prompts :_

- « Crée un blog Cecil avec une version anglaise et une version française. »
- « Ajoute une section `projects` avec une liste de cartes sur la page d’accueil. »
- « Transforme les layouts de ce site en un thème Cecil réutilisable. »

:::tip
Les skills évoluent avec Cecil : réinstallez-les après une mise à jour de Cecil pour bénéficier des dernières instructions (le plugin Claude Code est mis à jour automatiquement).
:::
