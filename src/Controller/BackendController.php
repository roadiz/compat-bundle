<?php
declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\Controller;

/**
 * Special controller app file for backend themes.
 *
 * This AppController implementation will use a security scheme
 */
abstract class BackendController extends AppController
{
    protected static bool $backendTheme = true;
    public static int $priority = -10;

    /**
     * @inheritDoc
     */
    public function createEntityListManager($entity, array $criteria = [], array $ordering = [])
    {
        return parent::createEntityListManager($entity, $criteria, $ordering)
            ->setDisplayingNotPublishedNodes(true);
    }
}
