<?php

declare(strict_types=1);

namespace Efabrica\Tests;

use PHPUnit\Framework\TestCase;
use Efabrica\Translatte\Translator;
use Efabrica\Translatte\Resource\NeonDirectoryResource;

class TranslatorTest extends TestCase
{
    public function testLanguage(): void
    {
        $translator = new Translator('sk_SK');
        $translator->addResource(new NeonDirectoryResource([__DIR__ . '/translations']));
        $this->assertSame("Akcie", $translator->translate('dictionary.app.article.actions'));
        $this->assertSame("Actions", $translator->translate('dictionary.app.article.actions', 1, [], 'en_US'));
        $this->assertSame("Actions", $translator->translate('dictionary.app.article.actions', [], 'en_US'));
    }

    public function testParams(): void
    {
        $translator = new Translator('sk_SK');
        $translator->addResource(new NeonDirectoryResource([__DIR__ . '/translations']));
        $this->assertSame(
            "Naozaj chcete odstrániť článok <strong>Article no.1</strong>?",
            $translator->translate('dictionary.app.article.are_you_sure_you_want_to_delete_article', ['articleTitle' => 'Article no.1'])
        );
        $this->assertSame(
            "Are you sure you want to delete the article <strong>Article no.1</strong>?",
            $translator->translate('dictionary.app.article.are_you_sure_you_want_to_delete_article', 1, ['articleTitle' => 'Article no.1'], 'en_US')
        );
    }

    public function testCount(): void
    {
        $translator = new Translator('sk_SK');
        $translator->addResource(new NeonDirectoryResource([__DIR__ . '/translations']));
        $this->assertSame("článok", $translator->translate('dictionary.app.article.articles_count'));
        $this->assertSame("články", $translator->translate('dictionary.app.article.articles_count', 4));
        $this->assertSame("článkov", $translator->translate('dictionary.app.article.articles_count', 100));

        $this->assertSame("article", $translator->translate('dictionary.app.article.articles_count', 1, [], 'en_US'));
        $this->assertSame("articles", $translator->translate('dictionary.app.article.articles_count', 4, [], 'en_US'));
        $this->assertSame("articles", $translator->translate('dictionary.app.article.articles_count', 99, [], 'en_US'));
    }

    public function testCountWithPlaceholder(): void
    {
        $translator = new Translator('sk_SK');
        $translator->addResource(new NeonDirectoryResource([__DIR__ . '/translations']));
        $this->assertSame("1 článok", $translator->translate('dictionary.app.article.articles_count_with_placeholder'));
        $this->assertSame("4 články", $translator->translate('dictionary.app.article.articles_count_with_placeholder', 4));
        $this->assertSame("100 článkov", $translator->translate('dictionary.app.article.articles_count_with_placeholder', 100));
        $this->assertSame("4 články", $translator->translate('dictionary.app.article.articles_count_with_placeholder', ['count' => 4]));
        $this->assertSame("100 článkov", $translator->translate('dictionary.app.article.articles_count_with_placeholder', ['count' => 100]));

        $this->assertSame("1 article", $translator->translate('dictionary.app.article.articles_count_with_placeholder', 1, [], 'en_US'));
        $this->assertSame("4 articles", $translator->translate('dictionary.app.article.articles_count_with_placeholder', 4, [], 'en_US'));
        $this->assertSame("99 articles", $translator->translate('dictionary.app.article.articles_count_with_placeholder', 99, [], 'en_US'));
        $this->assertSame("4 articles", $translator->translate('dictionary.app.article.articles_count_with_placeholder', ['count' => 4], 'en_US'));
        $this->assertSame("99 articles", $translator->translate('dictionary.app.article.articles_count_with_placeholder', ['count' => 99], 'en_US'));

        // Count has higher priority for plural form as parameter count (but dont replace count parameter in params array)
        $this->assertSame("100 články", $translator->translate('dictionary.app.article.articles_count_with_placeholder', 3, ['count' => 100]));
        $this->assertSame("4 article", $translator->translate('dictionary.app.article.articles_count_with_placeholder', 1, ['count' => 4], 'en_US'));
    }


