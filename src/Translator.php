<?php

declare(strict_types=1);

namespace Efabrica\Translatte;

use Efabrica\Translatte\Cache\ICache;
use Efabrica\Translatte\Cache\NullCache;
use Efabrica\Translatte\Resolver\IResolver;
use Efabrica\Translatte\Resolver\StaticResolver;
use Efabrica\Translatte\Resource\IResource;
use InvalidArgumentException;
use Nette\Localization\ITranslator;
use Nette\Utils\Arrays;

class Translator implements ITranslator
{
    /** @var string */
    private $defaultLang;

    /** @var string */
    private $lang;

    /** @var IResolver */
    private $resolver;

    /** @var ICache */
    private $cache;

    /** @var IResource[] */
    private $resources = [];

    /** @var array */
    private $fallbackLanguages = [];

    /** @var Dictionary[] */
    private $dictionaries = [];

    /** @var array */
    public $onTranslate = [];

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

        $message = (string)$message;
        list($count, $params, $lang) = array_values($this->parseParameters($parameters));

        // If wrong input arguments passed, return message key
        if (!is_int($count) || !is_array($params) || !is_string($lang)) {
            Arrays::invoke($this->onTranslate, $this, $message, $message, $lang, (int)strval($count), (array)$params);
            return $message; // @ maybe throw exception?
        }

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
                Arrays::invoke($this->onTranslate, $this, $message, $translation, $lang, $count, $params);
                return $message;
            }
        }

        $translation = $this->selectRightPluralForm($translation, $lang, $count);
        $translation = $this->replaceParams($translation, $params);

        Arrays::invoke($this->onTranslate, $this, $message, $translation, $lang, $count, $params);
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
        foreach ($exploded as $value) {
            $translationPlural = $this->findSpecialFormat($value, $count);
            if ($translationPlural !== null) {
                return $translationPlural;
            }
        }
        $pluralForm = PluralForm::get($count, $lang);
        return $exploded[$pluralForm] ?? $exploded[0];
    }

    private function findSpecialFormat(string $translationForm, int $count): ?string
    {
        preg_match('/^\{ *[\d, ]+ *\}/', $translationForm, $result);
        $match = reset($result);
        if (!empty($match)) {
            $translationForm = str_replace($match, '', $translationForm);
            $match = str_replace(['{', '}', ' '], '', trim($match));
            $foundCountArray = explode(',', $match);
            foreach ($foundCountArray as $foundCount) {
                if ($count === (int)$foundCount) {
                    return $translationForm;
                }
            }
        }
        preg_match('/^[\[,\]] *[+,-]? *[\d,Inf]+ *, *[+,-]? *[\d,Inf]+ *[\[,\]]/', $translationForm, $result);
        $match = reset($result);
        if (!empty($match)) {
            $startChar = substr($match, 0, 1);
            $endChar = substr($match, -1);
            $translationForm = str_replace($match, '', $translationForm);
            $range = substr($match, 1, strlen($match) - 2);
            $rangeArray = explode(',', $range);
            $fromRaw = str_replace(' ', '', $rangeArray[0]);
            $toRaw = str_replace(' ', '', $rangeArray[1]);
            $from = $fromRaw === '-Inf' ? PHP_INT_MIN : (int)$fromRaw;
            $to = ($toRaw === 'Inf' || $toRaw === '+Inf') ? PHP_INT_MAX : (int)$toRaw;
            if (
                ($startChar === '[' && $endChar === ']' && $from <= $count && $count <= $to) ||
                ($startChar === ']' && $endChar === ']' && $from < $count && $count <= $to) ||
                ($startChar === ']' && $endChar === '[' && $from < $count && $count < $to) ||
                ($startChar === '[' && $endChar === '[' && $from <= $count && $count < $to)
            ) {
                return $translationForm;
            }
        }
        return null;
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
            $transParams['%' . $key . '%'] = $value;
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
        if ($parameters[0] && !isset($params['count'])) {
            $params['count'] = $parameters[0];
        }

        return [
            'count' => $parameters[0] ?? 1,
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
                    throw new InvalidArgumentException(sprintf('%s expected. Resource returned %s', Dictionary::class, get_class($dictionary)));
                }
                $this->dictionaries[$lang]->extend($dictionary);
            }
        }
        $this->cache->store($lang, $this->dictionaries[$lang]->getRecords());

        return $this->dictionaries[$lang];
    }
}
