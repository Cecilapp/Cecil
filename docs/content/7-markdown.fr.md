<!--
title: "Markdown"
description: "Syntaxe et extensions Markdown : attributs, liens, images, table des matières, notes, coloration syntaxique, etc."
date: 2026-03-27
updated: 2026-10-03
path: documentation/contenu/markdown
-->
# Markdown

Cecil prend en charge le format [Markdown](http://daringfireball.net/projects/markdown/syntax), ainsi que [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/).

Cecil fournit aussi des **fonctionnalités supplémentaires** pour enrichir votre contenu, voir ci-dessous.

## Attributs

Avec [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/), vous pouvez définir un id, une classe et des attributs personnalisés sur certains éléments à l’aide d’un bloc d’attributs.  
Par exemple, placez le(s) attribut(s) souhaité(s) après un en-tête, un bloc de code délimité, un lien ou une image en fin de ligne, entre accolades, comme ceci :

```markdown
## En-tête {#id .class attribute=value}
```

:::warning
Pour un élément en ligne, comme un lien, vous devez utiliser un retour à la ligne après l’accolade fermante :

```markdown
Lorem ipsum [dolor](url){attribute=value} 
sit amet.
```

:::

## Liens

Vous pouvez créer un lien avec la syntaxe `[Texte](url)` ; `url` peut être un chemin, un chemin relatif vers un fichier Markdown, une URL externe, etc.

_Exemple :_

```markdown
[Link to a path](/about/)
[Link to a Markdown file](/fr/a-propos/)
[Link to Cecil website](https://cecil.app)
```

:::info
Un lien relatif vers un fichier Markdown est résolu depuis le dossier du fichier courant (comme sur GitHub), puis remplacé par l’URL de la page ciblée.
:::

### Lien vers une page

Vous pouvez facilement créer un lien vers une page avec la syntaxe `[Titre de page](page:page-id)`.

_Exemple :_

```markdown
[Link to a blog post](page:blog/post-1)
```

### Externe

Par défaut, les liens externes ont la valeur suivante pour l’attribut `rel` : `noopener noreferrer`.

_Exemple :_

```html
<a href="<url>" rel="noopener noreferrer">Link to another website</a>
```

Vous pouvez modifier ce comportement avec les [options `pages.body.links.external`](../configuration/25-pages.fr.md#pages-body-links).

### Liens intégrés

Vous pouvez laisser Cecil essayer de transformer un lien en contenu embarqué en utilisant l’attribut `{embed}` ou en activant l’option de configuration globale `pages.body.links.embed.enabled` à `true`.

:::important
Seuls les liens **YouTube**, **Vimeo**, **Dailymotion** et **GitHub Gists** sont pris en charge.
:::

_Exemple :_

```markdown
[CECIL : LE générateur de SITES STATIQUES en PHP](https://www.youtube.com/watch?v=ur8koU0iYvc){embed}
```

[CECIL : LE générateur de SITES STATIQUES en PHP](https://www.youtube.com/watch?v=ur8koU0iYvc){embed}

#### Local video/audio files

Cecil peut aussi créer des éléments HTML vidéo et audio, selon l’extension du fichier.

_Exemple :_

```markdown
[Video file](video.mp4){embed controls poster=/images/video-test.png}
[Audio file](song.mp3){embed controls}
```

Est converti en :

```html
<video src="/video.mp4" controls poster="/images/video-test.png" style="max-width:100%;height:auto;"></video>
<audio src="/song.mp3" controls></audio>
```

## Images

Pour ajouter une image, utilisez un point d’exclamation (`!`) suivi d’une description alternative entre crochets (`[]`), puis du chemin ou de l’URL de l’image entre parenthèses (`()`).  
Vous pouvez facultativement ajouter un titre entre guillemets.

```markdown
![Alternative description](/image.jpg "Image title")
```

:::info
Le chemin doit être relatif à la racine de votre site Web (ex. : `/image.jpg`), mais Cecil est capable de normaliser un chemin relatif aux répertoires _assets_ et _static_ (ex. : `../../assets/image.jpg`).
:::

### Lazy loading

Cecil ajoute l’attribut `loading="lazy"` à chaque image.

_Exemple :_

```markdown
![](/image.jpg)
```

Est converti en :

```html
<img src="/image.jpg" loading="lazy">
```

:::info
Vous pouvez désactiver ce comportement avec l’attribut `{loading=eager}` ou avec l’[option `lazy`](../configuration/25-pages.fr.md#pages-body-images).
:::

### Decoding

Cecil ajoute l’attribut `decoding="async"` à chaque image.

_Exemple :_

```markdown
![](/image.jpg)
```

Est converti en :

```html
<img src="/image.jpg" decoding="async">
```

:::info
Vous pouvez désactiver ce comportement avec l’attribut `{decoding=auto}` ou avec l’[option `decoding`](../configuration/25-pages.fr.md#pages-body-images).
:::

### Redimensionnement

Chaque image du _body_ peut être redimensionnée automatiquement en définissant une largeur inférieure à celle d’origine, avec l’attribut additionnel `{width=X}`.

_Exemple :_

```markdown
![](/image.jpg){width=800}
```

Est converti en :

```html
<img src="/thumbnails/800/image.jpg" width="800" height="600">
```

:::info
Le ratio est conservé (l’attribut `height` est calculé automatiquement), le fichier original n’est pas modifié et la version redimensionnée est stockée dans `/thumbnails/<width>/`.
:::

:::important
Cette fonctionnalité nécessite une bibliothèque de traitement d’images : [Imagick](https://www.php.net/manual/book.imagick.php) est utilisé en priorité s’il est disponible (et capable de lire le JPEG et le PNG), puis [libvips](https://www.libvips.org/) (via l’extension PHP [FFI](https://www.php.net/manual/book.ffi.php)), et enfin [GD](https://www.php.net/manual/book.image.php) en dernier recours ; sinon, elle ajoute seulement un attribut HTML `width` à la balise `img`.
:::

:::info
Le support de libvips est optionnel et n’est pas inclus dans `cecil.phar`. Pour l’utiliser, Cecil doit être installé avec [Composer](https://getcomposer.org) et il faut :

1. [libvips](https://www.libvips.org/install.html) installé sur le système
2. l’extension PHP [FFI](https://www.php.net/manual/book.ffi.php) activée
3. le paquet `intervention/image-driver-vips` installé avec Cecil

Si Cecil est une dépendance de votre projet (voir [Bibliothèque](../developers/42-library.fr.md#support-de-libvips)) :

```bash
composer require intervention/image-driver-vips
```

Si Cecil est installé globalement :

```bash
composer global require cecil/cecil intervention/image-driver-vips
```
:::

### Formats

Si l’[option `formats`](../configuration/25-pages.fr.md#pages-body-images) est définie, des images alternatives sont créées et ajoutées.

_Exemple :_

```markdown
![](/image.jpg)
```

Peut être converti en :

```html
<picture>
  <source srcset="/image.avif" type="image/avif">
  <source srcset="/image.webp" type="image/webp">
  <img src="/image.jpg">
</picture>
```

:::important
Veuillez noter que **tous les formats d’image** ne sont pas toujours inclus dans les extensions d’image PHP.
:::

### Responsive

Si l’[option `responsive`](../configuration/25-pages.fr.md#pages-body-images) est activée, alors toutes les images du _body_ seront automatiquement rendues « responsive ».

_Exemple :_

```markdown
![](/image.jpg){width=800}
```

sera converti en :

```html
<img src="/thumbnails/800/image.jpg" width="800" height="600"
  srcset="/thumbnails/320/image.jpg 320w,
          /thumbnails/640/image.jpg 640w,
          /thumbnails/800/image.jpg 800w"
  sizes="100vw"
>
```

:::info
Comme une image du body est convertie en [Asset](../assets/index.fr.md#asset), les différentes largeurs doivent être définies dans la [configuration des assets](../configuration/27-assets.fr.md).
:::

L’attribut `sizes` prend la valeur de l’option de configuration `assets.images.responsive.sizes.default`, mais peut être modifié en créant une nouvelle entrée nommée d’après une _class_ ajoutée à l’image.

_Exemple :_

```yaml
assets:
  images:
    responsive:
      sizes:
        default: 100vw
        my_class: "(max-width: 800px) 768px, 1024px"
```

```markdown
![](/image.jpg){.my_class}
```

:::info
Vous pouvez combiner les options `formats` et `responsive`.
:::

### CSS class

Vous pouvez définir une valeur par défaut pour l’attribut `class` de chaque image avec l’[option `class`](../configuration/25-pages.fr.md#pages-body-images).

### Caption

Le titre optionnel peut être utilisé pour créer automatiquement une légende (`figcaption`) en activant l’[option `caption`](../configuration/25-pages.fr.md#pages-body-images).

_Exemple :_

```markdown
![](/images/img.jpg "Title")
```

Est converti en :

```html
<figure>
  <img src="/image.jpg" title="Title">
  <figcaption>Title</figcaption>
</figure>
```

:::info
La légende prend en charge le contenu Markdown.
:::

### Image localisée

Pour les pages traduites, Cecil recherche d’abord un fichier suffixé par la langue lors de la résolution des chemins d’image Markdown.

_Exemple :_

```markdown
![](/images/cecil-logo.png)
```

Avec une page française (`fr`), Cecil essaie d’abord `/images/cecil-logo.fr.png`, puis revient à `/images/cecil-logo.png`.

### Placeholder

Comme les images sont généralement des ressources plus lourdes et plus lentes, et qu’elles ne bloquent pas le rendu, il est préférable de donner aux utilisateurs quelque chose à voir pendant qu’ils attendent leur chargement.

L’attribut `placeholder` accepte 2 options :

1. `color`: affiche un fond coloré (basé sur la couleur dominante de l’image)
2. `lqip`: [Low-Quality Image Placeholder](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders)

_Exemples :_

```markdown
![](/images/img.jpg){placeholder=color}
![](/images/img.jpg){placeholder=lqip}
```

:::tip
Vous pouvez définir une valeur pour l’attribut `placeholder` de chaque image avec l’[option `placeholder`](../configuration/25-pages.fr.md#pages-body-images).
:::

:::warning
L’option `lqip` n’est pas compatible avec les GIF animés.
:::

## Table des matières

Vous pouvez ajouter une table des matières avec la syntaxe Markdown suivante :

```markdown
[toc]
```

:::info
Par défaut, la ToC extrait les en-têtes H2 et H3. Vous pouvez modifier ce comportement avec les [options de body](../configuration/25-pages.fr.md#pages-body).
:::

## Extrait

Un extrait peut être défini dans le _body_ avec l’une des balises suivantes : `excerpt` ou `break`.

_Exemple :_

```html
Introduction.
<!-- excerpt -->
Main content.
```

Utilisez ensuite le filtre [`excerpt_html`](../templates/reference/14-filters.fr.md#excerpt-html) dans votre template.

## Notes

Créez un bloc de _Note_ (info, astuce, important, etc.).

_Exemple :_

```markdown
:::tip
**Tip:** This is advice.
:::
```

Est converti en :

```html
<aside class="note note-tip">
  <p>
    <strong>Tip:</strong> This is advice.
  </p>
</aside>
```

:::tip
**Tip:** This is advice.
:::

_Autres exemples :_

:::
empty
:::

:::info
info
:::

:::tip
tip
:::

:::important
important
:::

:::warning
warning
:::

:::caution
caution
:::

## Coloration syntaxique

La coloration syntaxique des blocs de code est activée par défaut avec l’option [pages.body.highlight](../configuration/25-pages.fr.md#pages-body-highlight).

Si besoin, vous pouvez la désactiver avec :

```yaml
pages:
  body:
    highlight: false
```

_Exemple :_

<pre>
```php
echo "Hello world";
```
</pre>

Est rendu en :

```php
echo "Hello world";
```

:::info
Vous pouvez personnaliser le style de coloration syntaxique en créant votre propre thème. Consultez le [guide des thèmes de Highlight.js](https://highlightjs.readthedocs.io/en/latest/theme-guide.html).
:::

## Texte inséré

Représente une plage de texte qui a été ajoutée.

```markdown
++text++
```

Est converti en :

```html
<ins>text</ins>
```
