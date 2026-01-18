# Configuration de l'injection de dépendances Cecil

Ce répertoire contient la configuration de l'injection de dépendances pour Cecil, basée sur le composant Symfony DependencyInjection.

## Fichiers

### `services.yaml`
Fichier de configuration principal définissant tous les services de l'application :
- Services de base (Config, Builder, Logger)
- Renderer et extensions Twig
- Générateurs de pages
- Steps de build
- Commandes console
- Convertisseurs et utilitaires

### Classes principales

#### `ContainerBuilder`
Classe responsable de la construction et de la configuration du container d'injection de dépendances. Elle charge la configuration depuis `services.yaml` et enregistre les compiler passes.

#### `CecilExtension`
Extension Symfony pour la configuration personnalisée de Cecil. Permet d'ajouter des options de configuration supplémentaires.

#### `Configuration`
Définit la structure de configuration validée pour Cecil, avec valeurs par défaut et validation des types.

### Compiler Passes

#### `GeneratorPass`
Enregistre automatiquement tous les générateurs de pages tagués avec `cecil.generator` dans le `GeneratorManager`. Supporte les priorités.

#### `StepPass`
Enregistre automatiquement tous les steps de build tagués avec `cecil.step` et les rend accessibles au Builder.

#### `TwigExtensionPass`
Enregistre automatiquement toutes les extensions Twig tagguées avec `cecil.twig.extension` dans le Renderer.

## Utilisation

### Mode legacy (par défaut)
```php
$app = new Application();
```

### Mode avec injection de dépendances
```php
$app = new Application(true);
$container = $app->getContainer();
$builder = $container->get('Cecil\Builder');
```

## Migration progressive

L'implémentation actuelle permet une **migration progressive** :
- Le mode legacy reste fonctionnel par défaut
- Le mode DI peut être activé progressivement
- Les deux modes coexistent pendant la transition
- Pas de breaking changes pour les utilisateurs

## Avantages

✅ **Testabilité** : Injection de mocks facilité  
✅ **Performance** : Lazy loading des services  
✅ **Maintenabilité** : Dépendances explicites  
✅ **Extensibilité** : Ajout de services simplifié  
✅ **Configuration** : Centralisée et type-safe  

## Prochaines étapes

1. Refactorer les commandes pour utiliser l'injection de dépendances
2. Adapter le Builder pour recevoir ses dépendances par injection
3. Migrer les générateurs et steps
4. Activer le mode DI par défaut
5. Supprimer le mode legacy
