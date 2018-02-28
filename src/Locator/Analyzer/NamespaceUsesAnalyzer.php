<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Injector\Locator\Analyzer;

use Injector\Locator\Analyzer\Analysis\NamespaceUseAnalysis;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 *  TODO: remove once this one is in php-cs-fixer core.
 *
 * @internal
 */
final class NamespaceUsesAnalyzer
{
    /**
     * @param Tokens $tokens
     *
     * @return NamespaceUseAnalysis[]
     */
    public function getDeclarationsFromTokens(Tokens $tokens)
    {
        $tokenAnalyzer = new TokensAnalyzer($tokens);
        $useIndexes = $tokenAnalyzer->getImportUseIndexes();

        return $this->getDeclarations($tokens, $useIndexes);
    }

    /**
     * @param Tokens $tokens
     * @param array  $useIndexes
     *
     * @return NamespaceUseAnalysis[]
     */
    public function getDeclarations(Tokens $tokens, array $useIndexes)
    {
        $uses = [];

        foreach ($useIndexes as $index) {
            $endIndex = (int) $tokens->getNextTokenOfKind($index, [';', [T_CLOSE_TAG]]);
            $analysis = $this->parseDeclaration($tokens, $index, $endIndex);
            if ($analysis) {
                $uses[$analysis->getShortName()] = $analysis;
            }
        }

        return $uses;
    }

    /**
     * @param Tokens $tokens
     * @param int    $startIndex
     * @param int    $endIndex
     *
     * @return NamespaceUseAnalysis|null
     */
    private function parseDeclaration(Tokens $tokens, $startIndex, $endIndex)
    {
        $fullName = $shortName = '';
        $aliased = false;

        for ($i = $startIndex; $i <= $endIndex; ++$i) {
            $token = $tokens[$i];
            if ($token->equals(',') || $token->isGivenKind(CT::T_GROUP_IMPORT_BRACE_CLOSE)) {
                // do not touch group use declarations until the logic of this is added (for example: `use some\a\{ClassD};`)
                // ignore multiple use statements that should be split into few separate statements (for example: `use BarB, BarC as C;`)
                return null;
            }

            if ($token->isWhitespace() || $token->isComment() || $token->isGivenKind([T_USE])) {
                continue;
            }

            if ($token->isGivenKind(T_STRING)) {
                $shortName = $token->getContent();
                if (!$aliased) {
                    $fullName .= $shortName;
                }
                continue;
            }
            if ($token->isGivenKind(T_NS_SEPARATOR)) {
                $fullName .= $token->getContent();
                continue;
            }

            if ($token->isGivenKind(T_AS)) {
                $aliased = true;
            }
        }

        return new NamespaceUseAnalysis(
            trim($fullName),
            $shortName,
            $aliased,
            $startIndex,
            $endIndex
        );
    }
}
