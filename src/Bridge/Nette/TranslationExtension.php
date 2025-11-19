<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Bridge\Nette;

use Efabrica\Translatte\Latte\TranslateMacros;
use Efabrica\Translatte\Resolver\ChainResolver;
use Efabrica\Translatte\Resource\NeonDirectoryResource;
use Efabrica\Translatte\Translator;
use Latte\Engine;
use Nette\Application\UI\TemplateFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\DynamicParameter;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

/**
 * @phpstan-type TranslationConfig object{
 *     default: string,
 *     fallback: array<string>,
 *     dirs: array<string>,
 *     cache: Statement|DynamicParameter|null,
 *     resolvers: array<Statement>,
 *     resources: array<Statement>,
 *     recordTranslate: Statement|DynamicParameter|null
 * }
 */
class TranslationExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'default' => Expect::string()->required(),
            'fallback' => Expect::arrayOf('string'),
            'dirs' => Expect::arrayOf('string'),
            'cache' => Expect::anyOf(Expect::type(Statement::class), Expect::type(DynamicParameter::class)),
            'resolvers' => Expect::arrayOf(Statement::class),
            'resources' => Expect::arrayOf(Statement::class),
            'recordTranslate' => Expect::anyOf(Expect::type(Statement::class), Expect::type(DynamicParameter::class)),
        ]);
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        /** @var TranslationConfig $config */
        $config = $this->config;

        // Prepare params for translator
        $params = ['defaultLang' => $config->default];
        if (!empty($config->resolvers)) {
            $params['resolver'] = new Statement(ChainResolver::class, [$config->resolvers]);
        }
        if ($config->cache !== null) {
            $params['cache'] = $config->cache;
        }
        if ($config->recordTranslate !== null) {
            $params['recordTranslate'] = $config->recordTranslate;
        }

        $translator = $builder->addDefinition($this->prefix('translator'))
            ->setFactory(Translator::class, $params);

        // Configure translator
        foreach ($config->resources as $resource) {
            $translator->addSetup('addResource', [$resource]);
        }
        if (!empty($config->fallback)) {
            $translator->addSetup('setFallbackLanguages', [$config->fallback]);
        }
        if (!empty($config->dirs)) {
            $translator->addSetup('addResource', [new Statement(NeonDirectoryResource::class, [$config->dirs])]);
        }
    }

    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();

        /** @var ServiceDefinition $translator */
        $translator = $builder->getDefinition($this->prefix('translator'));

        $templateFactoryName = $builder->getByType(TemplateFactory::class);
        if ($templateFactoryName !== null) {
            /** @var ServiceDefinition $templateFactory */
            $templateFactory = $builder->getDefinition($templateFactoryName);
            $templateFactory->addSetup('
					$service->onCreate[] = function (Nette\\Bridges\\ApplicationLatte\\Template $template): void {
						$template->setTranslator(?);
					};', [$translator]);
        }

        if ($builder->hasDefinition('latte.latteFactory')) {
            /** @var FactoryDefinition $latteFactory */
            $latteFactory = $builder->getDefinition('latte.latteFactory');
            $latteFactory->getResultDefinition()
                ->addSetup('addProvider', ['translator', $builder->getDefinition($this->prefix('translator'))]);

            if (version_compare(Engine::VERSION, '3', '<')) { // @phpstan-ignore-line
                $latteFactory->getResultDefinition()->addSetup('?->onCompile[] = function($engine) { ?::install($engine->getCompiler()); }', ['@self', new PhpLiteral(TranslateMacros::class)]);
            }
        }
    }
}
