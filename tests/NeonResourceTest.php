<?php

declare(strict_types=1);

namespace Efabrica\Tests;

use Efabrica\Translatte\Resource\NeonResource;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class NeonResourceTest extends TestCase
{
    private const DIR = __DIR__ . '/translations/neon';

    public function testAnotherLanguageIsNotThisResourcesConcern(): void
    {
        $resource = new NeonResource(self::DIR . '/dictionary.sk_SK.neon', 'sk_SK');

        $this->assertSame([], $resource->load('en_US'));
    }

    public function testRecordsAreLoadedForItsOwnLanguage(): void
    {
        $resource = new NeonResource(self::DIR . '/dictionary.sk_SK.neon', 'sk_SK');
        $dictionaries = $resource->load('sk_SK');

        $this->assertCount(1, $dictionaries);
        $this->assertSame('sk_SK', $dictionaries[0]->getLang());
        $this->assertNotSame([], $dictionaries[0]->getRecords());
    }

    /**
     * A file with no translations is allowed and yields an empty dictionary. Only a genuine
     * failure throws, so that a caller cannot confuse "nothing to translate" with "could not read".
     */
    public function testAnEmptyFileYieldsAnEmptyDictionary(): void
    {
        $path = $this->tempFile('');
        $dictionaries = (new NeonResource($path, 'sk_SK'))->load('sk_SK');

        $this->assertCount(1, $dictionaries);
        $this->assertSame([], $dictionaries[0]->getRecords());
    }

    public function testAnUnreadableFileThrows(): void
    {
        $resource = new NeonResource(self::DIR . '/does-not-exist.sk_SK.neon', 'sk_SK');

        $this->expectException(RuntimeException::class);
        $resource->load('sk_SK');
    }

    public function testAFileThatIsNotAMapThrows(): void
    {
        $path = $this->tempFile('just a string');

        $this->expectException(RuntimeException::class);
        (new NeonResource($path, 'sk_SK'))->load('sk_SK');
    }

    private function tempFile(string $content): string
    {
        $path = (string)tempnam(sys_get_temp_dir(), 'neonres');
        file_put_contents($path, $content);

        return $path;
    }
}
