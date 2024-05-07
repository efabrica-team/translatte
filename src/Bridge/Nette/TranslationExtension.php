<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Bridge\Nette;

use Efabrica\Translatte\Resolver\ChainResolver;
use Efabrica\Translatte\Resource\NeonDirectoryResource;
use Efabrica\Translatte\Translator;
use Nette\Application\UI\ITemplateFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\DynamicParameter;
use Nette\DI\ServiceDefinition;
use Nette\Localization\ITranslator;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

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
            'resources' => Expect::arrayOf(Statement::class)
        ]);
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        // Prepare params for translator
        $params = ['defaultLang' => $this->config->default];
        if (!empty($this->config->resolvers)) {
            $params['resolver'] = new Statement(ChainResolver::class, [$this->config->resolvers]);
        }
        if ($this->config->cache) {
            $params['cache'] = $this->config->cache;
        }

        $translator = $builder->addDefinition($this->prefix('translator'))
            ->setFactory(Translator::class, $params);

        // Configure translator
        foreach ($this->config->resources as $resource) {
            $translator->addSetup('addResource', [$resource]);
        }
        if (!empty($this->config->fallback)) {
            $translator->addSetup('setFallbackLanguages', [$this->config->fallback]);
        }
        if (!empty($this->config->dirs)) {
            $translator->addSetup('addResource', [new Statement(NeonDirectoryResource::class, [$this->config->dirs])]);
        }
    }

    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();

        /** @var ServiceDefinition $translator */
        $translator = $builder->getDefinition($this->prefix('translator'));

        $templateFactoryName = $builder->getByType(ITemplateFactory::class);
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
        }
    }
}
