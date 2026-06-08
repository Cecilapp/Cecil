<!--
description: "Architecture de Cecil."
date: 2026-05-27
updated: 2026-06-08
-->
# Architecture

## Diagramme

```mermaid
graph TD
    %% Entrée
    CLI["bin/cecil\n(Point d'entrée CLI)"]
    APP["Application\n(Symfony Console)"]
    CMD["Commandes\nbuild / serve / new:site\nnew:page / clear / show:content"]

    CLI --> APP --> CMD

    %% Orchestrateur
    BUILDER["Builder\n(Orchestrateur)"]
    CONFIG["Configuration\n(config.yml + default.php\n+ thèmes)"]
    CMD --> BUILDER
    CONFIG --> BUILDER

    %% Pipeline
    subgraph PIPELINE["Pipeline de génération (étapes)"]
        S1["1. Pages/Load\n(Finder → fichiers Markdown)"]
        S2["2. Data/Load\n(fichiers YAML)"]
        S3["3. StaticFiles/Load\n(chargement des fichiers statiques)"]
        S4["4. Pages/Create\n(création des objets Page)"]
        S5["5. Pages/Convert\n(Markdown → HTML\nfront matter)"]
        S6["6. Taxonomies/Create\n(création des taxonomies)"]
        S7["7. Pages/Generate\n(générateurs)"]
        S8["8. Menus/Create\n(création des menus)"]
        S9["9. StaticFiles/Copy\n(copie des fichiers statiques)"]
        S10["10. Pages/Render\n(rendu Twig)"]
        S11["11. Pages/Save\n(sauvegarde des pages)"]
        S12["12. Assets/Save\n(sauvegarde des assets)"]
        S13["13. Optimize/*\n(optimisation HTML/CSS/JS/Images)"]

        S1 --> S2 --> S3 --> S4 --> S5 --> S6 --> S7 --> S8 --> S9 --> S10 --> S11 --> S12 --> S13
    end

    BUILDER --> PIPELINE

    %% Sous-systèmes
    subgraph COLLECTIONS["Collections"]
        PC["PagesCollection\n(objets Page)"]
        TC["TaxonomiesCollection\n(vocabulaires/termes)"]
        MC["MenusCollection"]
    end

    subgraph GENERATORS["Générateurs (virtuels)"]
        GP["Pagination"]
        GT["Taxonomie"]
        GS["Section"]
        GR["Redirection"]
        GD["DefaultPages (accueil, 404)"]
    end

    subgraph RENDERER["Rendu Twig"]
        TW["Moteur Twig"]
        EXT["Extensions\n(Core, Content, Collection)"]
        PP["PostProcessors\n(métadonnées, extraits, liens)"]
        TH["Thèmes / Layouts"]
        TW --> EXT
        TW --> PP
        TH --> TW
    end

    subgraph ASSETS["Assets"]
        AL["Localisateur d'assets"]
        AC["Compilateur\n(SCSS → CSS)"]
        AI["Processeur d'images\n(responsive, WebP, AVIF)"]
        AO["Optimiseur\n(minification CSS/JS)"]
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

    %% Entrées
    subgraph INPUT["Sources"]
        MD["content/\n(Markdown + front matter)"]
        DATA["data/\n(YAML)"]
        STATIC["static/"]
        LAYOUTS["layouts/\n(modèles Twig)"]
    end

    MD --> S1
    DATA --> S2
    STATIC --> S3
    LAYOUTS --> TH
```

## Légende des composants clés

| Composant           | Rôle                                                                                 |
|---------------------|--------------------------------------------------------------------------------------|
| **Builder**         | Orchestrateur central, exécute les étapes en séquence                                |
| **Config**          | Fusion de la configuration par défaut + thème + projet + CLI                         |
| **Steps**           | Pipeline modulaire (13 étapes), chacune avec `init()` / `canProcess()` / `process()` |
| **Collections**     | Pages, Taxonomies, Menus — structures de données centrales                           |
| **Generators**      | Créent des pages virtuelles (pagination, tags, redirections…)                        |
| **Renderer (Twig)** | Applique les modèles + extensions + post-traitements                                |
| **Assets**          | Compile SCSS, optimise images, fingerprinte les fichiers                             |
