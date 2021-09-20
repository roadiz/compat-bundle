<?php
declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\DependencyInjection;

use RZ\Roadiz\CompatBundle\Controller\AppController;
use RZ\Roadiz\CoreBundle\Entity\Theme;
use RZ\Roadiz\CoreBundle\Entity\Translation;
use RZ\Roadiz\CoreBundle\Repository\TranslationRepository;
use RZ\Roadiz\CompatBundle\Theme\StaticThemeResolver;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Slugger\AsciiSlugger;

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

        $this->registerThemes($config, $container);
        $this->registerThemeTranslatorResources($config, $container);
    }

    private function registerThemes(array $config, ContainerBuilder $container): void
    {
        $frontendThemes = [];

        foreach ($config['themes'] as $index => $themeConfig) {
            $themeSlug = (new AsciiSlugger())->slug($themeConfig['classname'], '_');
            $serviceId = 'roadiz_compat.themes.' . $themeSlug;
            /** @var class-string $className */
            $className = $themeConfig['classname'];
            $themeDir = $className::getThemeDir();
            $container->setDefinition(
                $serviceId,
                (new Definition())
                    ->setClass(Theme::class)
                    ->setPublic(true)
                    ->addMethodCall('setId', [$index])
                    ->addMethodCall('setAvailable', [true])
                    ->addMethodCall('setClassName', [$className])
                    ->addMethodCall('setHostname', [$themeConfig['hostname']])
                    ->addMethodCall('setRoutePrefix', [$themeConfig['routePrefix']])
                    ->addMethodCall('setBackendTheme', [false])
                    ->addMethodCall('setStaticTheme', [false])
                    ->addTag('roadiz_compat.theme')
            );
            $frontendThemes[] = new Reference($serviceId);

            // Register asset packages
            $container->setDefinition(
                'roadiz_compat.assets._package' . $themeSlug,
                (new Definition())
                    ->setClass(PathPackage::class)
                    ->setArguments([
                        'themes/' . $themeDir . '/static',
                        new Reference('assets.empty_version_strategy'),
                        new Reference('assets.context')
                    ])
                    ->addTag('assets.package', [
                        'package' => $themeDir
                    ])
            );

            // Add Twig paths
            $container->getDefinition('roadiz_compat.twig_loader')
                ->addMethodCall('prependPath', [
                    $className::getViewsFolder()
                ])
                ->addMethodCall('prependPath', [
                    $className::getViewsFolder(), $themeDir
                ]);
        }

        if ($container->hasDefinition(StaticThemeResolver::class)) {
            $container->getDefinition(StaticThemeResolver::class)->setArgument('$themes', $frontendThemes);
        }
    }

    private function registerThemeTranslatorResources(array $config, ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        foreach ($config['themes'] as $themeConfig) {
            /** @var class-string<AppController> $className */
            $className = $themeConfig['classname'];

            // add translations paths
            $translationFolder = $className::getTranslationsFolder();
            if ($container->hasDefinition('translator.default') && file_exists($translationFolder)) {
                $translator = $container->findDefinition('translator.default');
                $files = [];
                $finder = Finder::create()
                    ->followLinks()
                    ->files()
                    ->filter(function (\SplFileInfo $file) {
                        return 2 <= substr_count($file->getBasename(), '.') &&
                            preg_match('/\.\w+$/', $file->getBasename());
                    })
                    ->in($translationFolder)
                    ->sortByName()
                ;
                foreach ($finder as $file) {
                    $fileNameParts = explode('.', basename($file));
                    $locale = $fileNameParts[\count($fileNameParts) - 2];
                    if (!isset($files[$locale])) {
                        $files[$locale] = [];
                    }

                    $files[$locale][] = (string) $file;
                }
                $options = array_merge(
                    $translator->getArgument(4),
                    [
                        'resource_files' => $files,
                        'scanned_directories' => $scannedDirectories = [$translationFolder],
                        'cache_vary' => [
                            'scanned_directories' => array_map(static function (string $dir) use ($projectDir): string {
                                return str_starts_with($dir, $projectDir.'/') ? substr($dir, 1 + \strlen($projectDir)) : $dir;
                            }, $scannedDirectories),
                        ],
                    ]
                );

                $translator->replaceArgument(4, $options);
            }
        }
    }

}
