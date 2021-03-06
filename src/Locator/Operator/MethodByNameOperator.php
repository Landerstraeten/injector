<?php

declare(strict_types=1);

namespace Injector\Locator\Operator;

use PhpCsFixer\Tokenizer\Tokens;

final class MethodByNameOperator implements TokenOperatorInterface
{
    private const METHOD_FUNC_REGEX = '/METHODNAME\(([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\)/';

    public function operates(string $location): bool
    {
        return (bool) preg_match(self::METHOD_FUNC_REGEX, $location);
    }

    public function searchIndex(Tokens $tokens, int $previousIndex, string $location): ?int
    {
        $methodName = $this->parseMethodName($location);
        $index = $previousIndex;
        while ($index = $tokens->getNextTokenOfKind($index, [[T_FUNCTION]])) {
            $nameIndex = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$nameIndex]->getContent() === $methodName) {
                return $index;
            }
        }

        return null;
    }

    private function parseMethodName($location): string
    {
        $matches = [];
        preg_match(self::METHOD_FUNC_REGEX, $location, $matches);

        return $matches[1] ?? '';
    }
}
