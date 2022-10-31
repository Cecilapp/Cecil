<!-- break -->

[toc]

## Attributes

```markdown
[Link with id and class](./){#foo .bar}
```

[Link with id and class](./){#foo .bar}

## Links

- [Link to Cecil.app](https://cecil.app)
- [Link to `../Others/external-body.md`](../Others/external-body.md)
- [Link to `../About.md`](../About.md)
- [Link to page:about](page:about)

### Emebedded links

#### Youtube

[An example YouTube video](https://www.youtube.com/watch?v=FTpBS7g7YnI){embed=true}

#### GitHub Gist

[Cecil build script](<https://gist.github.com/ArnaudLigny/6b2aa9e6b25581c96435e9296efe0c0e){embed=true>}

#### Video

[Video example](/video/test.mp4 "/video/test.mp4"){embed=true controls poster=/images/cecil-logo.png style="width:100%;"}

#### Audio

[Audio example](/audio/test.mp3 "/audio/test.mp3"){embed=true controls}

## Image

![alt](/cecil-logo-1000.png "/cecil-logo-1000.png")

### Relative path to the parent asset dir

![alt](../../assets/cecil-logo-1000.png "../../assets/cecil-logo-1000.png")

### Resize

![alt](/cecil-logo-1000.png "/cecil-logo-1000.png"){width=200}

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
