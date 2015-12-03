<?php

namespace Screamz\SecureDownloadBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('secure_download');

        $rootNode->children()
            ->scalarNode('stashPrefixKey')->defaultValue('secure_download_bundle')->end()
            ->scalarNode('documentHashSalt')->defaultValue('screamzSecureDownloader')->end();

        return $treeBuilder;
    }
}
