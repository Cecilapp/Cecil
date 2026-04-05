<!--
title: Étendre
description: "Étendre Cecil."
date: 2026-03-27
slug: etendre
-->
# Étendre

Comme Cecil repose sur PHP, il est facile d'en étendre les capacités.

[toc]

## Générateur de pages

Un générateur permet de créer des pages sans fichiers Markdown (avec des données provenant d'une API ou d'une base de données, par exemple), ou de modifier des pages existantes.

Créez simplement une nouvelle classe PHP dans l'espace de noms `Cecil\Generator`, puis ajoutez le nom de la classe à la liste [`pages.generators`](4-Configuration.md#pages-generators).

**Exemple:**

_/extensions/Cecil/Generator/DummyPage.php_

```php
<?php
namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;

class DummyPage extends AbstractGenerator implements GeneratorInterface
{
    public function generate(): void
    {
        // create a new page $page, then add it to the site collection
        $page = (new Page('my-page'))
            ->setType(Type::PAGE->value)
            ->setPath('mypage')
            ->setBodyHtml('<p>My page body</p>')
            ->setVariable('language', 'en')
            ->setVariable('title', 'My page')
            ->setVariable('date', now())
            ->setVariable('menu', ['main' => ['weight' => 99]]);
        $this->generatedPages->add($page);
    }
}
```

_/extensions/Cecil/Generator/Database.php_

```php
<?php
namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;

class Database extends AbstractGenerator implements GeneratorInterface
{
    public function generate(): void
    {
        // create pages from a SQLite database
        $db = new SQLite3('database.sqlite');
        $statement = $db->prepare('SELECT * FROM blog');
        $result = $statement->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $page = (new Page($row['page-id']))
                ->setType(Type::PAGE->value)
                ->setPath($row['path'])
                ->setBodyHtml($row['html'])
                ->setVariable('title', $row['title'])
                ->setVariable('date', $row['date']);
            $this->generatedPages->add($page);
        }
        $result->finalize();
        $db->close();
    }
}
```

_configuration_

```yaml
pages:
  generators:
    # priority: class name
    99: Cecil\Generator\DummyPage
    35: Cecil\Generator\Database
```

## Extension Twig

Vous pouvez ajouter des [fonctions](3-Templates.md#functions) et des [filtres](3-Templates.md#filters) personnalisés :

1. [créez une extension Twig](https://twig.symfony.com/doc/advanced.html#creating-an-extension) dans l'espace de noms `Cecil\Renderer\Extension`
2. ajoutez le fichier PHP dans le répertoire `extensions`
3. ajoutez le nom de la classe à la configuration

**Exemple:**

_/extensions/Cecil/Renderer/Extension/MyTwigExtension.php_

```php
<?php
namespace Cecil\Renderer\Extension;

class MyTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getFilters()
    {
        // add a new filter named 'md5'
        return [
            new \Twig\TwigFilter('md5', 'md5'),
        ];
    }
}
```

_configuration_

```yaml
layouts:
  extensions:
    MyExtension: Cecil\Renderer\Extension\MyTwigExtension
```

## Post-processeur de rendu

Vous pouvez post-traiter le rendu des pages.

Créez simplement une nouvelle classe PHP dans l'espace de noms `Cecil\Renderer\PostProcessor` et ajoutez le nom de la classe à la liste `output.postprocessors`.

**Exemple:**

_/extensions/Cecil/Renderer/PostProcessor/MyProcessor.php_

```php
<?php
namespace Cecil\Renderer\PostProcessor;

use Cecil\Collection\Page\Page;

class MyProcessor extends AbstractPostProcessor
{
    public function process(Page $page, string $output, string $format): string
    {
        // add a meta tag to the head of the HTML output
        if ($format == 'html') {
            if (!preg_match('/<meta name="test".*/i', $output)) {
                $meta = \sprintf('<meta name="test" content="Test">');
                $output = preg_replace_callback('/([[:blank:]]*)(<\/head>)/i', function ($matches) use ($meta) {
                    return str_repeat($matches[1] ?: ' ', 2) . $meta . "\n" . $matches[1] . $matches[2];
                }, $output);
            }
        }

        return $output;
    }
}
```

_configuration_

```yaml
output:
  postprocessors:
    MyProcessor: Cecil\Renderer\PostProcessor\MyProcessor
```
