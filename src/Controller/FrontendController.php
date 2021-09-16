<?php
declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\Controller;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\CoreBundle\Entity\Node;
use RZ\Roadiz\CoreBundle\Entity\NodesSources;
use RZ\Roadiz\CoreBundle\EntityHandler\NodesSourcesHandler;
use RZ\Roadiz\CoreBundle\Preview\PreviewResolverInterface;
use RZ\Roadiz\CoreBundle\Routing\NodeRouteHelper;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Frontend controller to handle a page request.
 *
 * This class must be inherited in order to create a new theme.
 */
abstract class FrontendController extends AppController
{
    /**
     * {@inheritdoc}
     */
    public static int $priority = 10;
    /**
     * {@inheritdoc}
     */
    protected static string $themeName = 'Default theme';
    /**
     * {@inheritdoc}
     */
    protected static string $themeAuthor = 'Ambroise Maupate';
    /**
     * {@inheritdoc}
     */
    protected static string $themeCopyright = 'REZO ZERO';
    /**
     * {@inheritdoc}
     */
    protected static string $themeDir = 'DefaultTheme';
    /**
     * {@inheritdoc}
     */
    protected static bool $backendTheme = false;
    /**
     * Put here your node which need a specific controller
     * instead of a node-type controller.
     *
     * @var array<string>
     */
    protected static array $specificNodesControllers = [
        'home',
    ];

    protected ?Node $node = null;
    protected ?NodesSources $nodeSource = null;
    protected ?TranslationInterface $translation = null;
    /**
     * @var ContainerInterface|null
     * @deprecated Use a service locator object
     */
    protected ?ContainerInterface $themeContainer = null;

    /**
     * Append objects to global container.
     *
     * Add a request matcher on frontend to make securityTokenStorage
     * available even when no user has logged in.
     *
     * @param ContainerInterface $container
     * @deprecated
     */
    public static function setupDependencyInjection(ContainerInterface $container)
    {
        parent::setupDependencyInjection($container);
        static::addThemeTemplatesPath($container);

        $container->get(Packages::class)->addPackage(static::getThemeDir(), new PathPackage(
            'themes/' . static::getThemeDir() . '/static',
            $container['versionStrategy'],
            new RequestStackContext($container['requestStack'])
        ));
    }

    /**
     * @return Node|null
     */
    public function getNode(): ?Node
    {
        return $this->node;
    }

    /**
     * @return NodesSources|null
     */
    public function getNodeSource(): ?NodesSources
    {
        return $this->nodeSource;
    }

    /**
     * @return TranslationInterface|null
     */
    public function getTranslation(): ?TranslationInterface
    {
        return $this->translation;
    }

    /**
     * Default action for any node URL.
     *
     * @param Request $request
     * @param Node|null $node
     * @param TranslationInterface|null $translation
     *
     * @return Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        TranslationInterface $translation = null
    ) {
        $this->get(Stopwatch::class)->start('handleNodeController');
        $this->node = $node;
        $this->translation = $translation;

        //  Main node based routing method
        return $this->handle($request, $this->node, $this->translation);
    }

    /**
     * Handle node based routing, returns a Response object
     * for a node-based request.
     *
     * @param Request $request
     * @param Node|null $node
     * @param TranslationInterface|null $translation
     * @return Response
     */
    protected function handle(
        Request $request,
        Node $node = null,
        TranslationInterface $translation = null
    ) {
        $this->get(Stopwatch::class)->start('handleNodeController');

        if ($node !== null) {
            $nodeRouteHelper = new NodeRouteHelper(
                $node,
                $this->getTheme(),
                $this->get(PreviewResolverInterface::class),
                $this->get(LoggerInterface::class),
                'RZ\Roadiz\CoreBundle\Controller\DefaultNodeSourceController'
            );
            $controllerPath = $nodeRouteHelper->getController();
            $method = $nodeRouteHelper->getMethod();

            if (true !== $nodeRouteHelper->isViewable()) {
                $msg = "No front-end controller found for '" .
                $node->getNodeName() .
                "' node. You need to create a " . $controllerPath . ".";
                throw $this->createNotFoundException($msg);
            }

            return $this->forward($controllerPath . '::' . $method, [
                'node' => $node,
                'translation' => $translation
            ]);
        }

        throw $this->createNotFoundException("No front-end controller found");
    }

    /**
     * Initialize controller with environment from another controller
     * in order to avoid initializing same component again.
     *
     * @param array $baseAssignation
     * @param ContainerInterface|null $themeContainer
     * @deprecated
     */
    public function __initFromOtherController(
        array &$baseAssignation = [],
        ContainerInterface $themeContainer = null
    ) {
        $this->assignation = $baseAssignation;
        $this->themeContainer = $themeContainer;
    }

    /**
     * Default action for default URL (homepage).
     *
     * @param Request $request
     * @param string|null $_locale
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction(Request $request, $_locale = null)
    {
        /*
         * If you use a static route for Home page
         * we need to grab manually language.
         *
         * Get language from static route
         */
        $translation = $this->bindLocaleFromRoute($request, $_locale);

        /*
         * Grab home flagged node
         */
        $node = $this->getHome($translation);
        $this->prepareThemeAssignation($node, $translation);

