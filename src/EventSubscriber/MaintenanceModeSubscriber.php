<?php
declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\EventSubscriber;

use RZ\Roadiz\CompatBundle\Controller\AppController;
use RZ\Roadiz\CoreBundle\Bag\Settings;
use RZ\Roadiz\CoreBundle\Entity\Theme;
use RZ\Roadiz\CoreBundle\Exception\MaintenanceModeException;
use RZ\Roadiz\CompatBundle\Theme\ThemeResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

/**
 * @package RZ\Roadiz\CoreBundle\Event
 */
final class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    private Settings $settings;
    private Security $security;
    private ThemeResolverInterface $themeResolver;
    private ContainerInterface $serviceLocator;

    public function __construct(
        Settings $settings,
        Security $security,
        ThemeResolverInterface $themeResolver,
        ContainerInterface $serviceLocator
    ) {
        $this->settings = $settings;
        $this->security = $security;
        $this->themeResolver = $themeResolver;
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * @return array
     */
    private function getAuthorizedRoutes()
    {
        return [
            'loginPage',
            'loginRequestPage',
            'loginRequestConfirmPage',
            'loginResetConfirmPage',
            'loginResetPage',
            'loginFailedPage',
            'loginCheckPage',
            'logoutPage',
            'FontFile',
            'FontFaceCSS',
            'loginImagePage',
            'interventionRequestProcess',
        ];
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 31],
            // Should be lower than RouterListener (32) to be executed after!
        ];
    }

    /**
     * @param RequestEvent $event
     * @throws MaintenanceModeException
     */
    public function onRequest(RequestEvent $event)
    {
        if ($event->isMainRequest()) {
            $maintenanceMode = (bool) $this->settings->get('maintenance_mode', false);
            if (
                $maintenanceMode === true &&
                !$this->security->isGranted('ROLE_BACKEND_USER') &&
                !in_array($event->getRequest()->get('_route'), $this->getAuthorizedRoutes())
            ) {
                $theme = $this->themeResolver->findTheme(null);
                if (null !== $theme) {
                    throw new MaintenanceModeException($this->getControllerForTheme($theme, $event->getRequest()));
                }
                throw new MaintenanceModeException();
            }
        }
    }

    /**
     * @param Theme   $theme
     * @param Request $request
     *
     * @return AbstractController
     */
    private function getControllerForTheme(Theme $theme, Request $request)
    {
        $ctrlClass = $theme->getClassName();
        $controller = new $ctrlClass();
        $serviceId = get_class($controller);

        if ($this->serviceLocator->has($serviceId)) {
            $controller = $this->serviceLocator->get($serviceId);
        }
        if ($controller instanceof AppController) {
            $controller->prepareBaseAssignation();
        }

        // No node controller matching in install mode
        $request->attributes->set('theme', $controller->getTheme());

        /*
         * Set request locale if _locale param
         * is present in Route.
         */
        $routeParams = $request->get('_route_params');
        if (!empty($routeParams["_locale"])) {
            $request->setLocale($routeParams["_locale"]);
        }

        return $controller;
    }
}
