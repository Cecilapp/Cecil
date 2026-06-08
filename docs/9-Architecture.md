<!--
description: "Cecil architecture."
date: 2026-05-27
updated: 2026-06-08
-->
# Architecture

## Diagram

```mermaid
graph TD
    %% Entry
    CLI["bin/cecil\n(CLI entry point)"]
    APP["Application\n(Symfony Console)"]
    CMD["Commands\nbuild / serve / new:site\nnew:page / clear / show:content"]

    CLI --> APP --> CMD

    %% Orchestrator
    BUILDER["Builder\n(Orchestrator)"]
    CONFIG["Configuration\n(config.yml + default.php\n+ themes)"]
    CMD --> BUILDER
    CONFIG --> BUILDER

    %% Pipeline
    subgraph PIPELINE["Build pipeline (steps)"]
        S1["1. Pages/Load\n(Finder -> Markdown files)"]
        S2["2. Data/Load\n(YAML files)"]
        S3["3. StaticFiles/Load\n(load static files)"]
        S4["4. Pages/Create\n(create Page objects)"]
        S5["5. Pages/Convert\n(Markdown -> HTML\nfront matter)"]
        S6["6. Taxonomies/Create\n(create taxonomies)"]
        S7["7. Pages/Generate\n(generators)"]
        S8["8. Menus/Create\n(create menus)"]
        S9["9. StaticFiles/Copy\n(copy static files)"]
        S10["10. Pages/Render\n(Twig rendering)"]
        S11["11. Pages/Save\n(save pages)"]
        S12["12. Assets/Save\n(save assets)"]
        S13["13. Optimize/*\n(optimize HTML/CSS/JS/Images)"]

        S1 --> S2 --> S3 --> S4 --> S5 --> S6 --> S7 --> S8 --> S9 --> S10 --> S11 --> S12 --> S13
    end

    BUILDER --> PIPELINE

    %% Subsystems
    subgraph COLLECTIONS["Collections"]
        PC["PagesCollection\n(Page objects)"]
        TC["TaxonomiesCollection\n(vocabularies/terms)"]
        MC["MenusCollection"]
    end

    subgraph GENERATORS["Generators (virtual pages)"]
        GP["Pagination"]
        GT["Taxonomy"]
        GS["Section"]
        GR["Redirect"]
        GD["DefaultPages (home, 404)"]
    end

    subgraph RENDERER["Twig rendering"]
        TW["Twig engine"]
        EXT["Extensions\n(Core, Content, Collection)"]
        PP["PostProcessors\n(metadata, excerpts, links)"]
        TH["Themes / Layouts"]
        TW --> EXT
        TW --> PP
        TH --> TW
    end

    subgraph ASSETS["Assets"]
        AL["Asset locator"]
        AC["Compiler\n(SCSS -> CSS)"]
        AI["Image processor\n(responsive, WebP, AVIF)"]
        AO["Optimizer\n(CSS/JS minification)"]
    end

    subgraph OUTPUT["Output (_site/)"]
        HTML[".html pages"]
        CSS2["CSS/JS assets"]
        IMG["images"]
        SF["static files"]
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

    %% Inputs
    subgraph INPUT["Sources"]
        MD["content/\n(Markdown + front matter)"]
        DATA["data/\n(YAML)"]
        STATIC["static/"]
        LAYOUTS["layouts/\n(Twig templates)"]
    end

    MD --> S1
    DATA --> S2
    STATIC --> S3
    LAYOUTS --> TH
```

## Key Components Legend

| Component         | Role                                                                            |
|-------------------|----------------------------------------------------------------------------------|
| **Builder**       | Central orchestrator that executes steps in sequence                            |
| **Config**        | Merges default + theme + project + CLI configuration                            |
| **Steps**         | Modular pipeline (13 steps), each with `init()` / `canProcess()` / `process()` |
| **Collections**   | Pages, Taxonomies, Menus: core data structures                                  |
| **Generators**    | Create virtual pages (pagination, tags, redirects, etc.)                        |
| **Renderer (Twig)** | Applies templates + extensions + post-processors                              |
| **Assets**        | Compiles SCSS, optimizes images, fingerprints files                             |
