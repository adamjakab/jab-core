<?php

namespace Jab\Config\EntityBundle\DependencyInjection;

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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jab_config_entity');

	    $rootNode
		    ->children()
			    ->arrayNode("entity_config")
				    ->children()
					    ->booleanNode("show_unmanaged")->defaultTrue()->end()
					    ->scalarNode("my_name")->end()
				    ->end()
			    ->end()
		    ->end()
	    ;

        return $treeBuilder;
    }
}
