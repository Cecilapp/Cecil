# Migration du Builder vers l'Injection de Dépendances

## Vue d'ensemble

Le `Builder` a été adapté pour supporter l'injection de dépendances tout en maintenant une compatibilité totale avec l'ancien système (mode legacy).

## Changements apportés

### 1. Constructeur étendu

**Avant :**
```php
public function __construct($config = null, ?LoggerInterface $logger = null)
```

**Après :**
```php
public function __construct(
    $config = null,
    ?LoggerInterface $logger = null,
    ?Renderer\Twig $renderer = null,
    ?GeneratorManager $generatorManager = null
)
```

### 2. BuilderFactory créé

Une nouvelle factory permet de créer des instances du Builder :

```php
// Mode legacy (compatible avec l'existant)
$builder = BuilderFactory::createLegacy($config, $logger);
// ou
$builder = Builder::create($config, $logger); // méthode existante

// Mode DI (nouveau)
$builder = BuilderFactory::createFromContainer($container);

// Mode automatique
$builder = BuilderFactory::create($container, $config, $logger);
```

## Configuration dans services.yaml

Le Builder est configuré dans `config/services.yaml` :

```yaml
Cecil\Builder:
    public: true
    lazy: true
    arguments:
        $config: '@Cecil\Config'
        $logger: '@Psr\Log\LoggerInterface'
        $renderer: '@Cecil\Renderer\Twig'
        $generatorManager: '@Cecil\Generator\GeneratorManager'
```

## Avantages de cette approche

✅ **Rétrocompatibilité totale** : Le code existant continue de fonctionner sans modification  
✅ **Migration progressive** : On peut migrer service par service vers DI  
✅ **Testabilité améliorée** : Injection de mocks facilité pour les tests  
✅ **Lazy loading** : Les services lourds ne sont chargés que si nécessaire  
✅ **Découplage** : Dépendances explicites, plus facile à maintenir

## Utilisation dans les commandes

### Mode Legacy (actuel)
```php
$builder = new Builder($config, $logger);
$builder->build($options);
```

### Mode DI (futur)
```php
class BuildCommand extends AbstractCommand
{
    public function __construct(
        private Builder $builder
    ) {
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->builder->build($options);
        return 0;
    }
}
```

## Prochaines étapes

1. ✅ **Phase 1 : Infrastructure** (TERMINÉ)
   - Ajout du composant DependencyInjection
   - Création de la structure DI
   - Adaptation du Builder

2. 🔄 **Phase 2 : Services de base** (EN COURS)
   - Adapter Config pour DI
   - Adapter Renderer pour DI
   - Adapter GeneratorManager pour DI

3. ⏳ **Phase 3 : Commandes**
   - Refactorer AbstractCommand
   - Migrer les commandes une par une
   - Tester chaque commande

4. ⏳ **Phase 4 : Steps & Generators**
   - Adapter les Steps pour DI
   - Adapter les Generators pour DI
   - Utiliser les CompilerPass

5. ⏳ **Phase 5 : Activation**
   - Activer le mode DI par défaut
   - Nettoyer le code legacy
   - Documentation complète

## Tests

### Tester le mode legacy
```bash
php bin/cecil build
```

### Tester le mode DI (quand activé)
```bash
# Activer dans bin/cecil : new Application(true)
php bin/cecil build
```

## Notes techniques

- Les services injectés sont optionnels (nullable) pour maintenir la compatibilité
- Le Builder crée lui-même ses dépendances si elles ne sont pas injectées (mode legacy)
- Le `lazy: true` dans services.yaml évite l'instanciation prématurée
- Les CircularReference sont évitées en ne passant pas le Builder dans ses propres dépendances de construction