    public function testCountSpecialFormat(): void
    {
        $translator = new Translator('sk_SK');
        $translator->addResource(new NeonDirectoryResource([__DIR__ . '/translations']));
        $this->assertSame("veľký negatívny počet", $translator->translate('dictionary.app.article.count_special_format', -99));
        $this->assertSame("negatívny počet", $translator->translate('dictionary.app.article.count_special_format', -1));
        $this->assertSame("nula počet", $translator->translate('dictionary.app.article.count_special_format', 0));
        $this->assertSame("jedna počet", $translator->translate('dictionary.app.article.count_special_format', 1));
        $this->assertSame("dva,tri,štyri počet", $translator->translate('dictionary.app.article.count_special_format', 2));
        $this->assertSame("dva,tri,štyri počet", $translator->translate('dictionary.app.article.count_special_format', 4));
        $this->assertSame("viac ako štyri počet", $translator->translate('dictionary.app.article.count_special_format', 99));

        $this->assertSame("big negative count", $translator->translate('dictionary.app.article.count_special_format', -99, [], 'en_US'));
        $this->assertSame("negative count", $translator->translate('dictionary.app.article.count_special_format', -1, [], 'en_US'));
        $this->assertSame("zero count", $translator->translate('dictionary.app.article.count_special_format', 0, [], 'en_US'));
        $this->assertSame("one count", $translator->translate('dictionary.app.article.count_special_format', 1, [], 'en_US'));
        $this->assertSame("two,three,four count", $translator->translate('dictionary.app.article.count_special_format', 2, [], 'en_US'));
        $this->assertSame("two,three,four count", $translator->translate('dictionary.app.article.count_special_format', 4, [], 'en_US'));
        $this->assertSame("more than four count", $translator->translate('dictionary.app.article.count_special_format', 99, [], 'en_US'));
    }

