<!--
title: "Architecture"
description: "Diagramme du processus de génération et composants clés."
date: 2026-05-27
updated: 2026-10-19
alias: documentation/architecture
path: documentation/developpeurs/architecture
-->
# Architecture

Cette page présente une vue d’ensemble du pipeline de génération de Cecil et de ses composants clés.

## Diagramme

```mermaid
graph TD
    %% Entrée
    CLI["bin/cecil\n(point d’entrée CLI)"]
    APP["Application\n(Symfony Console)"]
    CMD["Commandes\nbuild / serve / doctor\nnew:* / clear / cache:clear\nshow:* / util:* / ..."]

    CLI --> APP --> CMD

    %% Orchestrateur
    BUILDER["Builder\n(orchestrateur)"]
    CONFIG["Configuration\n(default.php\n< thèmes < cecil.yml\n< variables d’environnement + options CLI)"]
    CMD --> BUILDER
    CONFIG --> BUILDER

    %% Pipeline
    subgraph PIPELINE["Pipeline de génération (étapes)"]
        S1["1. Pages/Load\n(Finder → fichiers Markdown)"]
        S2["2. Data/Load\n(fichiers de données)"]
        S3["3. StaticFiles/Load\n(si static.load est activé)"]
        S4["4. Pages/Create\n(création des objets Page)"]
        S5["5. Pages/Convert\n(front matter +\nMarkdown → HTML)"]
        S6["6. Taxonomies/Create\n(création des taxonomies)"]
        S7["7. Pages/Generate\n(générateurs)"]
        S8["8. Menus/Create\n(création des menus)"]
        S9["9. StaticFiles/Copy\n(copie des fichiers statiques)"]
        S10["10. Pages/Render\n(rendu Twig)"]
        S11["11. Pages/Save\n(enregistrement des pages)"]
        S12["12. Assets/Save\n(enregistrement des assets)"]
        S13["13-16. Optimize/Html, Css, Js, Images\n(si l’optimisation est activée)"]

        S1 --> S2 --> S3 --> S4 --> S5 --> S6 --> S7 --> S8 --> S9 --> S10 --> S11 --> S12 --> S13
    end

    BUILDER --> PIPELINE

    %% Sous-systèmes
    subgraph COLLECTIONS["Collections"]
        PC["PagesCollection\n(objets Page)"]
        TC["TaxonomiesCollection\n(vocabulaires/termes)"]
        MC["MenusCollection"]
    end

    subgraph GENERATORS["Générateurs (par priorité)"]
        G1["DefaultPages\n(index, 404, robots, sitemap, xsl)"]
        G3["ExternalBody"]
        G4["Section"]
        G5["Taxonomy"]
        G6["Homepage"]
        G7["Pagination"]
        G8["Alias"]
        G9["Redirect"]
        G1 --> G3 --> G4 --> G5 --> G6 --> G7 --> G8 --> G9
    end

    subgraph RENDERER["Rendu Twig"]
        TW["Moteur Twig"]
        EXT["Extensions\n(Core, Content, Collection)"]
        PP["PostProcessors\n(balise meta generator, extraits, liens)"]
        TH["Thèmes / Layouts"]
        TW --> EXT
        TW --> PP
        TH --> TW
    end

    subgraph ASSETS["Assets"]
        AL["Localisateur d’assets"]
        AC["Compilateur\n(SCSS → CSS)"]
        AI["Traitement des images\n(redimensionnement, responsive, WebP, AVIF)"]
        AO["Optimiseur\n(minification CSS/JS,\noptimisation des images)"]
    end

    subgraph OUTPUT["Sortie (_site/)"]
        HTML["pages (HTML, XML, JSON, etc.)"]
        CSS2["assets CSS/JS"]
        IMG["images"]
        SF["fichiers statiques"]
    end

    S4 --> PC
    S6 --> TC
    S8 --> MC
    S7 --> GENERATORS
    GENERATORS --> PC
    S10 --> RENDERER

    PC --> RENDERER
    TC --> RENDERER
    MC --> RENDERER

    %% les assets sont créés et traités par les templates (fonction asset()), puis enregistrés
    EXT --> ASSETS
    ASSETS --> S12

    RENDERER --> S11
    S11 --> HTML
    S12 --> CSS2
    S12 --> IMG
    S9 --> SF

    %% Entrées
    subgraph INPUT["Sources"]
        MD["pages/\n(Markdown + front matter)"]
        DATA["data/\n(YAML, JSON, XML, CSV)"]
        STATIC["static/"]
        ASSETSDIR["assets/"]
        LAYOUTS["layouts/\n(templates Twig)"]
    end

    MD --> S1
    DATA --> S2
    STATIC --> S3
    ASSETSDIR --> AL
    LAYOUTS --> TH
```

## Composants clés

| Composant           | Rôle                                                                                                                     |
|---------------------|--------------------------------------------------------------------------------------------------------------------------|
| **Builder**         | Orchestrateur central, exécute les étapes en séquence                                                                    |
| **Config**          | Fusionne la configuration par défaut, des thèmes et du projet (`Config\Merger`), les variables d’environnement (`CECIL_*`) et les options CLI, et signale les options dépréciées (`Config\Deprecations`) |
| **Steps**           | Pipeline modulaire (16 étapes), chacune avec `init()` / `canProcess()` / `process()`                                     |
| **Collections**     | Pages, Taxonomies, Menus : structures de données centrales                                                               |
| **Generators**      | Créent ou mettent à jour des pages (pages par défaut, sections, taxonomies, pagination, alias, redirections, etc.)       |
| **Renderer (Twig)** | Applique les templates + extensions + post-processeurs                                                                   |
| **Assets**          | Créés depuis les templates : compilent le SCSS, minifient, redimensionnent et convertissent les images, ajoutent une empreinte aux fichiers |
