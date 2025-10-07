<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\AI\Agent\StructuredOutput\AgentProcessor as StructureOutputProcessor;
use Symfony\AI\Agent\StructuredOutput\ResponseFormatFactory;
use Symfony\AI\Agent\StructuredOutput\ResponseFormatFactoryInterface;
use Symfony\AI\Agent\Toolbox\AgentProcessor as ToolProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Agent\Toolbox\ToolboxInterface;
use Symfony\AI\Agent\Toolbox\ToolCallArgumentResolver;
use Symfony\AI\Agent\Toolbox\ToolFactory\AbstractToolFactory;
use Symfony\AI\Agent\Toolbox\ToolFactory\ReflectionToolFactory;
use Symfony\AI\Agent\Toolbox\ToolResultConverter;
use Symfony\AI\AiBundle\Command\AgentCallCommand;
use Symfony\AI\AiBundle\Command\PlatformInvokeCommand;
use Symfony\AI\AiBundle\Profiler\DataCollector;
use Symfony\AI\AiBundle\Profiler\TraceableToolbox;
use Symfony\AI\AiBundle\Security\EventListener\IsGrantedToolAttributeListener;
use Symfony\AI\Chat\Command\DropStoreCommand as DropMessageStoreCommand;
use Symfony\AI\Chat\Command\SetupStoreCommand as SetupMessageStoreCommand;
use Symfony\AI\Platform\Bridge\AiMlApi\ModelCatalog as AiMlApiModelCatalog;
use Symfony\AI\Platform\Bridge\Anthropic\Contract\AnthropicContract;
use Symfony\AI\Platform\Bridge\Anthropic\ModelCatalog as AnthropicModelCatalog;
use Symfony\AI\Platform\Bridge\Anthropic\TokenOutputProcessor as AnthropicTokenOutputProcessor;
use Symfony\AI\Platform\Bridge\Azure\Meta\ModelCatalog as AzureMetaModelCatalog;
use Symfony\AI\Platform\Bridge\Azure\OpenAi\ModelCatalog as AzureOpenAiModelCatalog;
use Symfony\AI\Platform\Bridge\Cerebras\ModelCatalog as CerebrasModelCatalog;
use Symfony\AI\Platform\Bridge\DeepSeek\ModelCatalog as DeepSeekModelCatalog;
use Symfony\AI\Platform\Bridge\DockerModelRunner\ModelCatalog as DockerModelRunnerModelCatalog;
use Symfony\AI\Platform\Bridge\ElevenLabs\ModelCatalog as ElevenLabsModelCatalog;
use Symfony\AI\Platform\Bridge\Gemini\Contract\GeminiContract;
use Symfony\AI\Platform\Bridge\Gemini\ModelCatalog as GeminiModelCatalog;
use Symfony\AI\Platform\Bridge\Gemini\TokenOutputProcessor as GeminiTokenOutputProcessor;
use Symfony\AI\Platform\Bridge\HuggingFace\ModelCatalog as HuggingFaceModelCatalog;
use Symfony\AI\Platform\Bridge\LmStudio\ModelCatalog as LmStudioModelCatalog;
use Symfony\AI\Platform\Bridge\Mistral\ModelCatalog as MistralModelCatalog;
use Symfony\AI\Platform\Bridge\Mistral\TokenOutputProcessor as MistralTokenOutputProcessor;
use Symfony\AI\Platform\Bridge\Ollama\Contract\OllamaContract;
use Symfony\AI\Platform\Bridge\Ollama\ModelCatalog as OllamaModelCatalog;
use Symfony\AI\Platform\Bridge\OpenAi\Contract\OpenAiContract;
use Symfony\AI\Platform\Bridge\OpenAi\ModelCatalog as OpenAiModelCatalog;
use Symfony\AI\Platform\Bridge\OpenAi\TokenOutputProcessor as OpenAiTokenOutputProcessor;
use Symfony\AI\Platform\Bridge\OpenRouter\ModelCatalog as OpenRouterModelCatalog;
use Symfony\AI\Platform\Bridge\Perplexity\Contract\PerplexityContract;
use Symfony\AI\Platform\Bridge\Perplexity\ModelCatalog as PerplexityModelCatalog;
use Symfony\AI\Platform\Bridge\Perplexity\SearchResultProcessor as PerplexitySearchResultProcessor;
use Symfony\AI\Platform\Bridge\Perplexity\TokenOutputProcessor as PerplexityTokenOutputProcessor;
use Symfony\AI\Platform\Bridge\Replicate\ModelCatalog as ReplicateModelCatalog;
use Symfony\AI\Platform\Bridge\Scaleway\ModelCatalog as ScalewayModelCatalog;
use Symfony\AI\Platform\Bridge\VertexAi\Contract\GeminiContract as VertexAiGeminiContract;
use Symfony\AI\Platform\Bridge\VertexAi\ModelCatalog as VertexAiModelCatalog;
use Symfony\AI\Platform\Bridge\VertexAi\TokenOutputProcessor as VertexAiTokenOutputProcessor;
use Symfony\AI\Platform\Bridge\Voyage\ModelCatalog as VoyageModelCatalog;
use Symfony\AI\Platform\Contract;
use Symfony\AI\Platform\Contract\JsonSchema\DescriptionParser;
use Symfony\AI\Platform\Contract\JsonSchema\Factory as SchemaFactory;
use Symfony\AI\Store\Command\DropStoreCommand;
use Symfony\AI\Store\Command\IndexCommand;
use Symfony\AI\Store\Command\SetupStoreCommand;

