<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeed\Model\Finder;

use Hryvinskyi\PageSpeedApi\Api\Finder\FinderInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\RawInterfaceFactory;

abstract class AbstractRegexp implements FinderInterface
{
    public array $needles = [];
    private RawInterfaceFactory $rawFactory;

    /**
     * @param RawInterfaceFactory $rawFactory
     */
    public function __construct(RawInterfaceFactory $rawFactory)
    {
        $this->rawFactory = $rawFactory;
    }

    /**
     * @param string $haystack
     * @param null|int $start
     * @param null|int $end
     *
     * @return RawInterface[]
     * @throws \Exception
     */
    public function findAll(string $haystack, int $start = null, int $end = null): array
    {
        $result = [[]];
        foreach ($this->needles as $needle) {
            $result[] = $this->findByNeedle($needle, $haystack, $start, $end);
        }

        return array_values(array_merge(...$result));
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @param null|int $start
     * @param null|int $end
     *
     * @return RawInterface[]
     * @throws \Exception
     */
    public function findByNeedle(
        string $needle,
        string $haystack,
        int $start = null,
        int $end = null
    ): array {
        $findResult = preg_match_all(
            $needle,
            $haystack,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        if ($findResult === false) {
            throw new \Exception('preg_match_all error in ' . self::class . ', error code: ' . preg_last_error());
        }

        $result = [];
        foreach ($matches as $match) {
            [$match, $pos] = $match[0];

            if ($start !== null && $start > $pos) {
                continue;
            }
            if ($end !== null && $end < $pos) {
                continue;
            }

            $result[] = $this->processResult($match, $pos);
        }

        return $result;
    }

    /**
     * @param string $source
     * @param int $pos
     *
     * @return RawInterface
     * @throws \Exception
     */
    public function processResult(string $source, int $pos): RawInterface
    {
        $end = $pos + strlen($source) - 1;
        /** @var RawInterface $result */
        $result = $this->rawFactory->create();
        $result->setContent($source);
        $result->setStart($pos);
        $result->setEnd($end);

        return $result;
    }
}
