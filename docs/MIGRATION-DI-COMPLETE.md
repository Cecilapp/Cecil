# Migration complète vers l'injection de dépendances

## Vue d'ensemble

Cette migration étend l'architecture DI de Cecil en appliquant le pattern à 4 composants critiques :
- **Converter** (Markdown, YAML, TOML, JSON)
- **Parsedown** (Parser Markdown personnalisé)
- **GeneratorManager** (Gestion des générateurs de pages)
- **Twig Renderer** (Moteur de templates)

## Composants migrés

### 1. Converter (`Cecil\Converter\Converter`)

**Avant :**
```php
public function __construct(Builder $builder)
public function convertBody(string $string): string
{
    $parsedown = new Parsedown($this->builder);
    return $parsedown->text($string);
}
```

**Après :**
```php
public function __construct(Builder $builder, Parsedown $parsedown)
public function convertBody(string $string): string
{
    return $this->parsedown->text($string);
}
```

**Bénéfices :**
- Testabilité améliorée (mock de Parsedown)
- Pas de création d'instance à chaque conversion
- Service partagé entre Steps

**Utilisateurs du service :**
- `Cecil\Step\Pages\Convert` (injection directe)
- `Cecil\Generator\ExternalBody` (injection directe)

---

### 2. Parsedown (`Cecil\Converter\Parsedown`)

**Avant :**
```php
// Instancié directement dans Converter
$parsedown = new Parsedown($this->builder);
```

**Après :**
```php
// Service injecté dans Converter
public function __construct(Builder $builder, Parsedown $parsedown)
```

**Configuration (services.yaml) :**
```yaml
Cecil\Converter\Parsedown:
    public: true
    lazy: true
```

**Bénéfices :**
- Service réutilisable
- Lazy loading pour optimisation mémoire
- Configuration centralisée

---

### 3. GeneratorManager (`Cecil\Generator\GeneratorManager`)

**Avant :**
```php
// Dans Generate step
$generatorManager = new GeneratorManager($this->builder);
```

**Après :**
```php
// Dans Generate step constructor
public function __construct(
    GeneratorManager $generatorManager,
    ContainerInterface $container
)

// Dans process()
$generator = $this->container->get($generatorClass);
$generator->setBuilder($this->builder);
$this->generatorManager->addGenerator($generator, $priority);
```

**Adaptation des générateurs :**
- `AbstractGenerator` : ajout de `setBuilder()` pour DI
- `ExternalBody` : injection de `Converter` en plus de `Builder`
- `GeneratorInterface` : retrait de la contrainte de constructeur

**Bénéfices :**
- Générateurs injectables avec leurs propres dépendances
- Flexibilité pour ajouter des services aux générateurs
- Support de DI + mode legacy

---

### 4. Twig Renderer (`Cecil\Renderer\Twig`)

**Avant :**
```php
// Dans Render step
$this->builder->setRenderer(new Twig($this->builder, $this->getAllLayoutsPaths()));
```

**Après :**
```php
// Via factory pattern
class TwigFactory {
    public function create(Builder $builder, $templatesPath): Twig
}

// Dans Render step
public function __construct(TwigFactory $twigFactory)

$this->builder->setRenderer(
    $this->twigFactory->create($this->builder, $this->getAllLayoutsPaths())
);
```

**Bénéfices :**
- Constructeur complexe géré par factory
- Extensions Twig injectables séparément
- Configuration centralisée
- Testabilité améliorée

---

## Modifications d'architecture

### Interfaces adaptées

#### `StepInterface`
**Avant :**
```php
interface StepInterface {
    public function __construct(Builder $builder);
}
```

**Après :**
```php
interface StepInterface {
    // Pas de contrainte de constructeur
}
```

**Raison :** Permettre l'injection de dépendances spécifiques à chaque Step.

#### `GeneratorInterface`
**Avant :**
```php
interface GeneratorInterface {
    public function __construct(\Cecil\Builder $builder);
}
```

