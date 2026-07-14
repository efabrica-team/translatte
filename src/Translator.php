<?php

declare(strict_types=1);

namespace Efabrica\Translatte;

use Efabrica\Translatte\Cache\ICache;
use Efabrica\Translatte\Cache\NullCache;
use Efabrica\Translatte\Record\NullRecord;
use Efabrica\Translatte\Record\RecordInterface;
use Efabrica\Translatte\Resolver\IResolver;
use Efabrica\Translatte\Resolver\StaticResolver;
use Efabrica\Translatte\Resource\IResource;
use InvalidArgumentException;
use Nette\Localization\ITranslator;
use Nette\Utils\Arrays;

class Translator implements ITranslator
{
    public const PLURAL_DELIMITER = '|';
    public const PLURAL_DELIMITER_ESCAPED = '\|';
    public const PLURAL_DELIMITER_TMP = '_PLURAL_DELIMITER_';

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

    /** @var RecordInterface */
    private $recordTranslate;

    /** @var bool */
    private $recordDestination = false;

    /** @var array<string, string|false> */
    private $latteSourceCache = [];

    /** @var array<string, array<int, int>> */
    private $latteLineMarkersCache = [];

    public function __construct(
        string $defaultLang,
        ?IResolver $resolver = null,
        ?ICache $cache = null,
        ?RecordInterface $recordTranslate = null
    ) {
        $this->defaultLang = $defaultLang;
        $this->resolver = $resolver ?: new StaticResolver($defaultLang);
        $this->cache = $cache ?: new NullCache();
        $this->recordTranslate = $recordTranslate ?: new NullRecord();
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
     * When enabled, the file (and line) each translation was requested from
     * is resolved and passed to the record translate service.
     */
    public function setRecordDestination(bool $recordDestination): void
    {
        $this->recordDestination = $recordDestination;
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

        if ($this->recordDestination && !$this->recordTranslate instanceof NullRecord) {
            $this->recordTranslate->save($message, $this->resolveDestination());
        } else {
            $this->recordTranslate->save($message);
        }

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

        $translation = $this->fixEscapedDelimiter($translation);
        $translation = $this->selectRightPluralForm($translation, $lang, $count);
        $translation = $this->fixBackEscapedDelimiter($translation);
        $translation = $this->replaceParams($translation, $params);

        Arrays::invoke($this->onTranslate, $this, $message, $translation, $lang, $count, $params);
        return $translation;
    }

    /**
     * Resolve where the translation was requested from: the direct caller (file, line)
     * and the full call chain (trace, outermost call first) leading to the translate call.
     * @return array{file: string, line: int|null, trace: array<int, string>}|null null when the caller cannot be resolved
     */
    private function resolveDestination(): ?array
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        $trace = [];
        $direct = null;
        $fallback = null;
        foreach ($backtrace as $frame) {
            $file = $frame['file'] ?? null;
            if ($file === __FILE__) {
                continue;
            }
            if ($file !== null) {
                if ($fallback === null) {
                    $fallback = $frame;
                }
                // frames inside vendor (latte runtime, nette bridges, ...) are not the real caller
                if ($direct === null && strpos($file, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) === false) {
                    $direct = $frame;
                }
            }
            $step = $this->formatChainStep($frame);
            if ($step !== null) {
                $trace[] = $step;
            }
        }
        $frame = $direct ?? $fallback;
        if ($frame === null) {
            return null;
        }
        $destination = $this->formatDestination($frame);
        $destination['trace'] = array_reverse($trace);
        return $destination;
    }

    /**
     * Format one backtrace frame as "file:line Class->function()" — the function
     * that was called at that place. Latte compiled files are mapped to their source.
     * @param array{file?: string, line?: int, function?: string, class?: class-string, type?: string} $frame
     */
    private function formatChainStep(array $frame): ?string
    {
        $function = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
        $file = $frame['file'] ?? null;
        if ($file === null) {
            return $function !== '' ? $function . '()' : null;
        }
        $line = $frame['line'] ?? null;
        $latteSource = $this->resolveLatteSource($file);
        if ($latteSource !== null) {
            $line = $line !== null ? $this->resolveLatteSourceLine($file, (int)$line) : null;
            $file = $latteSource;
        }
        $step = $file . ($line !== null ? ':' . $line : '');
        if ($function !== '') {
            $step .= ' ' . $function . '()';
        }
        return $step;
    }

