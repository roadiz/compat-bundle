<?php

declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\CoreBundle\Bag\NodeTypes;
use RZ\Roadiz\CoreBundle\Bag\Roles;
use RZ\Roadiz\CoreBundle\Bag\Settings;
use RZ\Roadiz\CoreBundle\Entity\NodesSources;
use RZ\Roadiz\CoreBundle\Entity\Translation;
use RZ\Roadiz\CoreBundle\EntityApi\NodeApi;
use RZ\Roadiz\CoreBundle\EntityApi\NodeSourceApi;
use RZ\Roadiz\CoreBundle\Exception\NoTranslationAvailableException;
use RZ\Roadiz\CoreBundle\Form\Error\FormErrorSerializer;
use RZ\Roadiz\CoreBundle\ListManager\EntityListManager;
use RZ\Roadiz\CoreBundle\ListManager\EntityListManagerInterface;
use RZ\Roadiz\CoreBundle\Mailer\ContactFormManager;
use RZ\Roadiz\CoreBundle\Mailer\EmailManager;
use RZ\Roadiz\CoreBundle\Node\NodeFactory;
use RZ\Roadiz\CoreBundle\Preview\PreviewResolverInterface;
use RZ\Roadiz\CoreBundle\Repository\TranslationRepository;
use RZ\Roadiz\CoreBundle\SearchEngine\Indexer\NodeIndexer;
use RZ\Roadiz\CoreBundle\SearchEngine\NodeSourceSearchHandlerInterface;
use RZ\Roadiz\CoreBundle\Security\Authorization\Chroot\NodeChrootResolver;
use RZ\Roadiz\Documents\MediaFinders\RandomImageFinder;
use RZ\Roadiz\Documents\Packages;
use RZ\Roadiz\Documents\Renderer\RendererInterface;
use RZ\Roadiz\Documents\UrlGenerators\DocumentUrlGeneratorInterface;
use RZ\Roadiz\OpenId\OAuth2LinkGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;

