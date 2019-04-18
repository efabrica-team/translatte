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
