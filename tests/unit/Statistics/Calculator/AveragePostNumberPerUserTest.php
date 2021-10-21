<?php

declare(strict_types=1);

namespace Tests\unit\Statistics\Calculator;

use DateTime;
use PHPUnit\Framework\TestCase;
use SocialPost\Dto\SocialPostTo;
use Statistics\Calculator\AveragePostNumberPerUser;
use Statistics\Dto\ParamsTo;
use Statistics\Enum\StatsEnum;

/**
 * Class AveragePostNumberPerUserTest
 *
 * @package Tests\unit\Statistics\Calculator
 */
final class AveragePostNumberPerUserTest extends TestCase
{
    private DateTime $startDate;
    private DateTime $endDate;
    private DateTime $beforeStartDate;
    private DateTime $betweenDate;
    private DateTime $afterEndDate;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->startDate = (new DateTime())
            ->modify('first day of this month')
            ->setTime(0, 0);

        $this->endDate = (new DateTime())
            ->modify('last day of this month')
            ->setTime(23, 59, 59, 999999);

        $this->beforeStartDate = (clone $this->startDate)->modify('-1 microsecond');
        $this->betweenDate = (clone $this->startDate)->modify('+2 day');
        $this->afterEndDate = (clone $this->endDate)->modify('+1 microsecond');
    }

    public function dataProvider(): array
    {
        return [
            'Positive: if there are three posts and two users' => [
                'posts' => [
                    $this->createPost('user_1'),
                    $this->createPost('user_2'),
                    $this->createPost('user_1'),
                ],
                'expected' => 1.5,
            ],
            'Positive: if some posts are not in the date range' => [
                'posts' => [
                    $this->createPost('user_1', $this->beforeStartDate),
                    $this->createPost('user_1', $this->betweenDate),
                    $this->createPost('user_1', $this->startDate),
                    $this->createPost('user_1', $this->endDate),
                    $this->createPost('user_1', $this->afterEndDate),
                ],
                'expected' => 3,
            ],
            'Negative: if there are no posts' => [
                'posts' => [],
                'expected' => 0,
            ],
            'Negative: if the post types are different' => [
                'posts' => [
                    $this->createPost('user_1'),
                    $this->createPost('user_1', $this->betweenDate, 'any_type'),
                    $this->createPost('user_1', $this->betweenDate, 'another_type'),
                    $this->createPost('user_2', $this->betweenDate, null),
                ],
                'expected' => 2,
            ],
            'Negative: if some posts do not have an author' => [
                'posts' => [
                    $this->createPost('user_1'),
                    $this->createPost('user_2'),
                    $this->createPost('user_2'),
                    $this->createPost(),
                    $this->createPost(),
                ],
                'expected' => 1.5,
            ],
            'Negative: if all posts do not have an author' => [
                'posts' => [
                    $this->createPost(),
                    $this->createPost(),
                ],
                'expected' => 0,
            ],
            'Negative: if post author id is empty string' => [
                'posts' => [
                    $this->createPost(''),
                ],
                'expected' => 0,
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @test
     *
     * @param SocialPostTo[] $posts
     * @param float $expected
     */
    public function it_satisfies_expectation(array $posts, float $expected): void
    {
        $it = new AveragePostNumberPerUser();
        $it->setParameters(
            (new ParamsTo())
                ->setStatName(StatsEnum::AVERAGE_POST_NUMBER_PER_USER)
                ->setStartDate($this->startDate)
                ->setEndDate($this->endDate)
        );

        foreach ($posts as $post) {
            $it->accumulateData($post);
        }

        $this->assertEquals($expected, $it->calculate()->getValue());
    }

    /**
     * @test
     */
    public function it__works_without_date_parameters(): void
    {
        $it = new AveragePostNumberPerUser();
        $it->setParameters(
            (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POST_NUMBER_PER_USER)
        );

        $it->accumulateData($this->createPost('user_1'));

        $this->assertEquals(1, $it->calculate()->getValue());
    }

    /**
     * @test
     */
    public function it_returns_correct_statistic_name(): void
    {
        $params = new ParamsTo();
        $params->setStatName(StatsEnum::AVERAGE_POST_NUMBER_PER_USER);

        $it = new AveragePostNumberPerUser();
        $it->setParameters($params);

        $this->assertEquals(StatsEnum::AVERAGE_POST_NUMBER_PER_USER, $it->calculate()->getName());
    }

    private function createPost(
        ?string $authorId = null,
        ?DateTime $date = null,
        ?string $type = 'status'
    ): SocialPostTo
    {
        return (new SocialPostTo())
            ->setId(uniqid())
            ->setAuthorId($authorId)
            ->setType($type)
            ->setDate(clone ($date ?: $this->betweenDate));
    }
}
