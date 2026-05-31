<!--
description: "Architecture de Cecil."
date: 2026-05-27
-->
# Architecture

## Diagramme

```mermaid
graph TD
    %% Entry
    CLI["bin/cecil\n(CLI Entry)"]
    APP["Application\n(Symfony Console)"]
    CMD["Commands\nbuild / serve / new:site\nnew:page / clear / show:content"]

    CLI --> APP --> CMD

    %% Builder
    BUILDER["Builder\n(Orchestrateur)"]
    CONFIG["Config\n(config.yml + default.php\n+ thèmes)"]
    CMD --> BUILDER
    CONFIG --> BUILDER

    %% Pipeline
    subgraph PIPELINE["Pipeline de Build (Steps)"]
        S1["1. Pages/Load\n(Finder → fichiers MD)"]
        S2["2. Data/Load\n(fichiers YAML)"]
        S3["3. StaticFiles/Load"]
        S4["4. Pages/Create\n(objets Page)"]
        S5["5. Pages/Convert\n(Markdown → HTML\nfront matter)"]
        S6["6. Taxonomies/Create"]
        S7["7. Pages/Generate\n(générateurs)"]
        S8["8. Menus/Create"]
        S9["9. StaticFiles/Copy"]
        S10["10. Pages/Render\n(Twig)"]
        S11["11. Pages/Save"]
        S12["12. Assets/Save"]
        S13["13. Optimize/*\n(HTML/CSS/JS/Images)"]

        S1 --> S2 --> S3 --> S4 --> S5 --> S6 --> S7 --> S8 --> S9 --> S10 --> S11 --> S12 --> S13
    end

    BUILDER --> PIPELINE

    %% Subsystems
    subgraph COLLECTIONS["Collections"]
        PC["PagesCollection\n(Page objects)"]
        TC["TaxonomiesCollection\n(Vocabulary/Terms)"]
        MC["MenusCollection"]
    end

    subgraph GENERATORS["Générateurs (virtuels)"]
        GP["Pagination"]
        GT["Taxonomy"]
        GS["Section"]
        GR["Redirect"]
        GD["DefaultPages (home, 404)"]
    end

    subgraph RENDERER["Rendu Twig"]
        TW["Twig Engine"]
        EXT["Extensions\n(Core, Content, Collection)"]
        PP["PostProcessors\n(metadata, excerpts, links)"]
        TH["Themes / Layouts"]
        TW --> EXT
        TW --> PP
        TH --> TW
    end

    subgraph ASSETS["Assets"]
        AL["Asset Locator"]
        AC["Compiler\n(SCSS → CSS)"]
        AI["Image Processor\n(responsive, WebP, AVIF)"]
        AO["Optimizer\n(minify CSS/JS)"]
    end

    subgraph OUTPUT["Sortie (_site/)"]
        HTML["pages .html"]
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
    S12 --> ASSETS

    PC --> RENDERER
    TC --> RENDERER
    MC --> RENDERER

    RENDERER --> S11
    S11 --> HTML
    S12 --> CSS2
    S12 --> IMG
    S9 --> SF

    %% Input
    subgraph INPUT["Sources"]
        MD["content/\n(Markdown + front matter)"]
        DATA["data/\n(YAML)"]
        STATIC["static/"]
        LAYOUTS["layouts/\n(templates Twig)"]
    end

    MD --> S1
    DATA --> S2
    STATIC --> S3
    LAYOUTS --> TH
```

## Légende des composants clés

| Composant           | Rôle                                                                                 |
|---------------------|--------------------------------------------------------------------------------------|
| **Builder**         | Orchestrateur central, exécute les steps en séquence                                 |
| **Config**          | Fusion config défaut + thème + projet + CLI                                          |
| **Steps**           | Pipeline modulaire (13 étapes), chacune avec `init()` / `canProcess()` / `process()` |
| **Collections**     | Pages, Taxonomies, Menus — structures de données centrales                           |
| **Generators**      | Créent des pages virtuelles (pagination, tags, redirections…)                        |
| **Renderer (Twig)** | Applique les templates + extensions + post-processors                                |
| **Assets**          | Compile SCSS, optimise images, fingerprinte les fichiers                             |