**Après :**
```php
interface GeneratorInterface {
    // Pas de contrainte de constructeur
}
```

**Raison :** Permettre l'injection de dépendances spécifiques à chaque générateur.

---

## Configuration services.yaml

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    # Converter & Parsedown
    Cecil\Converter\Converter:
        public: true
        lazy: true

    Cecil\Converter\Parsedown:
        public: true
        lazy: true

    # Generator Manager
    Cecil\Generator\GeneratorManager:
        public: true
        lazy: true

    # Twig Renderer
    Cecil\Renderer\TwigFactory:
        public: true

    Cecil\Renderer\Twig:
        lazy: true
        autowire: true

    # Builder Factory
    Cecil\BuilderFactory:
        public: true

    # Auto-registration des générateurs
    Cecil\Generator\:
        resource: '../src/Generator/*'
        exclude: '../src/Generator/{GeneratorManager.php,GeneratorInterface.php,AbstractGenerator.php}'
        tags: ['cecil.generator']

    # Auto-registration des steps
    Cecil\Step\:
        resource: '../src/Step/**/*'
        exclude: '../src/Step/{AbstractStep.php,StepInterface.php}'
        tags: ['cecil.step']
```

---

## Tests de validation

### Test 1 : Services DI
```bash
php scripts/test-di-migration.php
```

**Résultat attendu :**
- ✓ Parsedown créé
- ✓ Converter créé avec Parsedown injecté
- ✓ GeneratorManager créé
- ✓ TwigFactory créé
- ✓ Steps créés avec dépendances

### Test 2 : Steps avec injection
```bash
php scripts/test-steps-di.php
```

**Résultat attendu :**
- ✓ Convert Step avec Converter injecté
- ✓ Generate Step avec GeneratorManager et Container
- ✓ Render Step avec TwigFactory

### Test 3 : Application complète
```bash
php scripts/test-di.php
```

**Résultat attendu :**
- ✓ 36+ services enregistrés
- ✓ Builder créé
- ✓ Application fonctionnelle

---

## Impact et compatibilité

### ✅ Rétrocompatibilité
- Les générateurs existants continuent de fonctionner (mode legacy)
- `ExternalBody` supporte les deux modes

### 🔧 Modifications nécessaires pour les extensions

**Si vous créez un nouveau générateur :**
```php
class MyGenerator extends AbstractGenerator {
    public function __construct(
        Builder $builder,
        MyService $myService  // Nouvelle dépendance
    ) {
        parent::__construct($builder);
        $this->myService = $myService;
    }
}
```

**Si vous créez un nouveau step :**
```php
class MyStep extends AbstractStep {
    public function __construct(MyService $myService) {
        $this->myService = $myService;
    }
}
```

---

## Prochaines étapes possibles

1. **Migrer tous les générateurs** pour utiliser DI
2. **Migrer tous les steps** pour injecter leurs dépendances
3. **Ajouter des services** pour Renderer, Cache, Asset
4. **Container caching** pour améliorer les performances en production
5. **Tests d'intégration** pour valider les builds complets avec DI

---

## Statistiques

- **Services ajoutés :** 5 (Converter, Parsedown, GeneratorManager, TwigFactory, BuilderFactory)
- **Fichiers modifiés :** 11
- **Interfaces adaptées :** 2 (StepInterface, GeneratorInterface)
- **Steps adaptés :** 3 (Convert, Generate, Render)
- **Générateurs adaptés :** 1 (ExternalBody)

---

## Résumé

Cette migration applique le pattern DI à 4 composants critiques de Cecil, améliorant :
- **Testabilité** : Mock/stub facile des dépendances
- **Maintenabilité** : Dépendances explicites
- **Performance** : Lazy loading, services partagés
- **Extensibilité** : Ajout facile de nouvelles dépendances

Le système reste **100% rétrocompatible** tout en offrant une architecture moderne et flexible.
