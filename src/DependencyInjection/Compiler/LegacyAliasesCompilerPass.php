<?php
declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\DependencyInjection\Compiler;

use RZ\Roadiz\CompatBundle\Aliases;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\String\UnicodeString;

class LegacyAliasesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach (Aliases::getAliases() as $className => $alias) {
            if (
                !(new UnicodeString($className))->containsAny([
                    '\\Entity\\',
                    '\\DependencyInjection',
                    '\\Doctrine\\Event',
                    '\\Traits',
                    '\\CoreBundle\\Event\\',
                    'RZ\\Roadiz\\CMS\\Forms',
                    '\\ListManager',
                    '\\NodeDuplicator',
                    '\\NodeRouteHelper',
                    'AbstractXlsxSerializer',
                    'BackendController',
                    'Kernel',
                    'CustomFormHelper',
                    '\\Exception',
                    '\\Recaptcha',
                    '\\DataTransformer',
                ])
            ) {
                $container->setAlias($alias, $className)->setPublic(true)->setDeprecated(true);
            }
        }
    }
}
