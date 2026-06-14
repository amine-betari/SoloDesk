# Tests

Les tests fonctionnels utilisent Symfony BrowserKit avec PHPUnit. Ils exécutent
de vraies requêtes HTTP contre l'application, mais n'exécutent pas le
JavaScript dans un navigateur.

## Initialiser la base de test

La configuration Doctrine ajoute automatiquement le suffixe `_test` à la base
de données. Les commandes suivantes créent donc `SoloDesk_test` sans modifier
la base de développement :

```bash
APP_ENV=test APP_SECRET=test-secret-not-for-production php bin/console doctrine:database:create --if-not-exists
APP_ENV=test APP_SECRET=test-secret-not-for-production php bin/console doctrine:schema:create
```

## Exécuter les tests

Toute la suite :

```bash
php vendor/bin/simple-phpunit -c phpunit.dist.xml
```

Uniquement les parcours fonctionnels des formulaires de création :

```bash
php vendor/bin/simple-phpunit -c phpunit.dist.xml tests/Functional/NewFormFlowTest.php
```

Uniquement les contrats Twig des formulaires de création :

```bash
php vendor/bin/simple-phpunit -c phpunit.dist.xml tests/Template/NewFormContractTest.php
```

## Recréer la base de test

```bash
APP_ENV=test APP_SECRET=test-secret-not-for-production php bin/console doctrine:database:drop --force --if-exists
APP_ENV=test APP_SECRET=test-secret-not-for-production php bin/console doctrine:database:create
APP_ENV=test APP_SECRET=test-secret-not-for-production php bin/console doctrine:schema:create
```
