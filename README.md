# Translatte

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

### Resolver
Resolves which language has translator to use.  
Available resolvers:
 * **StaticResolver** - Resolves to given static lang.
 * **ChainResolver** - Multiple resolvers can be registered to this resolver. First resolver which returns non empty string is used.
 
<code>IResolver:</code>
```
<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resolver;

interface IResolver
{
    public function resolve(): ?string;
}

```

### Resource
Represents "storage" with translation strings. It can be anything - directory with translation files, database, redis or external api call.  
Available resources:
 * **NeonResource** - One neon file.
 * **NeonDirectoryResource** - Multiple directories in which resource search neon files in format "{prefix}.{lang}.neon".

<code>IResource:</code>
```
<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resource;

interface IResource
{
    public function load(string $lang): array;
}
```

### Cache
How translator cache generated directory

<code>ICache:</code>
```
<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Cache;

interface ICache
{
    public function store(string $lang, array $data): void;

    public function load(string $lang): ?array;
}

```