abstract class Controller extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'assetPackages' => Packages::class,
            'csrfTokenManager' => CsrfTokenManagerInterface::class,
            'defaultTranslation' => 'defaultTranslation',
            'dispatcher' => 'event_dispatcher',
            'em' => EntityManagerInterface::class,
            'event_dispatcher' => 'event_dispatcher',
            'kernel' => KernelInterface::class,
            'logger' => LoggerInterface::class,
            'nodeApi' => NodeApi::class,
            'nodeSourceApi' => NodeSourceApi::class,
            'nodeTypesBag' => NodeTypes::class,
            'requestStack' => RequestStack::class,
            'rolesBag' => Roles::class,
            'securityAuthenticationUtils' => AuthenticationUtils::class,
            'securityTokenStorage' => TokenStorageInterface::class,
            'settingsBag' => Settings::class,
            'stopwatch' => Stopwatch::class,
            'translator' => TranslatorInterface::class,
            'urlGenerator' => UrlGeneratorInterface::class,
            ContactFormManager::class => ContactFormManager::class,
            DocumentUrlGeneratorInterface::class => DocumentUrlGeneratorInterface::class,
            EmailManager::class => EmailManager::class,
            Environment::class => Environment::class,
            FormErrorSerializer::class => FormErrorSerializer::class,
            LoggerInterface::class => LoggerInterface::class,
            NodeChrootResolver::class => NodeChrootResolver::class,
            NodeFactory::class => NodeFactory::class,
            NodeIndexer::class => NodeIndexer::class,
            NodeSourceSearchHandlerInterface::class => NodeSourceSearchHandlerInterface::class,
            OAuth2LinkGenerator::class => OAuth2LinkGenerator::class,
            PreviewResolverInterface::class => PreviewResolverInterface::class,
            RandomImageFinder::class => RandomImageFinder::class,
            RendererInterface::class => RendererInterface::class,
            RequestStack::class => RequestStack::class,
            Security::class => Security::class,
            Settings::class => Settings::class,
            Stopwatch::class => Stopwatch::class,
            TokenStorageInterface::class => TokenStorageInterface::class,
            TranslatorInterface::class => TranslatorInterface::class,
            \RZ\Roadiz\Core\Handlers\HandlerFactoryInterface::class => HandlerFactoryInterface::class,
        ]);
    }

    /**
     * @return Request
     */
    protected function getRequest(): Request
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->get(RequestStack::class);
        $request = $requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is not available in this context');
        }
        return $request;
    }

    /**
     * @return Security
     */
    protected function getAuthorizationChecker(): Security
    {
        /** @var Security $security */ # php-stan hint
        $security = $this->get(Security::class);
        return $security;
    }

    /**
     * Alias for `$this->container['securityTokenStorage']`.
     *
     * @return TokenStorageInterface
     */
    protected function getTokenStorage(): TokenStorageInterface
    {
        /** @var TokenStorageInterface $tokenStorage */ # php-stan hint
        $tokenStorage = $this->get(TokenStorageInterface::class);
        return $tokenStorage;
    }

    /**
     * Alias for `$this->container['em']`.
     *
     * @return ObjectManager
     */
    protected function em(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator(): TranslatorInterface
    {
        /** @var TranslatorInterface $translator */ # php-stan hint
        $translator = $this->get(TranslatorInterface::class);
        return $translator;
    }

    /**
     * @return Environment
     */
    protected function getTwig(): Environment
    {
        /** @var Environment $twig */ # php-stan hint
        $twig = $this->get(Environment::class);
        return $twig;
    }

    protected function getStopwatch(): Stopwatch
    {
        /** @var Stopwatch $stopwatch */
        $stopwatch = $this->get(Stopwatch::class);
        return $stopwatch;
    }

    protected function getPreviewResolver(): PreviewResolverInterface
    {
        /** @var PreviewResolverInterface $previewResolver */
        $previewResolver = $this->get(PreviewResolverInterface::class);
        return $previewResolver;
    }

    /**
     * @param object $event
     * @return object The passed $event MUST be returned
     */
    protected function dispatchEvent($event)
    {
        /** @var EventDispatcherInterface $eventDispatcher */ # php-stan hint
        $eventDispatcher = $this->get('event_dispatcher');
        return $eventDispatcher->dispatch($event);
    }

    protected function getSettingsBag(): Settings
    {
        /** @var Settings $settingsBag */ # php-stan hint
        $settingsBag = $this->get(Settings::class);
        return $settingsBag;
    }

    /**
     * @return Packages
     * @deprecated
     */
    protected function getPackages(): Packages
    {
        /** @var Packages $packages */ # php-stan hint
        $packages = $this->get('assetPackages');
        return $packages;
    }

    protected function getHandlerFactory(): HandlerFactoryInterface
    {
        /** @var HandlerFactoryInterface $handlerFactory */ # php-stan hint
        $handlerFactory = $this->get(HandlerFactoryInterface::class);
        return $handlerFactory;
    }

    protected function getLogger(): LoggerInterface
    {
        /** @var LoggerInterface $logger */ # php-stan hint
        $logger = $this->get(LoggerInterface::class);
        return $logger;
    }

    /**
     * Wrap `$this->get('urlGenerator')->generate`
     *
     * @param string|NodesSources $route
     * @param array $parameters
     * @param int $referenceType
     * @return string
     */
    protected function generateUrl($route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        if ($route instanceof NodesSources) {
            /** @var UrlGeneratorInterface $urlGenerator */
            $urlGenerator = $this->get('urlGenerator');
            return $urlGenerator->generate(
                RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                array_merge($parameters, [RouteObjectInterface::ROUTE_OBJECT => $route]),
                $referenceType
            );
        }
        return parent::generateUrl($route, $parameters, $referenceType);
    }

    /**
     * @return string
     */
    public static function getCalledClass()
    {
        $className = get_called_class();
        if (\mb_strpos($className, "\\") !== 0) {
            $className = "\\" . $className;
        }
        return $className;
    }

    /**
     * Validate a request against a given ROLE_* and throws
     * an AccessDeniedException exception.
     *
     * @param string $role
     * @deprecated Use denyAccessUnlessGranted() method instead
     * @throws AccessDeniedException
     * @return void
     */
    public function validateAccessForRole($role)
    {
        if (!$this->isGranted($role)) {
            throw new AccessDeniedException("You don't have access to this page:" . $role);
        }
    }

    /**
     * Custom route for redirecting routes with a trailing slash.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function removeTrailingSlashAction(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        return $this->redirect($url, Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Make translation variable with the good localization.
     *
     * @param Request $request
     * @param string $_locale
     *
     * @return TranslationInterface
     * @throws NoTranslationAvailableException
     */
    protected function bindLocaleFromRoute(Request $request, $_locale = null): TranslationInterface
    {
        /*
         * If you use a static route for Home page
         * we need to grab manually language.
         *
         * Get language from static route
         */
        $translation = $this->findTranslationForLocale($_locale);
        $request->setLocale($translation->getPreferredLocale());
        return $translation;
    }

    /**
     * @param string|null $_locale
     *
     * @return TranslationInterface
     */
    protected function findTranslationForLocale(string $_locale = null): TranslationInterface
    {
        if (null === $_locale) {
            $defaultTranslation = $this->getDoctrine()->getRepository(Translation::class)->findDefault();
            if (null === $defaultTranslation) {
                throw new NoTranslationAvailableException();
            }
            return $defaultTranslation;
        }
        /** @var TranslationRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Translation::class);

        if ($this->getPreviewResolver()->isPreview()) {
            $translation = $repository->findOneByLocaleOrOverrideLocale($_locale);
        } else {
            $translation = $repository->findOneAvailableByLocaleOrOverrideLocale($_locale);
        }

        if (null !== $translation) {
            return $translation;
        }

        throw new NoTranslationAvailableException();
    }

    /**
     * Return a Response from a template string with its rendering assignation.
     *
     * @see http://api.symfony.com/2.6/Symfony/Bundle/FrameworkBundle/Controller/Controller.html#method_render
     *
     * @param string        $view Template file path
     * @param array         $parameters Twig assignation array
     * @param Response|null $response Optional Response object to customize response parameters
     * @param string        $namespace Twig loader namespace
     *
     * @return Response
     * @throws RuntimeError
     */
    public function render(string $view, array $parameters = [], Response $response = null, string $namespace = ''): Response
    {
        try {
            return parent::render($view, $parameters, $response);
        } catch (RuntimeError $e) {
            if ($e->getPrevious() instanceof \RZ\Roadiz\CoreBundle\Exception\ForceResponseException) {
                return $e->getPrevious()->getResponse();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param string $view
     * @param string $namespace
     * @return string
     */
    protected function getNamespacedView(string $view, string $namespace = ''): string
    {
        if ($namespace !== "" && $namespace !== "/") {
            return '@' . $namespace . '/' . $view;
        }

        return $view;
    }

    /**
     * @param array $data
     * @param int $httpStatus
     * @return JsonResponse
     */
    public function renderJson(array $data = [], int $httpStatus = JsonResponse::HTTP_OK)
    {
        return $this->json($data, $httpStatus);
    }

    /**
     * Throw a NotFoundException if request format is not accepted.
     *
     * @param Request $request
     * @param array $acceptableFormats
     * @return void
     */
    protected function denyResourceExceptForFormats(Request $request, array $acceptableFormats = ['html'])
    {
        if (!in_array($request->get('_format', 'html'), $acceptableFormats)) {
            throw $this->createNotFoundException(sprintf(
                'Resource not found for %s format',
                $request->get('_format', 'html')
            ));
        }
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param string $name Form name
     * @param mixed $data The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilderInterface
     */
    protected function createNamedFormBuilder(string $name = 'form', $data = null, array $options = [])
    {
        /** @var FormFactoryInterface $formFactory */
        $formFactory = $this->get('form.factory');
        return $formFactory->createNamedBuilder($name, FormType::class, $data, $options);
    }

    /**
     * Creates and returns an EntityListManager instance.
     *
     * @param mixed $entity Entity class path
     * @param array $criteria
     * @param array $ordering
     *
     * @return EntityListManagerInterface
     */
    public function createEntityListManager($entity, array $criteria = [], array $ordering = [])
    {
        return new EntityListManager(
            $this->getRequest(),
            $this->getDoctrine()->getManager(),
            $entity,
            $criteria,
            $ordering
        );
    }

    /**
     * Create and return a ContactFormManager to build and send contact
     * form by email.
     *
     * @return ContactFormManager
     */
    public function createContactFormManager(): ContactFormManager
    {
        /** @var ContactFormManager $contactFormManager */ # php-stan hinting
        $contactFormManager = $this->get(ContactFormManager::class);
        return $contactFormManager;
    }

    /**
     * Create and return a EmailManager to build and send emails.
     *
     * @return EmailManager
     */
    public function createEmailManager(): EmailManager
    {
        /** @var EmailManager $emailManager */ # php-stan hinting
        $emailManager = $this->get(EmailManager::class);
        return $emailManager;
    }

    /**
     * Get a user from the tokenStorage.
     *
     * @return UserInterface|object|null
     *
     * @throws \LogicException If tokenStorage is not available
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser()
    {
        if (!$this->has('securityTokenStorage')) {
            throw new \LogicException('No TokenStorage has been registered in your application.');
        }

        /** @var TokenInterface|null $token */
        $token = $this->getTokenStorage()->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();

        return \is_object($user) ? $user : null;
    }
}
