<?php
declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\String\UnicodeString;

class RoadizCompatExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/../config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $aliases = require __DIR__ . '/../aliases.php';
        foreach ($aliases as $className => $alias) {
            if (!(new UnicodeString($className))->containsAny(['\\Entity\\', '\\DependencyInjection'])) {
                $container->setAlias($alias, $className);
            }
        }
    }
}
