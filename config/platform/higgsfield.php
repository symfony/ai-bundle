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

return (new ArrayNodeDefinition('higgsfield'))
    ->children()
        ->stringNode('api_key')->isRequired()->end()
        ->stringNode('api_secret')->isRequired()->end()
        ->stringNode('base_url')
            ->info('Base URL of the Higgsfield API. Defaults to "https://platform.higgsfield.ai" when null.')
        ->end()
        ->stringNode('http_client')
            ->defaultValue('http_client')
            ->info('Service ID of the HTTP client to use')
        ->end()
        ->stringNode('model_catalog')
            ->info('Service ID of the model catalog to use')
        ->end()
    ->end();
