<?php

declare(strict_types=1);

namespace RZ\Roadiz\FontBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\CoreBundle\Repository\EntityRepository;
use RZ\Roadiz\FontBundle\Entity\Font;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @package RZ\Roadiz\CoreBundle\Repository
 * @extends EntityRepository<\RZ\Roadiz\FontBundle\Entity\Font>
 */
final class FontRepository extends EntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct($registry, Font::class, $dispatcher);
    }

    public function getLatestUpdateDate()
    {
        $query = $this->_em->createQuery('
            SELECT MAX(f.updatedAt) FROM RZ\Roadiz\FontBundle\Entity\Font f');

        return $query->getSingleScalarResult();
    }
}
