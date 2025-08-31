---
title: Markdown
---
<!-- break -->

[toc]

---

## Attributes

```markdown
[Link with id and class](./){#foo .bar}
```

[Link with id and class](./){#foo .bar}

## Links

- [Link to Cecil.app](https://cecil.app)
- [Link to `../Others/external-body.md`](../Others/external-body.md)
- [Link to `../About.md`](../About.md)
- [Link to `/markdown.md`](/markdown.md)
- [Link to page:about](page:about)

### Embedded links

#### YouTube links

[An example YouTube video](https://www.youtube.com/watch?v=FTpBS7g7YnI){embed=true}

#### YouTube links (embed = false)

[An example YouTube video](https://www.youtube.com/watch?v=FTpBS7g7YnI){embed=false}

#### GitHub Gist links

[Cecil build script](<https://gist.github.com/ArnaudLigny/6b2aa9e6b25581c96435e9296efe0c0e){embed=true>}

#### Video links

[Video example](/video/test.mp4 "/video/test.mp4"){embed=true controls poster=/video/test.png}

#### Video links (without controls = autoplay + loop)

[Video example](/video/test.mp4 "/video/test.mp4"){embed=true poster=/images/cecil-logo.png}

#### Video links (embed = false)

[Video example](/video/test.mp4 "/video/test.mp4"){embed=false controls poster=/images/cecil-logo.png}

#### Audio links

[Audio example](/audio/test.mp3 "/audio/test.mp3"){embed=true controls}

#### Audio links (embed = false)

[Audio example](/audio/test.mp3 "/audio/test.mp3"){embed=false controls}

## Image

![alt](/cecil-logo-big.png "/cecil-logo-big.png")

### Image with a relative path to the parent asset dir

![alt](../../assets/cecil-logo-big.png "../../assets/cecil-logo-big.png")

### Image resized

![alt](/cecil-logo-big.png "/cecil-logo-big.png"){width=200}

## Notes

:::tip
**Tip:** This is an advice.
:::

## Syntax highlight

```php
<?php
echo 'foo';
```

## Inserted text

++test++

## Deleted text

~~test~~
