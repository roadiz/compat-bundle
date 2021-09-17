<?php
declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\DependencyInjection;

use RZ\Roadiz\CoreBundle\Entity\Translation;
use RZ\Roadiz\CoreBundle\Repository\TranslationRepository;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

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

        $container->setDefinition(
            'defaultTranslation',
            (new Definition())
                ->setClass(Translation::class)
                ->setFactory([new Reference(TranslationRepository::class), 'findDefault'])
                ->setShared(true)
                ->setPublic(true)
                ->setDeprecated()
        );
    }
}
