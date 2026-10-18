<!--
title: "Architecture"
description: "Diagram of the build pipeline and key components."
date: 2026-05-27
updated: 2026-10-18
alias: documentation/architecture
-->
# Architecture

This page gives an overview of Cecil’s build pipeline and its key components.

## Diagram

```mermaid
graph TD
    %% Entry
    CLI["bin/cecil\n(CLI entry point)"]
    APP["Application\n(Symfony Console)"]
    CMD["Commands\nbuild / serve / doctor\nnew:* / clear / cache:clear\nshow:* / util:* / ..."]

    CLI --> APP --> CMD

    %% Orchestrator
    BUILDER["Builder\n(Orchestrator)"]
    CONFIG["Configuration\n(default.php\n< themes < cecil.yml\n< env vars + CLI options)"]
    CMD --> BUILDER
    CONFIG --> BUILDER

    %% Pipeline
    subgraph PIPELINE["Build pipeline (steps)"]
        S1["1. Pages/Load\n(Finder → Markdown files)"]
        S2["2. Data/Load\n(data files)"]
        S3["3. StaticFiles/Load\n(if static.load is enabled)"]
        S4["4. Pages/Create\n(create Page objects)"]
        S5["5. Pages/Convert\n(front matter +\nMarkdown → HTML)"]
        S6["6. Taxonomies/Create\n(create taxonomies)"]
        S7["7. Pages/Generate\n(generators)"]
        S8["8. Menus/Create\n(create menus)"]
        S9["9. StaticFiles/Copy\n(copy static files)"]
        S10["10. Pages/Render\n(Twig rendering)"]
        S11["11. Pages/Save\n(save pages)"]
        S12["12. Assets/Save\n(save assets)"]
        S13["13-16. Optimize/Html, Css, Js, Images\n(if optimization is enabled)"]

        S1 --> S2 --> S3 --> S4 --> S5 --> S6 --> S7 --> S8 --> S9 --> S10 --> S11 --> S12 --> S13
    end

    BUILDER --> PIPELINE

    %% Subsystems
    subgraph COLLECTIONS["Collections"]
        PC["PagesCollection\n(Page objects)"]
        TC["TaxonomiesCollection\n(vocabularies/terms)"]
        MC["MenusCollection"]
    end

    subgraph GENERATORS["Generators (by priority)"]
        G1["DefaultPages\n(index, 404, robots, sitemap, xsl)"]
        G2["VirtualPages"]
        G3["ExternalBody"]
        G4["Section"]
        G5["Taxonomy"]
        G6["Homepage"]
        G7["Pagination"]
        G8["Alias"]
        G9["Redirect"]
        G1 --> G2 --> G3 --> G4 --> G5 --> G6 --> G7 --> G8 --> G9
    end

    subgraph RENDERER["Twig rendering"]
        TW["Twig engine"]
        EXT["Extensions\n(Core, Content, Collection)"]
        PP["PostProcessors\n(generator meta tag, excerpts, links)"]
        TH["Themes / Layouts"]
        TW --> EXT
        TW --> PP
        TH --> TW
    end

    subgraph ASSETS["Assets"]
        AL["Asset locator"]
        AC["Compiler\n(SCSS → CSS)"]
        AI["Image processor\n(resize, responsive, WebP, AVIF)"]
        AO["Optimizer\n(CSS/JS minification,\nimage optimization)"]
    end

    subgraph OUTPUT["Output (_site/)"]
        HTML["pages (HTML, XML, JSON, etc.)"]
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

    PC --> RENDERER
    TC --> RENDERER
    MC --> RENDERER

    %% assets are created and processed by templates (asset() function), then saved
    EXT --> ASSETS
    ASSETS --> S12

    RENDERER --> S11
    S11 --> HTML
    S12 --> CSS2
    S12 --> IMG
    S9 --> SF

    %% Inputs
    subgraph INPUT["Sources"]
        MD["pages/\n(Markdown + front matter)"]
        DATA["data/\n(YAML, JSON, XML, CSV)"]
        STATIC["static/"]
        ASSETSDIR["assets/"]
        LAYOUTS["layouts/\n(Twig templates)"]
    end

    MD --> S1
    DATA --> S2
    STATIC --> S3
    ASSETSDIR --> AL
    LAYOUTS --> TH
```

## Key components

| Component           | Role                                                                                                     |
|---------------------|----------------------------------------------------------------------------------------------------------|
| **Builder**         | Central orchestrator that executes steps in sequence                                                     |
| **Config**          | Merges base + default + themes + project configuration, environment variables (`CECIL_*`) and CLI options |
| **Steps**           | Modular pipeline (16 steps), each with `init()` / `canProcess()` / `process()`                           |
| **Collections**     | Pages, Taxonomies, Menus: core data structures                                                           |
| **Generators**      | Create or update pages (default pages, sections, taxonomies, pagination, aliases, redirects, etc.)       |
| **Renderer (Twig)** | Applies templates + extensions + post-processors                                                         |
| **Assets**          | Created from templates: compiles SCSS, minifies, resizes and converts images, fingerprints files         |
