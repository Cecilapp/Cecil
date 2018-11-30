---
title: Variables
layout: macro.html
var1: 'var 1'
---
# Variables in Markdown

## Set and show
{% set foo = 'bar' %}
foo: `{{ foo }}`

## FM variable
page.var1: `{{ page.var1 }}`
