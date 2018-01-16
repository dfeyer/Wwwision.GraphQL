<?php
namespace Wwwision\GraphQL\Cache;

use Neos\Flow\Annotations as Flow;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Wwwision\GraphQL\Package;
use Wwwision\GraphQL\Resolver;

class SchemaCache
{
    /**
     * @var PhpFrontend
     */
    protected $cache;

    /**
     * @var array
     */
    protected $endpointsConfiguration;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param PhpFrontend $cache
     */
    public function injectCache(PhpFrontend $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param array $settings
     */
    public function injectSettings(array $settings)
    {
        $this->endpointsConfiguration = $settings['endpoints'];
    }

    /**
     * @param string $tag
     * @return string
     */
    protected function sanitizeTag(string $tag): string
    {
        return strtr($tag, '.:/', '_--');
    }

    /**
     * @param string $endpoint
     * @return Schema
     */
    public function getForEndpoint(string $endpoint): Schema
    {
        if (!$this->cache->has($endpoint)) {
            $this->buildForEndpoint($endpoint);
        }

        $code = $this->cache->requireOnce($endpoint);
        $document = AST::fromArray($code);

        /** @var Resolver[] $resolvers */
        $resolvers = [];

        return BuildSchema::build($document, function ($config) use ($endpoint, $resolvers) {
            $name = $config['name'];


            if (!isset($resolvers[$name]) && isset($this->endpointsConfiguration[$endpoint]['resolvers'][$name])) {
                $resolvers[$name] = $this->objectManager->get($this->endpointsConfiguration[$endpoint]['resolvers'][$name]);
            }

            if (isset($resolvers[$name])) {
                return $resolvers[$name]->decorateTypeConfig($config);
            }

            return $config;
        });
    }

    /**
     * @param string $endpoint
     */
    protected function buildForEndpoint(string $endpoint)
    {
        $filename = $this->endpointsConfiguration[$endpoint]['schema'];
        $content = file_get_contents($filename);
        $document = Parser::parse($content);

        $this->cache->set($endpoint, 'return ' . var_export(AST::toArray($document), true) . ';', [
            $this->sanitizeTag($filename)
        ]);
    }

    /**
     * @param string $fileMonitorIdentifier
     * @param array $changedFiles
     */
    public function flushOnFileChanges(string $fileMonitorIdentifier, array $changedFiles)
    {
        if ($fileMonitorIdentifier === Package::FILE_MONITOR_IDENTIFIER) {
            foreach(array_keys($changedFiles) as $changedFile) {
                $this->cache->flushByTag($this->sanitizeTag($changedFile));
            }
        }
    }

    /**
     *
     */
    public function warmup()
    {
        foreach(array_keys($this->endpointsConfiguration) as $endpoint) {
            $this->buildForEndpoint($endpoint);
        }
    }
}