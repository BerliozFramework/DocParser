<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2018 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\DocParser\Cache;

trait CacheAwareTrait
{
    /** @var \Berlioz\DocParser\Cache\CacheInterface */
    protected $cache;

    /**
     * Has cache?
     *
     * @return bool
     */
    public function hasCache(): bool
    {
        return !is_null($this->cache);
    }

    /**
     * Get cache.
     *
     * @return \Berlioz\DocParser\Cache\CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Set cache.
     *
     * @param \Berlioz\DocParser\Cache\CacheInterface $cache
     *
     * @return static
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }
}