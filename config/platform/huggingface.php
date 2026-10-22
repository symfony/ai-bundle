<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Config\Definition\Configurator;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

return (new ArrayNodeDefinition('huggingface'))
    ->children()
        ->stringNode('api_key')->isRequired()->end()
        ->stringNode('provider')
            ->defaultNull()
            ->info('Inference provider for chat completion, Hugging Face picks one when null')
        ->end()
        ->booleanNode('api_catalog')
            ->defaultFalse()
            ->info('Resolve models through the Hugging Face Hub API instead of the static model catalog')
        ->end()
        ->stringNode('http_client')
            ->defaultValue('http_client')
            ->info('Service ID of the HTTP client to use')
        ->end()
    ->end();
