<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\AiBundle\Tests\Fixture\Tool;

use Symfony\AI\AiBundle\Security\Attribute\IsGrantedTool;
use Symfony\Component\ExpressionLanguage\Expression;

#[IsGrantedTool('test:permission', new Expression('args["itemId"] ?? 0'), message: 'No access to ToolWithIsGrantedOnClass tool.')]
final class ToolWithIsGrantedOnClass
{
    public function __invoke(int $itemId): void
    {
    }
}