    public function testCountSpecialFormatWithPlaceholder(): void
    {
        $translator = new Translator('sk_SK');
        $translator->addResource(new NeonDirectoryResource([__DIR__ . '/translations']));
        $this->assertSame("-99 - veľký negatívny počet", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', -99));
        $this->assertSame("-1 - negatívny počet", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', -1));
        $this->assertSame("0 - nula počet", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 0));
        $this->assertSame("1 - jedna počet", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 1));
        $this->assertSame("2 - dva,tri,štyri počet", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 2));
        $this->assertSame("4 - dva,tri,štyri počet", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 4));
        $this->assertSame("99 - viac ako štyri počet", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 99));

        $this->assertSame("-99 - big negative count", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', -99, [], 'en_US'));
        $this->assertSame("-1 - negative count", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', -1, [], 'en_US'));
        $this->assertSame("0 - zero count", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 0, [], 'en_US'));
        $this->assertSame("1 - one count", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 1, [], 'en_US'));
        $this->assertSame("2 - two,three,four count", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 2, [], 'en_US'));
        $this->assertSame("4 - two,three,four count", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 4, [], 'en_US'));
        $this->assertSame("99 - more than four count", $translator->translate('dictionary.app.article.count_special_format_with_placeholder', 99, [], 'en_US'));
    }

    public function testCountSpecialFormatWithParams(): void
    {
        $translator = new Translator('sk_SK');
        $translator->addResource(new NeonDirectoryResource([__DIR__ . '/translations']));
        $this->assertSame("Test: -99 - veľký negatívny počet", $translator->translate('dictionary.app.article.count_special_format_with_params', -99, ['title' => 'Test']));
        $this->assertSame("Test: -1 - negatívny počet", $translator->translate('dictionary.app.article.count_special_format_with_params', -1, ['title' => 'Test']));
        $this->assertSame("Test: 0 - nula počet", $translator->translate('dictionary.app.article.count_special_format_with_params', 0, ['title' => 'Test']));
        $this->assertSame("Test: 1 - jedna počet", $translator->translate('dictionary.app.article.count_special_format_with_params', 1, ['title' => 'Test']));
        $this->assertSame("Test: 2 - dva,tri,štyri počet", $translator->translate('dictionary.app.article.count_special_format_with_params', 2, ['title' => 'Test']));
        $this->assertSame("Test: 4 - dva,tri,štyri počet", $translator->translate('dictionary.app.article.count_special_format_with_params', 4, ['title' => 'Test']));
        $this->assertSame("Test: 99 - viac ako štyri počet", $translator->translate('dictionary.app.article.count_special_format_with_params', 99, ['title' => 'Test']));
        $this->assertSame("Test: -99 - veľký negatívny počet", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', -99, ['title' => 'Test']));
        $this->assertSame("Test: -1 - negatívny počet", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', -1, ['title' => 'Test']));
        $this->assertSame("Test: 0 - nula počet", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 0, ['title' => 'Test']));
        $this->assertSame("Test: 1 - jedna počet", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 1, ['title' => 'Test']));
        $this->assertSame("Test: 2 - dva,tri,štyri počet", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 2, ['title' => 'Test']));
        $this->assertSame("Test: 4 - dva,tri,štyri počet", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 4, ['title' => 'Test']));
        $this->assertSame("Test: 99 - viac ako štyri počet", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 99, ['title' => 'Test']));

        $this->assertSame("Test: -99 - big negative count", $translator->translate('dictionary.app.article.count_special_format_with_params', -99, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: -1 - negative count", $translator->translate('dictionary.app.article.count_special_format_with_params', -1, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 0 - zero count", $translator->translate('dictionary.app.article.count_special_format_with_params', 0, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 1 - one count", $translator->translate('dictionary.app.article.count_special_format_with_params', 1, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 2 - two,three,four count", $translator->translate('dictionary.app.article.count_special_format_with_params', 2, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 4 - two,three,four count", $translator->translate('dictionary.app.article.count_special_format_with_params', 4, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 99 - more than four count", $translator->translate('dictionary.app.article.count_special_format_with_params', 99, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: -99 - big negative count", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', -99, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: -1 - negative count", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', -1, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 0 - zero count", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 0, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 1 - one count", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 1, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 2 - two,three,four count", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 2, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 4 - two,three,four count", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 4, ['title' => 'Test'], 'en_US'));
        $this->assertSame("Test: 99 - more than four count", $translator->translate('dictionary.app.article.count_special_format_with_spaces_with_params', 99, ['title' => 'Test'], 'en_US'));
    }

    public function testAll(): void
    {
        $skParams = ['category' => 'Novinky', 'section' => 'Článnky'];
        $enParams = ['category' => 'News', 'section' => 'Articles'];

        $translator = new Translator('sk_SK');
        $translator->addResource(new NeonDirectoryResource([__DIR__ . '/translations']));
        $this->assertSame("článok v kategórii Novinky a sekcii Článnky", $translator->translate('dictionary.app.article.articles_count_with_params', $skParams));
        $this->assertSame("články v kategórii Novinky a sekcii Článnky", $translator->translate('dictionary.app.article.articles_count_with_params', 4, $skParams));
        $this->assertSame("článkov v kategórii Novinky a sekcii Článnky", $translator->translate('dictionary.app.article.articles_count_with_params', 100, $skParams));

        $this->assertSame("article in category News and section Articles", $translator->translate('dictionary.app.article.articles_count_with_params', 1, $enParams, 'en_US'));
        $this->assertSame("articles in category News and section Articles", $translator->translate('dictionary.app.article.articles_count_with_params', 4, $enParams, 'en_US'));
        $this->assertSame("articles in category News and section Articles", $translator->translate('dictionary.app.article.articles_count_with_params', 99, $enParams, 'en_US'));
    }
}