return static function (ContainerConfigurator $container): void {
    $container->services()
        // contract
        ->set('ai.platform.contract.default', Contract::class)
            ->factory([Contract::class, 'create'])
        ->set('ai.platform.contract.openai', Contract::class)
            ->factory([OpenAiContract::class, 'create'])
        ->set('ai.platform.contract.anthropic', Contract::class)
            ->factory([AnthropicContract::class, 'create'])
        ->set('ai.platform.contract.gemini', Contract::class)
            ->factory([GeminiContract::class, 'create'])
        ->set('ai.platform.contract.vertexai.gemini', Contract::class)
            ->factory([VertexAiGeminiContract::class, 'create'])
        ->set('ai.platform.contract.ollama', Contract::class)
            ->factory([OllamaContract::class, 'create'])
        ->set('ai.platform.contract.perplexity', Contract::class)
            ->factory([PerplexityContract::class, 'create'])

        // model catalog
        ->set('ai.platform.model_catalog.aimlapi', AiMlApiModelCatalog::class)
        ->set('ai.platform.model_catalog.anthropic', AnthropicModelCatalog::class)
        ->set('ai.platform.model_catalog.azure.meta', AzureMetaModelCatalog::class)
        ->set('ai.platform.model_catalog.azure.openai', AzureOpenAiModelCatalog::class)
        ->set('ai.platform.model_catalog.cerebras', CerebrasModelCatalog::class)
        ->set('ai.platform.model_catalog.deepseek', DeepSeekModelCatalog::class)
        ->set('ai.platform.model_catalog.dockermodelrunner', DockerModelRunnerModelCatalog::class)
        ->set('ai.platform.model_catalog.elevenlabs', ElevenLabsModelCatalog::class)
        ->set('ai.platform.model_catalog.gemini', GeminiModelCatalog::class)
        ->set('ai.platform.model_catalog.huggingface', HuggingFaceModelCatalog::class)
        ->set('ai.platform.model_catalog.lmstudio', LmStudioModelCatalog::class)
        ->set('ai.platform.model_catalog.mistral', MistralModelCatalog::class)
        ->set('ai.platform.model_catalog.ollama', OllamaModelCatalog::class)
        ->set('ai.platform.model_catalog.openai', OpenAiModelCatalog::class)
        ->set('ai.platform.model_catalog.openrouter', OpenRouterModelCatalog::class)
        ->set('ai.platform.model_catalog.perplexity', PerplexityModelCatalog::class)
        ->set('ai.platform.model_catalog.replicate', ReplicateModelCatalog::class)
        ->set('ai.platform.model_catalog.scaleway', ScalewayModelCatalog::class)
        ->set('ai.platform.model_catalog.vertexai.gemini', VertexAiModelCatalog::class)
        ->set('ai.platform.model_catalog.voyage', VoyageModelCatalog::class)

        // structured output
        ->set('ai.agent.response_format_factory', ResponseFormatFactory::class)
            ->args([
                service('ai.platform.json_schema_factory'),
            ])
        ->set('ai.platform.json_schema.description_parser', DescriptionParser::class)
        ->set('ai.platform.json_schema_factory', SchemaFactory::class)
            ->args([
                service('ai.platform.json_schema.description_parser'),
                service('type_info.resolver')->nullOnInvalid(),
            ])
        ->alias(ResponseFormatFactoryInterface::class, 'ai.agent.response_format_factory')
        ->set('ai.agent.structured_output_processor', StructureOutputProcessor::class)
            ->args([
                service('ai.agent.response_format_factory'),
                service('serializer'),
            ])

        // tools
        ->set('ai.toolbox.abstract', Toolbox::class)
            ->abstract()
            ->args([
                abstract_arg('Collection of tools'),
                service('ai.tool_factory'),
                service('ai.tool_call_argument_resolver'),
                service('logger')->ignoreOnInvalid(),
                service('event_dispatcher')->nullOnInvalid(),
            ])
        ->set('ai.toolbox', Toolbox::class)
            ->parent('ai.toolbox.abstract')
            ->arg('index_0', tagged_iterator('ai.tool'))
        ->alias(ToolboxInterface::class, 'ai.toolbox')
        ->set('ai.tool_factory.abstract', AbstractToolFactory::class)
            ->abstract()
            ->args([
                service('ai.platform.json_schema_factory'),
            ])
        ->set('ai.tool_factory', ReflectionToolFactory::class)
            ->parent('ai.tool_factory.abstract')
        ->set('ai.tool_result_converter', ToolResultConverter::class)
            ->args([
                service('serializer'),
            ])
        ->set('ai.tool_call_argument_resolver', ToolCallArgumentResolver::class)
            ->args([
                service('serializer'),
                service('type_info.resolver')->nullOnInvalid(),
            ])
        ->set('ai.tool.agent_processor.abstract', ToolProcessor::class)
            ->abstract()
            ->args([
                abstract_arg('Toolbox'),
                service('ai.tool_result_converter'),
                service('event_dispatcher')->nullOnInvalid(),
                false,
            ])
        ->set('ai.tool.agent_processor', ToolProcessor::class)
            ->parent('ai.tool.agent_processor.abstract')
            ->arg('index_0', service('ai.toolbox'))
        ->set('ai.security.is_granted_attribute_listener', IsGrantedToolAttributeListener::class)
            ->args([
                service('security.authorization_checker'),
                service('expression_language')->nullOnInvalid(),
            ])
            ->tag('kernel.event_listener')

        // profiler
        ->set('ai.data_collector', DataCollector::class)
            ->args([
                tagged_iterator('ai.traceable_platform'),
                service('ai.toolbox'),
                tagged_iterator('ai.traceable_toolbox'),
            ])
            ->tag('data_collector')
        ->set('ai.traceable_toolbox', TraceableToolbox::class)
            ->decorate('ai.toolbox', priority: -1)
            ->args([
                service('.inner'),
            ])
            ->tag('ai.traceable_toolbox')

        // token usage processors
        ->set('ai.platform.token_usage_processor.anthropic', AnthropicTokenOutputProcessor::class)
        ->set('ai.platform.token_usage_processor.gemini', GeminiTokenOutputProcessor::class)
        ->set('ai.platform.token_usage_processor.mistral', MistralTokenOutputProcessor::class)
        ->set('ai.platform.token_usage_processor.openai', OpenAiTokenOutputProcessor::class)
        ->set('ai.platform.token_usage_processor.perplexity', PerplexityTokenOutputProcessor::class)
        ->set('ai.platform.token_usage_processor.vertexai', VertexAiTokenOutputProcessor::class)

        // search result processors
        ->set('ai.platform.search_result_processor.perplexity', PerplexitySearchResultProcessor::class)

        // commands
        ->set('ai.command.chat', AgentCallCommand::class)
            ->args([
                tagged_locator('ai.agent', 'name'),
            ])
            ->tag('console.command')
        ->set('ai.command.setup_store', SetupStoreCommand::class)
            ->args([
                tagged_locator('ai.store', 'name'),
            ])
            ->tag('console.command')
        ->set('ai.command.drop_store', DropStoreCommand::class)
            ->args([
                tagged_locator('ai.store', 'name'),
            ])
            ->tag('console.command')
        ->set('ai.command.index', IndexCommand::class)
            ->args([
                tagged_locator('ai.indexer', 'name'),
            ])
            ->tag('console.command')
        ->set('ai.command.platform_invoke', PlatformInvokeCommand::class)
            ->args([
                tagged_locator('ai.platform', 'name'),
            ])
            ->tag('console.command')
        ->set('ai.command.setup_message_store', SetupMessageStoreCommand::class)
            ->args([
                tagged_locator('ai.message_store', 'name'),
            ])
            ->tag('console.command')
        ->set('ai.command.drop_message_store', DropMessageStoreCommand::class)
            ->args([
                tagged_locator('ai.message_store', 'name'),
            ])
            ->tag('console.command')
    ;
};
