---
title: Authoring content
description: How to write content in Markdown, with the syntax supported by Cecil.
toc: false
---
# Authoring content in Markdown

Cecil supports the [Markdown](https://cecil.app/documentation/content/markdown/) syntax in `.md` files, as well as a [front matter](https://cecil.app/documentation/content/front-matter/) to define variables.

**Table of contents:**

[toc]

```markdown
[toc]
```

:::tip
The table of contents of the other pages of this section is displayed by their template (`layouts/docs/page.html.twig`): this page disables it with `toc: false` in its front matter, and uses the `[toc]` Markdown tag instead.
:::

## Page structure

Cecil automatically uses the file name as the page title, but you can define the title and other variables in the [front matter](https://cecil.app/documentation/content/front-matter/).

You can structure content with headings, indicated by a number of `#` at the start of the line.

```markdown
---
title: Page title
description: Page short description.
---
# Page title

Lorem ipsum dolor sit amet, consectetur adipiscing elit.

## Heading

Lorem ipsum dolor sit amet, consectetur adipiscing elit.
```

## Inline style

Text can be **bold**, _italic_, or ~~strikethrough~~, and `inline code` is highlighted with backticks.

```markdown
Text can be **bold**, _italic_, or ~~strikethrough~~, and `inline code` is highlighted with backticks.
```

## Links

You can link to [a Markdown file](../about.md), to [a page by its ID](page:blog/welcome), or to [an external website](https://cecil.app).

```markdown
You can link to [a Markdown file](../about.md), to [a page by its ID](page:blog/welcome), or to [an external website](https://cecil.app).
```

## Attributes

An ID, classes and attributes can be added to headings, links and images:

```markdown
## Heading {#my-id .my-class}
```

## Images

Images are processed by Cecil: they are searched in `assets/` and `static/` directories.

![Cecil icon](/icon.png "Cecil icon"){width=128}

```markdown
![Cecil icon](/icon.png "Cecil icon"){width=128}
```

The `width` attribute resizes the image. A relative path is also supported:

```markdown
![Cecil icon](../../assets/icon.png "Cecil icon")
```

## Embedded content

A link to a video (YouTube, Vimeo, Dailymotion) or a GitHub Gist can be embedded with the `{embed}` attribute:

[CECIL : LE générateur de SITES STATIQUES en PHP](https://www.youtube.com/watch?v=ur8koU0iYvc){embed}

```markdown
[CECIL : LE générateur de SITES STATIQUES en PHP](https://www.youtube.com/watch?v=ur8koU0iYvc){embed}
```

## Lists

- Unordered list
- Unordered list
  - Level 2
  - Level 2

1. Ordered list
2. Ordered list
3. Ordered list

```markdown
- Unordered list
- Unordered list
  - Level 2
  - Level 2

1. Ordered list
2. Ordered list
3. Ordered list
```

## Blockquote

> This is a blockquote, which is commonly used when quoting another person or document.
>
> Blockquotes are indicated by a `>` at the start of each line.

```markdown
> This is a blockquote, which is commonly used when quoting another person or document.
>
> Blockquotes are indicated by a `>` at the start of each line.
```

## Code block

A code block is indicated by three backticks at the start and end. You can indicate the programming language after the opening backticks.

```javascript
// JavaScript code
const greeting = (name) => `Hello ${name}!`;
console.log(greeting('Cecil'));
```

````markdown
```javascript
// JavaScript code
const greeting = (name) => `Hello ${name}!`;
console.log(greeting('Cecil'));
```
````

## Table

| Command    | Description              |
|:-----------|:-------------------------|
| `new:site` | Creates a new website    |
| `new:page` | Creates a new page       |
| `serve`    | Starts the local server  |
| `build`    | Builds the website       |

```markdown
| Command    | Description              |
|:-----------|:-------------------------|
| `new:site` | Creates a new website    |
| `new:page` | Creates a new page       |
| `serve`    | Starts the local server  |
| `build`    | Builds the website       |
```

## Definition list

Front matter
: Variables of the page, at the beginning of the file.

Body
: Content of the page, written in Markdown.

```markdown
Front matter
: Variables of the page, at the beginning of the file.

Body
: Content of the page, written in Markdown.
```

## Footnotes and abbreviations

Cecil is written in PHP[^1] and generates HTML.

[^1]: PHP is a popular general-purpose scripting language.

*[HTML]: HyperText Markup Language

```markdown
Cecil is written in PHP[^1] and generates HTML.

[^1]: PHP is a popular general-purpose scripting language.

*[HTML]: HyperText Markup Language
```

## Notes

:::info
Info note.
:::

:::tip
Tip note.
:::

:::important
Important note.
:::

:::warning
Warning note.
:::

:::caution
Caution note.
:::

```markdown
:::info|tip|important|warning|caution
Note here.
:::
```

## Tabs, steps and accordion

:::steps
#### Create a page

```bash
cecil new:page
```

#### Preview it

```bash
cecil serve
```
:::

:::accordion
#### Is PHP required?

No: the standalone binary embeds PHP.

#### Where is the generated website?

In the `_site/` directory.
:::

````markdown
:::steps
#### Create a page

```bash
cecil new:page
```

#### Preview it

```bash
cecil serve
```
:::

:::accordion
#### Is PHP required?

No: the standalone binary embeds PHP.

#### Where is the generated website?

In the `_site/` directory.
:::
````

## Horizontal rule

A horizontal rule is indicated by three dashes on their own line:

---

```markdown
---
```
