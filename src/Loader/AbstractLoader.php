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

namespace Berlioz\DocParser\Loader;

use Berlioz\DocParser\Exception\LoaderException;

abstract class AbstractLoader implements LoaderInterface
{
    const FILTER_ALL = 0;
    const FILTER_INCLUDE = 1;
    const FILTER_EXCLUDE = 2;
    /** @var array Options */
    protected $options = [];

    /**
     * AbstractLoader constructor.
     *
     * @param array $options Options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Get option.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption(string $name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }

    /**
     * Test filters includes/excludes.
     *
     * @param string $path Path to test
     * @param int    $type Constant of class (FILTER_ALL, FILTER_INCLUDE, FILTER_EXCLUDE)
     *
     * @return bool
     * @throws \Berlioz\DocParser\Exception\LoaderException
     */
    public function testFilter(string $path, int $type = self::FILTER_ALL): bool
    {
        switch ($type) {
            case self::FILTER_ALL:
                if ($this->testFilter($path, self::FILTER_INCLUDE)) {
                    if ($this->testFilter($path, self::FILTER_EXCLUDE)) {
                        return true;
                    }
                }
                break;
            case self::FILTER_INCLUDE:
                if (!is_array($this->getOption('filter.includes'))) {
                    return true;
                } else {
                    foreach ($this->getOption('filter.includes') as $included) {
                        if (($regexResult = @preg_match(sprintf('#%s#i', str_replace('#', '\\#', $included)), $path)) == 1) {
                            return true;
                        }

                        if ($regexResult === false) {
                            throw new LoaderException(sprintf('Invalid filter format: "%s", must be a valid regex', $included));
                        }
                    }
                }
                break;
            case self::FILTER_EXCLUDE:
                if (is_array($this->getOption('filter.excludes'))) {
                    foreach ($this->getOption('filter.excludes') as $excluded) {
                        if (($regexResult = @preg_match(sprintf('#%s#i', str_replace('#', '\\#', $excluded)), $path)) == 1) {
                            return false;
                        }

                        if ($regexResult === false) {
                            throw new LoaderException(sprintf('Invalid filter format: "%s", must be a valid regex', $excluded));
                        }
                    }
                }

                return true;
        }

        return false;
    }
}