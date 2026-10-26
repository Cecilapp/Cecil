<!--
title: "Architecture"
description: "Diagrams of the build pipeline and of the technical architecture, and key components."
date: 2026-05-27
updated: 2026-10-26
alias: documentation/architecture
-->
# Architecture

This page gives an overview of Cecil’s build pipeline, its technical architecture and its key components.

## Build pipeline

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
    CONV["Converter\n(Parsedown + highlighter)"]

    subgraph COLLECTIONS["Collections"]
        PC["PagesCollection\n(Page objects)"]
        TC["TaxonomiesCollection\n(vocabularies/terms)"]
        MC["MenusCollection"]
    end

    subgraph GENERATORS["Generators (by priority)"]
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

    subgraph RENDERER["Twig rendering"]
        TW["Twig engine"]
        EXT["Extensions\n(Core, Content, Collection,\nTranslation, Intl, Components)"]
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

    CACHE[".cache/\n(assets, templates, translations)"]

    subgraph OUTPUT["Output (_site/)"]
        HTML["pages (HTML, XML, JSON, etc.)"]
        CSS2["CSS/JS assets"]
        IMG["images"]
        SF["static files"]
    end

    S5 --> CONV
    S4 --> PC
    S6 --> TC
    S8 --> MC
    S7 --> GENERATORS
    GENERATORS --> PC
    S10 --> RENDERER

    PC --> RENDERER
    TC --> RENDERER
    MC --> RENDERER
    S2 -- "site.data" --> RENDERER
    S3 -- "site.static" --> RENDERER

    %% assets are created by the converter (Markdown images, links) and by templates (asset() function),
    %% processed and stored in cache, then copied to the output
    CONV --> ASSETS
    EXT --> ASSETS
    ASSETS --> CACHE
    CACHE --> S12

    RENDERER --> S11
    S11 --> HTML
    S12 --> CSS2
    S12 --> IMG
    S9 --> SF
    S13 -. "optimize files in place" .-> OUTPUT

    %% Inputs
    subgraph INPUT["Sources"]
        MD["pages/\n(Markdown + front matter)"]
        DATA["data/\n(YAML, JSON, XML, CSV)"]
        STATIC["static/"]
        ASSETSDIR["assets/"]
        LAYOUTS["layouts/\n(Twig templates)"]
        TRANS["translations/"]
        THEMES["themes/\n(config, layouts, assets,\nstatic, data, translations)"]
    end

    MD --> S1
    DATA --> S2
    STATIC --> S3
    STATIC --> S9
    ASSETSDIR --> AL
    LAYOUTS --> TH
    TRANS --> EXT
    THEMES --> CONFIG
    THEMES --> TH
