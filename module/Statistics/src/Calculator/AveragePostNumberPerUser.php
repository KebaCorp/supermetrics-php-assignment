<?php

declare(strict_types = 1);

namespace Statistics\Calculator;

use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

/**
 * Class AveragePostNumberPerUser
 *
 * @package Statistics\Calculator
 */
class AveragePostNumberPerUser extends AbstractCalculator
{
    protected const UNITS = 'posts';
    private int $postCount = 0;
    private array $postCountPerUser = [];

    /**
     * @param SocialPostTo $postTo
     */
    protected function doAccumulate(SocialPostTo $postTo): void
    {
        $authorId = $postTo->getAuthorId();

        if (! $authorId) {
            return;
        }

        ++$this->postCount;

        if (! in_array($authorId, $this->postCountPerUser)) {
            $this->postCountPerUser[] = $authorId;
        }
    }

    /**
     * @return StatisticsTo
     */
    protected function doCalculate(): StatisticsTo
    {
        $userCount = count($this->postCountPerUser);

        $value = $userCount > 0
            ? $this->postCount / $userCount
            : 0;

        return (new StatisticsTo())->setValue(round($value,2));
    }
}
