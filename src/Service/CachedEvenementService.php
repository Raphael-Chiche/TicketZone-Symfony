<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class CachedEvenementService
{
    private const CACHE_KEY = 'evenements_actifs';

    public function __construct(
        private readonly EvenementService $inner,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly int $ttl,
    ) {}

    /**
     * @return array
     */
    public function findActifs(): array
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY);

        if ($cacheItem->isHit()) {
            $this->logger->info('[CACHE] Récupération des événements actifs depuis le cache');
            return $cacheItem->get();
        }

        $this->logger->info('[DB] Récupération des événements actifs depuis la base de données');
        $evenements = $this->inner->findActifs();

        $cacheItem->set($evenements);
        $cacheItem->expiresAfter($this->ttl);
        $this->cache->save($cacheItem);

        return $evenements;
    }

    public function invalidateCache(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
        $this->logger->info('Cache des événements actifs invalidé');
    }
}
