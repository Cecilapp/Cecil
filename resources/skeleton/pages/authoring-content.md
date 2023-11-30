---
title: Authoring Content
menu:
  main:
    weight: 300
---
# Authoring Content in Markdown

Cecil supports [Markdown](http://daringfireball.net/projects/markdown/syntax) and [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/) syntax in `.md` files as well as [front matter](https://cecil.app/documentation/content/#front-matter) to define variables.

## Inline style

Text can be **bold**, _italic_, or ~~strikethrough~~.

```markdown
Text can be **bold**, _italic_, or ~~strikethrough~~.
```

You can [link to another page](/about.md).

```markdown
You can [link to another page](/about.md).
```

You can highlight `inline code` with backticks.

```markdown
You can highlight `inline code` with backticks.
```

## How to structure page

Cecil automatically use the page file name as title, but you can also define title and other variables in the [front matter](https://cecil.app/documentation/content/#front-matter).

You can structure content using a heading. Headings in Markdown are indicated by a number of `#` at the start of the line.

```markdown
---
title: Page title
description: Page short description.
---

Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.

## Heading

Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
```

## Image

Images use Cecil’s built-in optimized asset support.

![Cecil favicon](/favicon.png "Cecil favicon")

```markdown
![Cecil favicon](/favicon.png "Cecil favicon")
```

Cecil search images in `assets/` and `static/` folders, but relative path is also supported:

```markdown
![Cecil favicon](../assets/favicon.png "Cecil favicon")
```

## List

* Unordered list
* Unordered list
* Unordered list

1. Ordered list
2. Ordered list
3. Ordered list

* Level 1
  * Level 2
  * Level 2
      * Level 3
      * Level 3

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

A code block is indicated by a block with three backticks ` ``` ` at the start and end. You can indicate the programming language being used after the opening backticks.

```php
echo "Hello world";
```

<pre>
```php
echo "Hello world";
```
</pre>

## Definition list

First Term
: This is the definition of the first term.

Second Term
: This is one definition of the second term.
: This is another definition of the second term.

## Table

| Head 1       | Head 2            | Head 3 |
|:-------------|:------------------|:-------|
| ok           | good swedish fish | nice   |
| out of stock | good and plenty   | nice   |
| ok           | good `oreos`      | hmm    |
| ok           | good `zoute` drop | yumm   |
