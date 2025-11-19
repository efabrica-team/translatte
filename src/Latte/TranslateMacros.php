<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Latte;

use Latte\Compiler;
use Latte\Engine;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;

class TranslateMacros extends MacroSet
{
    public static function install(Compiler $compiler): void
    {
        $me = new self($compiler);
        $me->addMacro('_', [$me, 'macroTranslate'], [$me, 'macroTranslate']);
    }

    public function macroTranslate(MacroNode $node, PhpWriter $writer): string
    {
        if ($node->closing) {
            if (strpos($node->content, '<?php') === false) {
                $value = var_export($node->content, true);
                $node->content = '';
            } else {
                $node->openingCode = '<?php ob_start(function () {}) ?>' . $node->openingCode;
                $value = 'ob_get_clean()';
            }

            if (!defined(Engine::class . '::VERSION_ID') || Engine::VERSION_ID < 20804) { // @phpstan-ignore-line
                return $writer->write('$_fi = new LR\FilterInfo(%var); echo %modifyContent($this->filters->filterContent("translate", $_fi, %raw))', $node->context[0], $value);
            }

            if (Engine::VERSION_ID >= 20900 && Engine::VERSION_ID < 20902) { // @phpstan-ignore-line
                return $writer->write('$__fi = new LR\FilterInfo(%var); echo %modifyContent($this->filters->filterContent("translate", $__fi, %raw))', $node->context[0], $value);
            }

            return $writer->write('$ʟ_fi = new LR\FilterInfo(%var); echo %modifyContent($this->filters->filterContent("translate", $ʟ_fi, %raw))', $node->context[0], $value);
        } elseif ($node->args !== '') {
            $node->empty = true;
            if ($this->containsOnlyOneWord($node)) {
                return $writer->write('echo %modify(call_user_func($this->filters->translate, %node.word))');
            }

            return $writer->write('echo %modify(call_user_func($this->filters->translate, %node.word, %node.args))');
        }

        return '';
    }

    private function containsOnlyOneWord(MacroNode $node): bool
    {
        $result = trim($node->tokenizer->joinUntil(',')) === trim($node->args);
        $node->tokenizer->reset();
        return $result;
    }
}