```

## Technical architecture

The diagram below shows how the code is organized in layers, from the command line to the file system, and which libraries each component relies on.

```mermaid
graph TB
    %% Distribution and entry points
    subgraph ENTRY["Entry points"]
        BIN["bin/cecil\n(Composer)"]
        PHAR["cecil.phar\n(Box, self-update)"]
    end

    subgraph CONSOLE["Console layer (Symfony Console)"]
        APP["Application"]
        ENV[".env\n(phpdotenv)"]
        BUILD["build"]
        SERVE["serve"]
        DOCTOR["doctor:*"]
        OTHERS["new:* / show:* / clear / cache:clear / util:*"]
        APP --> BUILD
        APP --> SERVE
        APP --> DOCTOR
        APP --> OTHERS
        ENV --> APP
    end

    BIN --> APP
    PHAR --> APP

    subgraph DEV["Local server (serve)"]
        PHPS["PHP built-in server\n(.cecil/router.php)"]
        WATCH["FileWatcher\n(changes.flag)"]
        PROC["Build sub-process\n(Symfony Process)"]
        LR["livereload.js\n(Server-Sent Events)"]
        WATCH -- "rebuild" --> PROC
        WATCH -- "notify" --> PHPS
        PHPS --> LR
    end

    SERVE --> DEV

    subgraph CORE["Core"]
        BUILDER["Builder\n(steps orchestration, metrics)"]
        CFG["Config\n(Merger, Deprecations,\nnette/schema)"]
        LOG["Logger\n(PSR-3: Console, Progress, Print)"]
        STEPS["Steps\n(StepInterface)"]
        COLL["Collections\n(Page, Taxonomy, Menu)"]
        GEN["Generators\n(GeneratorManager)"]
        BUILDER --> CFG
        BUILDER --> LOG
        BUILDER --> STEPS
        STEPS --> COLL
        STEPS --> GEN
    end

    BUILD --> BUILDER
    PROC --> BUILDER
    OTHERS --> BUILDER

    subgraph SERVICES["Services"]
        CONV["Converter\n(Parsedown Extra + TOC,\nhighlight.php / Tempest)"]
        REND["Renderer\n(Twig, Symfony Translation,\nTwig Components)"]
        ASSET["Asset\n(Locator, Compiler, Image, Optimizer)"]
        CACHE["Cache\n(PSR-16, files)"]
        DOC["Doctor\n(Site, Frontmatter, Links, SEO)"]
    end

    STEPS --> CONV
    STEPS --> REND
    STEPS --> ASSET
    REND --> ASSET
    CONV --> ASSET
    ASSET --> CACHE
    REND --> CACHE
    DOCTOR --> DOC
    DOC --> BUILDER

    subgraph LIBS["Main libraries"]
        L1["Symfony\n(Finder, Filesystem, Yaml,\nSerializer, Validator, DomCrawler)"]
        L2["scssphp"]
        L3["Intervention Image\n(GD, Imagick, libvips)"]
        L4["matthiasmullie/minify\nvoku/html-min"]
        L5["spatie/image-optimizer\n(external binaries)"]
        L6["yosymfony/toml"]
        L7["getID3, mp3info\n(audio/video metadata)"]
    end

    CORE --> L1
    ASSET --> L2
    ASSET --> L3
    ASSET --> L4
    STEPS --> L4
    ASSET --> L5
    CONV --> L6
    ASSET --> L7

    subgraph FS["File system"]
        SRC["Sources\n(pages/, data/, assets/, static/,\nlayouts/, translations/, themes/)"]
        CACHEDIR[".cache/"]
        TMP[".cecil/\n(router, live reload, metrics, logs)"]
        OUT["_site/"]
    end

    STEPS -- "read" --> SRC
    CACHE --> CACHEDIR
    STEPS -- "write" --> OUT
    PHPS -- "serve" --> OUT
    DEV --> TMP
```

## Key components

| Component           | Role                                                                                                     |
|---------------------|----------------------------------------------------------------------------------------------------------|
| **Builder**         | Central orchestrator that executes steps in sequence                                                     |
| **Config**          | Merges default, themes and project configuration (`Config\Merger`), environment variables (`CECIL_*`) and CLI options, and reports deprecated options (`Config\Deprecations`) |
| **Steps**           | Modular pipeline (16 steps), each with `init()` / `canProcess()` / `process()`                           |
| **Collections**     | Pages, Taxonomies, Menus: core data structures                                                           |
| **Converter**       | Parses the front matter (YAML, INI, TOML or JSON) and converts Markdown to HTML (Parsedown, code highlighting) |
| **Generators**      | Create or update pages (default pages, sections, taxonomies, pagination, aliases, redirects, etc.)       |
| **Extensions**      | Generators, Twig extensions, post-processors and event subscribers declared by name, loaded and validated by `Extension\Loader` |
| **Events**          | Build events (`Event\BuildEvents`) dispatched around the build, each step and each page lifecycle, to [extend Cecil](1-extend.md#build-events) |
| **Renderer (Twig)** | Applies templates + extensions + post-processors                                                         |
| **Assets**          | Created from Markdown content and templates: compiles SCSS, minifies, resizes and converts images, fingerprints files |
| **Cache**           | Stores processed assets, compiled templates and translations in `.cache/` to speed up subsequent builds  |
| **Doctor**          | Diagnoses the project: front matter, links, SEO, cache (`doctor` commands)                               |
