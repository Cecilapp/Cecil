---
title: Variables
layout: shortcodes
var1: 'var 1'
---
<!-- excerpt -->
# Variables in Markdown

## Set and show

{% set foo = 'bar' %}
foo: `{{ foo }}`

## FM variable

page.var1: `{{ page.var1 }}`
