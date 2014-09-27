<?php

namespace Jab\Config\EntityBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class JabConfigEntityExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
	    $configuration = $this->getConfiguration($configs, $container);
	    $config = $this->processConfiguration($configuration, $configs);

	    $container->setParameter('jab_config_entity.show_unmanaged', $config['entity_config']['show_unmanaged']);
	    $container->setParameter('jab_config_entity.my_name', $config['entity_config']['my_name']);

        //$loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        //$loader->load('services.xml');
    }

	public function getConfiguration(array $configs, ContainerBuilder $container) {
		$param = [];
		return new Configuration($param);
	}
}
