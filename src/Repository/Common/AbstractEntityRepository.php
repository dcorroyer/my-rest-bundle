<?php

declare(strict_types=1);

namespace My\RestBundle\Repository\Common;

use My\RestBundle\Contract\ORMFilterInterface;
use My\RestBundle\Dto\PaginationQueryParams;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository as BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @template T of object
 *
 * @template-extends BaseRepository<T>
 */
abstract class AbstractEntityRepository extends BaseRepository implements ServiceEntityRepositoryInterface
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private readonly PaginatorInterface $paginator,
    ) {
        parent::__construct($managerRegistry, $this->getEntityClass());
    }

    /**
     * @return class-string<T> The entity class name
     */
    abstract public function getEntityClass(): string;

    public function save(object $entity, bool $flush = false): string|int|null
    {
        $this->_em->persist($entity);

        if ($flush) {
            $this->_em->flush();

            return $entity->getId();  // @phpstan-ignore-line
        }

        return null;
    }

    public function delete(object $entity, bool $flush = false): string|int
    {
        $id = $entity->getId();  // @phpstan-ignore-line
        $this->_em->remove($entity);

        if ($flush) {
            $this->_em->flush();
        }

        return $id;
    }

    public function count(array $criteria = []): int
    {
        return $this->count($criteria);
    }

    public function persist(object $entity): void
    {
        $this->_em->persist($entity);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function paginate(
        PaginationQueryParams $paginationQueryParams = null,
        ORMFilterInterface $filter = null,
        Criteria $extraCriteria = null
    ): SlidingPagination {
        $query = $this->createQueryBuilder('e');
        $paginationQueryParams ??= new PaginationQueryParams();

        if ($filter) {
            $query->addCriteria($filter->getCriteria());
        }

        if ($extraCriteria) {
            $query->addCriteria($extraCriteria);
        }

        $pagination = $this->paginator->paginate(
            $query,
            $paginationQueryParams->getPage(),
            $paginationQueryParams->getLimit()
        );

        if (! $pagination instanceof SlidingPagination) {
            throw new \Exception(
                "Paginator must be an instance of Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination"
            );
        }

        return $pagination;
    }
}
