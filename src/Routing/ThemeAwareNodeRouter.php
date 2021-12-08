<?php

declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\Routing;

use RZ\Roadiz\CompatBundle\Theme\ThemeResolverInterface;
use RZ\Roadiz\CoreBundle\Routing\NodeRouter;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

final class ThemeAwareNodeRouter implements RouterInterface, RequestMatcherInterface, VersatileGeneratorInterface
{
    private ThemeResolverInterface $themeResolver;
    private NodeRouter $innerRouter;

    /**
     * @param ThemeResolverInterface $themeResolver
     * @param NodeRouter $innerRouter
     */
    public function __construct(ThemeResolverInterface $themeResolver, NodeRouter $innerRouter)
    {
        $this->themeResolver = $themeResolver;
        $this->innerRouter = $innerRouter;
    }

    public function setContext(RequestContext $context)
    {
        $this->innerRouter->setContext($context);
    }

    public function getContext()
    {
        return $this->innerRouter->getContext();
    }

    public function matchRequest(Request $request)
    {
        return $this->innerRouter->matchRequest($request);
    }

    public function getRouteCollection()
    {
        return $this->innerRouter->getRouteCollection();
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH)
    {
        $this->innerRouter->setTheme($this->themeResolver->findTheme($this->getContext()->getHost()));
        return $this->innerRouter->generate($name, $parameters, $referenceType);
    }

    public function match(string $pathinfo)
    {
        return $this->innerRouter->match($pathinfo);
    }

    public function supports($name)
    {
        return $this->innerRouter->supports($name);
    }

    public function getRouteDebugMessage($name, array $parameters = [])
    {
        return $this->innerRouter->getRouteDebugMessage($name, $parameters);
    }
}
