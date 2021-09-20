<?php
declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\Routing;

use RZ\Roadiz\CompatBundle\Theme\ThemeResolverInterface;
use RZ\Roadiz\CoreBundle\Routing\NodeUrlMatcher;
use RZ\Roadiz\CoreBundle\Routing\NodeUrlMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

final class ThemeAwareNodeUrlMatcher implements UrlMatcherInterface, RequestMatcherInterface, NodeUrlMatcherInterface
{
    private ThemeResolverInterface $themeResolver;
    private NodeUrlMatcher $innerMatcher;

    public function __construct(
        ThemeResolverInterface $themeResolver,
        NodeUrlMatcher $innerMatcher
    ) {
        $this->themeResolver = $themeResolver;
        $this->innerMatcher = $innerMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function match(string $pathinfo)
    {
        $this->innerMatcher->setTheme(
            $this->themeResolver->findTheme($this->getContext()->getHost())
        );

        /*
         * Try nodes routes
         */
        return $this->innerMatcher->match($pathinfo);
    }

    public function setContext(RequestContext $context)
    {
        $this->innerMatcher->setContext($context);
    }

    public function getContext()
    {
        return $this->innerMatcher->getContext();
    }

    public function matchRequest(Request $request)
    {
        return $this->innerMatcher->matchRequest($request);
    }

    public function getSupportedFormatExtensions(): array
    {
        return $this->innerMatcher->getSupportedFormatExtensions();
    }

    public function getDefaultSupportedFormatExtension(): string
    {
        return $this->innerMatcher->getDefaultSupportedFormatExtension();
    }

    public function matchNode(string $decodedUrl): array
    {
        return $this->innerMatcher->matchNode($decodedUrl);
    }
}
