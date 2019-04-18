<?php

declare(strict_types=1);

namespace Efabrica\Tests;

use PHPUnit\Framework\TestCase;
use Efabrica\Translatte\Dictionary;
use InvalidArgumentException;

class DictionaryTest extends TestCase
{
    public function testNoTranslation(): void
    {
        $dictionary = new Dictionary('sk_SK', []);
        $this->assertNull($dictionary->findTranslation('no_translation'));
    }

    public function testFlatRecords(): void
    {
        $records = [
            'to.translate.key1' => 'translated.key1',
            'to.translate.key2' => 'translated.key2',
            'to.translate.key24' => 'translated.key24'
        ];

        $dictionary = new Dictionary('sk_SK', $records);
        $this->assertSame('sk_SK', $dictionary->getLang());
        $this->assertSame($records, $dictionary->getRecords());
        $this->assertSame($records['to.translate.key24'], $dictionary->findTranslation('to.translate.key24'));
    }

    public function testMultidimensionalRecords(): void
    {
        $recordsMultidimensional = [
            'to' => [
                'translate' => [
                    'key1' => 'translated.key1',
                    'key2' => 'translated.key2',
                    'key24' => 'translated.key24'
                ]
            ]
        ];

        $recordsFlat = [
            'to.translate.key1' => 'translated.key1',
            'to.translate.key2' => 'translated.key2',
            'to.translate.key24' => 'translated.key24'
        ];

        $dictionary = new Dictionary('en_US', $recordsMultidimensional);
        $this->assertSame('en_US', $dictionary->getLang());
        $this->assertSame($recordsFlat, $dictionary->getRecords());
        $this->assertSame($recordsMultidimensional['to']['translate']['key24'], $dictionary->findTranslation('to.translate.key24'));
    }

    public function testExtend(): void
    {
        $records1 = [
            'to' => [
                'translate' => [
                    'key1' => 'translated.key1',
                    'key2' => 'translated.key2',
                    'key24' => 'translated.key24'
                ]
            ]
        ];

        $records2 = [
            'to' => [
                'translate' => [
                    'key7' => 'translated.key7',
                    'key8' => 'translated.key8',
                    'key77' => 'translated.key77'
                ]
            ],
            'another' => [
                'to_translate' => 'translated'
            ]
        ];

        $recordsResult = [
            'to.translate.key1' => 'translated.key1',
            'to.translate.key2' => 'translated.key2',
            'to.translate.key24' => 'translated.key24',
            'to.translate.key7' => 'translated.key7',
            'to.translate.key8' => 'translated.key8',
            'to.translate.key77' => 'translated.key77',
            'another.to_translate' => 'translated'
        ];

        $dictionary1 = new Dictionary('sk_SK', $records1);
        $dictionary2 = new Dictionary('sk_SK', $records2);
        $dictionary1->extend($dictionary2);
        $this->assertSame($recordsResult, $dictionary1->getRecords());
        $this->assertSame($recordsResult['to.translate.key24'], $dictionary1->findTranslation('to.translate.key24'));
        $this->assertSame($recordsResult['to.translate.key7'], $dictionary1->findTranslation('to.translate.key7'));
        $this->assertSame($recordsResult['another.to_translate'], $dictionary1->findTranslation('another.to_translate'));
    }

    public function testExtendWithBadLanguage(): void
    {
        $records1 = [
            'to' => [
                'translate' => [
                    'key1' => 'translated.key1',
                    'key2' => 'translated.key2',
                    'key24' => 'translated.key24'
                ]
            ]
        ];

        $records2 = [
            'to' => [
                'translate' => [
                    'key7' => 'translated.key7',
                    'key8' => 'translated.key8',
                    'key77' => 'translated.key77'
                ]
            ],
            'another' => [
                'to_translate' => 'translated'
            ]
        ];

        $dictionary1 = new Dictionary('sk_SK', $records1);
        $dictionary2 = new Dictionary('en_US', $records2);

        $this->expectException(InvalidArgumentException::class);
        $dictionary1->extend($dictionary2);
    }
}
