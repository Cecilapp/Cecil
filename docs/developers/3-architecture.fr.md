<!--
title: "Architecture"
description: "Diagrammes du pipeline de génération et de l’architecture technique, et composants clés."
date: 2026-05-27
updated: 2026-10-26
alias: documentation/architecture
path: documentation/developpeurs/architecture
-->
# Architecture

Cette page présente une vue d’ensemble du pipeline de génération de Cecil, de son architecture technique et de ses composants clés.

## Pipeline de génération

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
    CONV["Convertisseur\n(Parsedown + coloration syntaxique)"]

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
        EXT["Extensions\n(Core, Content, Collection,\nTranslation, Intl, Components)"]
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

    CACHE[".cache/\n(assets, templates, traductions)"]

    subgraph OUTPUT["Sortie (_site/)"]
        HTML["pages (HTML, XML, JSON, etc.)"]
        CSS2["assets CSS/JS"]
        IMG["images"]
        SF["fichiers statiques"]
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

    %% les assets sont créés par le convertisseur (images, liens du Markdown) et par les templates (fonction asset()),
    %% traités et stockés en cache, puis copiés dans la sortie
    CONV --> ASSETS
    EXT --> ASSETS
    ASSETS --> CACHE
    CACHE --> S12

    RENDERER --> S11
    S11 --> HTML
    S12 --> CSS2
    S12 --> IMG
    S9 --> SF
    S13 -. "optimise les fichiers sur place" .-> OUTPUT

    %% Entrées
    subgraph INPUT["Sources"]
        MD["pages/\n(Markdown + front matter)"]
        DATA["data/\n(YAML, JSON, XML, CSV)"]
        STATIC["static/"]
        ASSETSDIR["assets/"]
        LAYOUTS["layouts/\n(templates Twig)"]
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

## Architecture technique

Le diagramme ci-dessous montre l’organisation du code en couches, de la ligne de commande jusqu’au système de fichiers, ainsi que les bibliothèques sur lesquelles s’appuie chaque composant.

```mermaid
graph TB
    %% Distribution et points d’entrée
    subgraph ENTRY["Points d’entrée"]
        BIN["bin/cecil\n(Composer)"]
        PHAR["cecil.phar\n(Box, self-update)"]
    end

    subgraph CONSOLE["Couche console (Symfony Console)"]
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

    subgraph DEV["Serveur local (serve)"]
        PHPS["Serveur web interne de PHP\n(.cecil/router.php)"]
        WATCH["FileWatcher\n(changes.flag)"]
        PROC["Sous-processus de build\n(Symfony Process)"]
        LR["livereload.js\n(Server-Sent Events)"]
        WATCH -- "régénère" --> PROC
        WATCH -- "notifie" --> PHPS
        PHPS --> LR
    end

    SERVE --> DEV

    subgraph CORE["Cœur"]
        BUILDER["Builder\n(orchestration des étapes, métriques)"]
        CFG["Config\n(Merger, Deprecations,\nnette/schema)"]
        LOG["Logger\n(PSR-3 : Console, Progress, Print)"]
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
        CACHE["Cache\n(PSR-16, fichiers)"]
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

    subgraph LIBS["Principales bibliothèques"]
        L1["Symfony\n(Finder, Filesystem, Yaml,\nSerializer, Validator, DomCrawler)"]
        L2["scssphp"]
        L3["Intervention Image\n(GD, Imagick, libvips)"]
        L4["matthiasmullie/minify\nvoku/html-min"]
        L5["spatie/image-optimizer\n(binaires externes)"]
        L6["yosymfony/toml"]
        L7["getID3, mp3info\n(métadonnées audio/vidéo)"]
    end

    CORE --> L1
    ASSET --> L2
    ASSET --> L3
    ASSET --> L4
    STEPS --> L4
    ASSET --> L5
    CONV --> L6
    ASSET --> L7

    subgraph FS["Système de fichiers"]
        SRC["Sources\n(pages/, data/, assets/, static/,\nlayouts/, translations/, themes/)"]
        CACHEDIR[".cache/"]
        TMP[".cecil/\n(routeur, live reload, métriques, logs)"]
        OUT["_site/"]
    end

    STEPS -- "lecture" --> SRC
    CACHE --> CACHEDIR
    STEPS -- "écriture" --> OUT
    PHPS -- "sert" --> OUT
    DEV --> TMP
```

## Composants clés

| Composant           | Rôle                                                                                                                     |
|---------------------|--------------------------------------------------------------------------------------------------------------------------|
| **Builder**         | Orchestrateur central, exécute les étapes en séquence                                                                    |
| **Config**          | Fusionne la configuration par défaut, des thèmes et du projet (`Config\Merger`), les variables d’environnement (`CECIL_*`) et les options CLI, et signale les options dépréciées (`Config\Deprecations`) |
| **Steps**           | Pipeline modulaire (16 étapes), chacune avec `init()` / `canProcess()` / `process()`                                     |
| **Collections**     | Pages, Taxonomies, Menus : structures de données centrales                                                               |
| **Converter**       | Analyse le front matter (YAML, INI, TOML ou JSON) et convertit le Markdown en HTML (Parsedown, coloration syntaxique)    |
| **Generators**      | Créent ou mettent à jour des pages (pages par défaut, sections, taxonomies, pagination, alias, redirections, etc.)       |
| **Extensions**      | Générateurs, extensions Twig, post-processeurs et abonnés aux événements déclarés par nom, chargés et validés par `Extension\Loader` |
| **Events**          | Événements de build (`Event\BuildEvents`) déclenchés autour du build, de chaque étape et du cycle de vie de chaque page, pour [étendre Cecil](1-extend.fr.md#evenements-de-build) |
| **Renderer (Twig)** | Applique les templates + extensions + post-processeurs                                                                   |
| **Assets**          | Créés depuis le contenu Markdown et les templates : compilent le SCSS, minifient, redimensionnent et convertissent les images, ajoutent une empreinte aux fichiers |
| **Cache**           | Stocke les assets traités, les templates compilés et les traductions dans `.cache/` pour accélérer les builds suivants   |
| **Doctor**          | Diagnostique le projet : front matter, liens, SEO, cache (commandes `doctor`)                                            |