    /**
     * @param array{file: string, line?: int} $frame
     * @return array{file: string, line: int|null}
     */
    private function formatDestination(array $frame): array
    {
        $latteSource = $this->resolveLatteSource($frame['file']);
        if ($latteSource !== null) {
            $line = isset($frame['line']) ? $this->resolveLatteSourceLine($frame['file'], (int)$frame['line']) : null;
            return ['file' => $latteSource, 'line' => $line];
        }
        return ['file' => $frame['file'], 'line' => $frame['line'] ?? null];
    }

    /**
     * Compiled latte templates contain a "source:" comment pointing to the original .latte file.
     */
    private function resolveLatteSource(string $file): ?string
    {
        if (array_key_exists($file, $this->latteSourceCache)) {
            return $this->latteSourceCache[$file] === false ? null : $this->latteSourceCache[$file];
        }
        $source = false;
        $head = @file_get_contents($file, false, null, 0, 512);
        if ($head !== false
            && preg_match('~^(?:/\*\*?|//)\s*source:\s*(.+?\.latte)\s*(?:\*/)?\s*$~m', $head, $matches)
        ) {
            $source = trim($matches[1]);
        }
        $this->latteSourceCache[$file] = $source;
        return $source === false ? null : $source;
    }

    /**
     * Compiled latte statements carry "pos LINE:COL" (Latte 3) or "line LINE" (Latte 2) markers.
     * Finds the marker closest to the given compiled line and returns the source template line.
     */
    private function resolveLatteSourceLine(string $file, int $compiledLine): ?int
    {
        if (!array_key_exists($file, $this->latteLineMarkersCache)) {
            $markers = [];
            $lines = @file($file);
            if ($lines !== false) {
                foreach ($lines as $i => $line) {
                    if (preg_match('~/\* (?:pos|line) (\d+)~', $line, $matches)) {
                        $markers[$i + 1] = (int)$matches[1];
                    }
                }
            }
            $this->latteLineMarkersCache[$file] = $markers;
        }
        $markers = $this->latteLineMarkersCache[$file];
        foreach ([0, 1, 2, 3, -1, -2, -3, -4, -5, -6, -7, -8, -9, -10] as $offset) {
            if (isset($markers[$compiledLine + $offset])) {
                return $markers[$compiledLine + $offset];
            }
        }
        return null;
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

    private function fixEscapedDelimiter(string $translation): string
    {
        return str_replace(self::PLURAL_DELIMITER_ESCAPED, self::PLURAL_DELIMITER_TMP, $translation);
    }

    private function fixBackEscapedDelimiter(string $translation): string
    {
        return str_replace(self::PLURAL_DELIMITER_TMP, self::PLURAL_DELIMITER, $translation);
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
            if (($startChar === '[' && $endChar === ']' && $from <= $count && $count <= $to) ||
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
        if (count($parameters) === 0) {
            return [
                'count' => 1,
                'params' => ['count' => 1],
                'lang' => $this->getResolvedLang(),
            ];
        }

        if (is_array($parameters[0])) {
            return [
                'count' => isset($parameters[0]['count']) ? $parameters[0]['count'] : 1,
                'params' => $parameters[0],
                'lang' => array_key_exists(1, $parameters) ? $parameters[1] : $this->getResolvedLang(),
            ];
        }

        $params = array_key_exists(1, $parameters) ? $parameters[1] : [];
        if ($parameters[0] && !isset($params['count'])) {
            $params['count'] = $parameters[0];
        }

        return [
            'count' => $parameters[0] ?? 1,
            'params' => $params,
            'lang' => array_key_exists(2, $parameters) ? $parameters[2] : $this->getResolvedLang(),
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

        $dictionaryCache = $this->cache->load($lang);
        if ($dictionaryCache !== null) {
            $this->dictionaries[$lang] = $dictionaryCache;
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

    public function reset(): void
    {
        $this->resources = [];
        $this->dictionaries = [];
    }
}
