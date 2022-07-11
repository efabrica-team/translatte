# Translatte

[![Build Status](https://travis-ci.org/efabrica-team/translatte.svg?branch=master)](https://travis-ci.org/efabrica-team/translatte)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/efabrica-team/translatte/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/efabrica-team/translatte/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/efabrica-team/translatte/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/efabrica-team/translatte/?branch=master)

Translator for Nette framework.

## Usage
```php
use Efabrica\Translatte\Translator;
use Efabrica\Translatte\Resource\NeonDirectoryResource;

// Create new translator with default language
$translator = new Translator('sk_SK');

// Register new resources where translations are stored
$translator->addResource(new NeonDirectoryResource([__DIR__ . '/lang', __DIR__ . '/another/lang']));
$translator->addResource(new NeonResource(__DIR__ . '/dictionary.sk_SK.neon', 'sk_SK'));

// Translate basic string
$translator->translate('dictionary.forms.error']);

// Translate with pluralization
$translator->translate('key', 10);

// Translate with parameters
$translator->translate('key', ['name' => 'Peter']);

// Translate with pluralization and parameters
$translator->translate('key', 10, ['page' => 'login']);

// Select translation language on the fly
$translator->translate('key', 1, [], 'en_US');
```

### Nette extension
```
extensions:
	translation: Efabrica\Translatte\Bridge\Nette\TranslationExtension
	
# Minimal configuration
translation:
    default: 'sk_SK' # mandatory

# Full configuration
translation:
    default: 'sk_SK' # mandatory
    fallback: # optional
        - 'en_US'
        - 'en_UK'
    dirs:
        - %appDir%/lang
    cache: Efabrica\Translatte\Cache\NullCache() # optional
    resolvers: # optional
        - Efabrica\Translatte\Resolver\StaticResolver('sk_Sk')
    resources: # optional
        - Efabrica\Translatte\Resource\NeonDirectoryResource(%appDir%/localize)
```

### Syntactic sugar

**dictionary.sk_SK.neon**:

```
cart:
    products_in_cart: 'V košíku je jeden produkt|V košíku sú %count% produkty|V košíku je %count% produktov'
```

**Source: src/PluralForm.php**

```
Example of count syntax:
sk: '1|2-4|0,5-Inf'
cz: '1|2-4|0,5-Inf'
en: '1|0,2-Inf'

Example of special count syntax (https://symfony.com/doc/3.1/components/translation/usage.html#pluralization):
en: '[-Inf,-10]big negative count|]-10,0[negative count|{0}zero count|{1}one count|{2,3,4}two,three,four count|]4,Inf]more than four count' 
sk: '[-Inf,-10]veľký negatívny počet|]-10,0[negatívny počet|{0}nula počet|{1}jedna počet|{2,3,4}dva,tri,štyri počet|]4,Inf]viac ako štyri počet' 
```

**index.php**:
```
// Translator setup
$translator = ...

// To params array is set count variable
$translator->translate('dictionary.cart.products_in_cart', 2); // V košíku sú 2 produkty

// Param count from params array is used to select right plural form
$translator->translate('dictionary.cart.products_in_cart', ['count' => 2]); // V košíku sú 2 produkty

// If we set both params nothing is override
$translator->translate('dictionary.cart.products_in_cart', 10, ['count' => 2]); // V košíku je 2 produktov
```

## 

## Main classes
### Resolver
Resolves which language has translator to use.  
Available resolvers:
 * **StaticResolver** - Resolves to given static lang.
 * **ChainResolver** - Multiple resolvers can be registered to this resolver. First resolver which returns non empty string is used.

### Resource
Represents "storage" with translation strings. It can be anything - directory with translation files, database, redis or external api call.  
Available resources:
 * **NeonResource** - One neon file.
 * **NeonDirectoryResource** - Multiple directories in which resource search neon files in format "{prefix}.{lang}.neon".

### Cache
Used for cache generated directory.