        return $this->render('home.html.twig', $this->assignation);
    }

    /**
     * Store basic information for your theme from a Node object.
     *
     * @param Node|null $node
     * @param TranslationInterface|null $translation
     *
     * @return void
     */
    protected function prepareThemeAssignation(Node $node = null, TranslationInterface $translation = null)
    {
        if (null === $this->themeContainer) {
            $this->get(Stopwatch::class)->start('prepareThemeAssignation');
            $this->storeNodeAndTranslation($node, $translation);
            $home = $this->getHome($translation);
            if (null !== $home) {
                $this->assignation['home'] = $home;
                $this->assignation['homeSource'] = $home->getNodeSourcesByTranslation($translation)->first();
            }
            /*
             * Use a DI container to delay API requests
             */
            $this->themeContainer = new Container();

            $this->get(Stopwatch::class)->start('extendAssignation');
            $this->extendAssignation();
            $this->get(Stopwatch::class)->stop('extendAssignation');
            $this->get(Stopwatch::class)->stop('prepareThemeAssignation');
        }
    }

    /**
     * Store current node and translation into controller.
     *
     * It makes following fields available into template assignation:
     *
     * * node
     * * nodeSource
     * * translation
     * * pageMeta
     *     * title
     *     * description
     *     * keywords
     *
     * @param Node|null $node
     * @param TranslationInterface|null $translation
     */
    public function storeNodeAndTranslation(Node $node = null, TranslationInterface $translation = null)
    {
        $this->node = $node;
        $this->translation = $translation;
        $this->assignation['translation'] = $this->translation;
        $this->getRequest()->attributes->set('translation', $this->translation);

        if (null !== $this->node && null !== $translation) {
            $this->getRequest()->attributes->set('node', $this->node);
            $this->nodeSource = $this->node->getNodeSourcesByTranslation($translation)->first() ?: null;
            $this->assignation['node'] = $this->node;
            $this->assignation['nodeSource'] = $this->nodeSource;
        }

        $this->assignation['pageMeta'] = $this->getNodeSEO();
    }

    /**
     * Get SEO information for current node.
     *
     * This method must return a 3-fields array with:
     *
     * * `title`
     * * `description`
     * * `keywords`
     *
     * @param NodesSources|null $fallbackNodeSource
     *
     * @return array
     */
    public function getNodeSEO(NodesSources $fallbackNodeSource = null)
    {
        if (null !== $this->nodeSource) {
            /** @var NodesSourcesHandler $nodesSourcesHandler */
            $nodesSourcesHandler = $this->get(HandlerFactoryInterface::class)->getHandler($this->nodeSource);
            return $nodesSourcesHandler->getSEO();
        }

        if (null !== $fallbackNodeSource) {
            /** @var NodesSourcesHandler $nodesSourcesHandler */
            $nodesSourcesHandler = $this->get(HandlerFactoryInterface::class)->getHandler($fallbackNodeSource);
            return $nodesSourcesHandler->getSEO();
        }

        return [
            'title' => '',
            'description' => '',
            'keywords' => '',
        ];
    }

    /**
     * Extends theme assignation with custom data.
     *
     * Override this method in your theme to add your own service
     * and data.
     */
    protected function extendAssignation()
    {
    }

    /**
     * Add a default translation locale for static routes and
     * node SEO data.
     *
     * * [parent assignations…]
     * * **_default_locale**
     * * meta
     *     * siteName
     *     * siteCopyright
     *     * siteDescription
     */
    public function prepareBaseAssignation()
    {
        parent::prepareBaseAssignation();

        $translation = $this->get('defaultTranslation');
        $this->assignation['_default_locale'] = $translation->getLocale();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function maintenanceAction(Request $request)
    {
        $translation = $this->bindLocaleFromRoute($request, $request->getLocale());
        $this->prepareThemeAssignation(null, $translation);

        return new Response(
            $this->renderView('maintenance.html.twig', $this->assignation),
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Store basic information for your theme from a NodesSources object.
     *
     * @param NodesSources|null $nodeSource
     * @param TranslationInterface|null $translation
     *
     * @return void
     */
    protected function prepareNodeSourceAssignation(
        NodesSources $nodeSource = null,
        TranslationInterface $translation = null
    ) {
        if (null === $this->themeContainer) {
            $this->storeNodeSourceAndTranslation($nodeSource, $translation);
            /** @deprecated Should not fetch home at each request */
            $this->assignation['home'] = $this->getHome($translation);
            /*
             * Use a DI container to delay API requests
             */
            $this->themeContainer = new Container();

            $this->extendAssignation();
        }
    }

    /**
     * Store current nodeSource and translation into controller.
     *
     * It makes following fields available into template assignation:
     *
     * * node
     * * nodeSource
     * * translation
     * * pageMeta
     *     * title
     *     * description
     *     * keywords
     *
     * @param NodesSources|null $nodeSource
     * @param TranslationInterface|null $translation
     */
    public function storeNodeSourceAndTranslation(
        NodesSources $nodeSource = null,
        TranslationInterface $translation = null
    ) {
        $this->nodeSource = $nodeSource;

        if (null !== $this->nodeSource) {
            $this->node = $this->nodeSource->getNode();
            $this->translation = $this->nodeSource->getTranslation();

            $this->getRequest()->attributes->set('translation', $this->translation);
            $this->getRequest()->attributes->set('node', $this->node);

            $this->assignation['translation'] = $this->translation;
            $this->assignation['node'] = $this->node;
            $this->assignation['nodeSource'] = $this->nodeSource;
        } else {
            $this->translation = $translation;
            $this->assignation['translation'] = $this->translation;
            $this->getRequest()->attributes->set('translation', $this->translation);
        }

        $this->assignation['pageMeta'] = $this->getNodeSEO();
    }

    /**
     * Deny access (404) node-source access if its publication date is in the future.
     *
     * @throws \Exception
     */
    protected function denyAccessUnlessPublished()
    {
        if (null !== $this->nodeSource) {
            if ($this->nodeSource->getPublishedAt() > new \DateTime() &&
                !$this->get(PreviewResolverInterface::class)->isPreview()) {
                throw $this->createNotFoundException();
            }
        }
    }
}
