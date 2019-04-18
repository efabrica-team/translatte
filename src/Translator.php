<?php

declare(strict_types=1);

namespace Efabrica\Translatte;

use Efabrica\Translatte\Cache\ICache;
use Efabrica\Translatte\Cache\NullCache;
use Efabrica\Translatte\Resolver\IResolver;
use Efabrica\Translatte\Resolver\StaticResolver;
use Efabrica\Translatte\Resource\IResource;
use Nette\Localization\ITranslator;
use InvalidArgumentException;

class Translator implements ITranslator
{
    /** @var string */
    private $defaultLang;

    /** @var string  */
    private $lang;

    /** @var IResolver */
    private $resolver;

    /** @var ICache */
    private $cache;

    /** @var array */
    private $resources = [];

    /** @var array */
    private $fallbackLanguages = [];

    /** @var array */
    private $dictionaries = [];

    public function __construct(
        string $defaultLang,
        IResolver $resolver = null,
        ICache $cache = null
    ) {
        $this->defaultLang = $defaultLang;
        $this->resolver = $resolver ?: new StaticResolver($defaultLang);
        $this->cache = $cache ?: new NullCache();
    }

    /**
     * Fallback languages will be used as waterfall, first with valid result is used
     * @param array $fallbackLanguages
     */
    public function setFallbackLanguages(array $fallbackLanguages): void
    {
        $this->fallbackLanguages = $fallbackLanguages;
    }

    /**
     * Add new resource to parse translations from
     * @param IResource $resource
     * @return Translator
     */
    public function addResource(IResource $resource): self
    {
        $this->resources[] = $resource;
        return $this;
    }

    /**
     * Provide translation
     * @param string|int $message
     * @param mixed ...$parameters
     * @return string
     */
    public function translate($message, ...$parameters): string
    {
        // translate($message, int $count, array $params, string $lang = null)
        // translate($message, array $params, string $lang = null)

        $message = (string) $message;
        list($count, $params, $lang) = array_values($this->parseParameters($parameters));

        $translation = $this->getDictionary($lang)->findTranslation($message);
        if ($translation === null) {
            // Try find translation in fallback languages
            foreach ($this->fallbackLanguages as $fallbackLanguage) {
                $translation = $this->getDictionary($fallbackLanguage)->findTranslation($message);
                if ($translation !== null) {
                    break;
                }
            }

            // If translation not found in base either fallback languages return message key
            if ($translation === null) {
                return $message;
            }
        }

        $translation = $this->selectRightPluralForm($translation, $lang, $count);
        $translation = $this->replaceParams($translation, $params);

        return $translation;
    }

    /**
     * Select right plural form based on selected language
     * @param string $translation
     * @param string $lang
     * @param int $count
     * @return string
     */
    private function selectRightPluralForm(string $translation, string $lang, int $count): string
    {
        $exploded = explode('|', $translation);
        if (count($exploded) === 1) {
            return $translation;
        }

        $pluralForm = PluralForm::get($count, $lang);
        return isset($exploded[$pluralForm]) ? $exploded[$pluralForm] : $exploded[0];
    }

    /**
     * Replace parameters in translation string
     * @param string $translation
     * @param array $params
     * @return string
     */
    private function replaceParams(string $translation, array $params): string
    {
        $transParams = [];
        foreach ($params as $key => $value) {
            $transParams["%" . $key . "%"] = $value;
        }

        return strtr($translation, $transParams);
    }

    /**
     * Parse translation input parameters
     * @param array $parameters
     * @return array
     */
    private function parseParameters(array $parameters): array
    {
        if (!count($parameters)) {
            return [
                'count' => 1,
                'params' => ['count' => 1],
                'lang' => $this->getResolvedLang()
            ];
        }

        if (is_array($parameters[0])) {
            return [
                'count' => isset($parameters[0]['count']) ? $parameters[0]['count'] : 1,
                'params' => $parameters[0],
                'lang' => array_key_exists(1, $parameters) ? $parameters[1] : $this->getResolvedLang()
            ];
        }

        $params = array_key_exists(1, $parameters) ? $parameters[1] : [];
        if (!isset($params['count'])) {
            $params['count'] = $parameters[0];
        }

        return [
            'count' => $parameters[0],
            'params' => $params,
            'lang' => array_key_exists(2, $parameters) ? $parameters[2] : $this->getResolvedLang()
        ];
    }

    /**
     * Use resolvers to resolve language to use
     * @return string
     */
    private function getResolvedLang(): string
    {
        if ($this->lang === null) {
            $resolvedLang = $this->resolver->resolve();
            $this->lang = $resolvedLang !== null ? $resolvedLang : $this->defaultLang;
        }
        return $this->lang;
    }

    /**
     * Prepare and return dictionary ready to use
     * @param string $lang
     * @return Dictionary
     */
    private function getDictionary(string $lang): Dictionary
    {
        if (array_key_exists($lang, $this->dictionaries)) {
            return $this->dictionaries[$lang];
        }

        $dictionary = $this->cache->load($lang);
        if ($dictionary !== null) {
            $this->dictionaries[$lang] = $dictionary;
            return $this->dictionaries[$lang];
        }

        $this->dictionaries[$lang] = new Dictionary($lang);
        foreach ($this->resources as $resource) {
            $dictionaries = $resource->load($lang);
            foreach ($dictionaries as $dictionary) {
                if (!$dictionary instanceof Dictionary) {
                    throw new InvalidArgumentException(sprintf("%s expected. Resource returned %s", Dictionary::class, get_class($dictionary)));
                }
                $this->dictionaries[$lang]->extend($dictionary);
            }
        }

        return $this->dictionaries[$lang];
    }
}
