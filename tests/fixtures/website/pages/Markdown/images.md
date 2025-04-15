---
title: 'Images'
date: 2020/06/08
updated: 2025/04/02
typora-root-url: ../../static
---
# Images in Markdown

[toc]
<!-- break -->

## Local image

```markdown
![Alt text](/images/cecil-logo.png)
```

![Alt text](/images/cecil-logo.png)

## External image

```markdown
![Jamstatic](https://jamstatic.fr/assets/images/twitter-card.png)
```

![Jamstatic](https://jamstatic.fr/assets/images/twitter-card.png)

### With fallback

```markdown
![Not found](https://example.com/images/not-found.png)
```

![Not found](https://example.com/images/not-found.png)

## Resize

### Image resized to 100px

```markdown
![Alt text](/images/cecil-logo.png){width=100}
```

![Alt text](/images/cecil-logo.png){width=100}

### External image resized to 250px

```markdown
![Jamstatic](https://jamstatic.fr/assets/images/twitter-card.png){width=250}
```

![Jamstatic](https://jamstatic.fr/assets/images/twitter-card.png){width=250}

## Image with caption

```markdown
![Alt text](/images/cecil-logo.png 'Title text')
```

![Alt text](/images/cecil-logo.png 'Title text')

## Responsive image

```markdown
![Alt text](/images/cecil-logo-1000.png)
```

![Alt text](/images/cecil-logo-1000.png)

## Animated GIF

```markdown
![Alt text](/images/nyan-cat.gif)
```

![Alt text](/images/nyan-cat.gif)

## Placeholder

### Without

```markdown
![Alt text](/images/japon_sample.jpg)
```

![Alt text](/images/japon_sample.jpg){placeholder=}

### Color

```markdown
![Alt text](/images/japon_sample.jpg){placeholder=color}
```

![Alt text](/images/japon_sample.jpg){placeholder=color}

### LQIP (Low-Quality Image Placeholder)

```markdown
![Alt text](/images/japon_sample.jpg){placeholder=lqip}
```

![Alt text](/images/japon_sample.jpg){placeholder=lqip}
