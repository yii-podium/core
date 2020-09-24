<?php

declare(strict_types=1);

namespace Podium\Tests\Unit\Post;

use Exception;
use Podium\Api\Interfaces\MemberRepositoryInterface;
use Podium\Api\Interfaces\PostRepositoryInterface;
use Podium\Api\Interfaces\ThumbRepositoryInterface;
use Podium\Api\Services\Post\PostLiker;
use Podium\Tests\AppTestCase;
use Yii;
use yii\db\Connection;
use yii\db\Transaction;

class PostLikerTest extends AppTestCase
{
    private PostLiker $service;

    protected function setUp(): void
    {
        $this->service = new PostLiker();
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction')->willReturn($this->createMock(Transaction::class));
        Yii::$app->set('db', $connection);
    }

    public function testBeforeThumbUpShouldReturnTrue(): void
    {
        self::assertTrue($this->service->beforeThumbUp());
    }

    public function testThumbUpShouldReturnErrorWhenUpErrored(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isUp')->willReturn(false);
        $thumb->method('up')->willReturn(false);
        $thumb->method('getErrors')->willReturn([1]);
        $result = $this->service->thumbUp(
            $thumb,
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame([1], $result->getErrors());
    }

    public function testThumbUpShouldReturnErrorWhenIsUpIsTrue(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isUp')->willReturn(true);
        $result = $this->service->thumbUp(
            $thumb,
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame('post.already.liked', $result->getErrors()['api']);
    }

    public function testThumbUpShouldReturnSuccessWhenUpIsDoneWithAlreadyRated(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isUp')->willReturn(false);
        $thumb->method('up')->willReturn(true);
        $thumb->expects(self::never())->method('prepare');
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('updateCounters')->with(1, -1)->willReturn(true);
        $result = $this->service->thumbUp($thumb, $post, $this->createMock(MemberRepositoryInterface::class));

        self::assertTrue($result->getResult());
    }

    public function testThumbUpShouldReturnSuccessWhenUpIsDoneWithNotPreviouslyRated(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(false);
        $thumb->method('isUp')->willReturn(false);
        $thumb->method('up')->willReturn(true);
        $thumb->expects(self::once())->method('prepare');
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('updateCounters')->with(1, 0)->willReturn(true);
        $result = $this->service->thumbUp($thumb, $post, $this->createMock(MemberRepositoryInterface::class));

        self::assertTrue($result->getResult());
    }

    public function testThumbUpShouldReturnErrorWhenUpThrowsException(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isUp')->willReturn(false);
        $thumb->method('up')->willThrowException(new Exception('exc'));
        $result = $this->service->thumbUp(
            $thumb,
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame('exc', $result->getErrors()['exception']->getMessage());
    }

    public function testThumbUpShouldReturnErrorWhenUpdateCountersErrored(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isUp')->willReturn(false);
        $thumb->method('up')->willReturn(true);
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('updateCounters')->willReturn(false);
        $result = $this->service->thumbUp($thumb, $post, $this->createMock(MemberRepositoryInterface::class));

        self::assertFalse($result->getResult());
        self::assertSame('Error while updating post counters!', $result->getErrors()['exception']->getMessage());
    }

    public function testBeforeThumbDownShouldReturnTrue(): void
    {
        self::assertTrue($this->service->beforeThumbDown());
    }

    public function testThumbDownShouldReturnErrorWhenDownErrored(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isDown')->willReturn(false);
        $thumb->method('down')->willReturn(false);
        $thumb->method('getErrors')->willReturn([1]);
        $result = $this->service->thumbDown(
            $thumb,
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame([1], $result->getErrors());
    }

    public function testThumbDownShouldReturnErrorWhenIsDownIsTrue(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isDown')->willReturn(true);
        $result = $this->service->thumbDown(
            $thumb,
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame('post.already.disliked', $result->getErrors()['api']);
    }

    public function testThumbDownShouldReturnSuccessWhenDownIsDoneWithAlreadyRated(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isDown')->willReturn(false);
        $thumb->method('down')->willReturn(true);
        $thumb->expects(self::never())->method('prepare');
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('updateCounters')->with(-1, 1)->willReturn(true);
        $result = $this->service->thumbDown($thumb, $post, $this->createMock(MemberRepositoryInterface::class));

        self::assertTrue($result->getResult());
    }

    public function testThumbDownShouldReturnSuccessWhenDownIsDoneWithNotPreviouslyRated(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(false);
        $thumb->method('isDown')->willReturn(false);
        $thumb->method('down')->willReturn(true);
        $thumb->expects(self::once())->method('prepare');
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('updateCounters')->with(0, 1)->willReturn(true);
        $result = $this->service->thumbDown($thumb, $post, $this->createMock(MemberRepositoryInterface::class));

        self::assertTrue($result->getResult());
    }

    public function testThumbDownShouldReturnErrorWhenDownThrowsException(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isDown')->willReturn(false);
        $thumb->method('down')->willThrowException(new Exception('exc'));
        $result = $this->service->thumbDown(
            $thumb,
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame('exc', $result->getErrors()['exception']->getMessage());
    }

    public function testThumbDownShouldReturnErrorWhenUpdateCountersErrored(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('isDown')->willReturn(false);
        $thumb->method('down')->willReturn(true);
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('updateCounters')->willReturn(false);
        $result = $this->service->thumbDown($thumb, $post, $this->createMock(MemberRepositoryInterface::class));

        self::assertFalse($result->getResult());
        self::assertSame('Error while updating post counters!', $result->getErrors()['exception']->getMessage());
    }

    public function testBeforeThumbResetShouldReturnTrue(): void
    {
        self::assertTrue($this->service->beforeThumbReset());
    }

    public function testThumbResetShouldReturnErrorWhenResetErrored(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('reset')->willReturn(false);
        $result = $this->service->thumbReset(
            $thumb,
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
    }

    public function testThumbResetShouldReturnErrorWhenPostIsNotRated(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(false);
        $result = $this->service->thumbReset(
            $thumb,
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame('post.not.rated', $result->getErrors()['api']);
    }

    public function testThumbResetShouldReturnSuccessWhenResetIsDoneWithPostPreviouslyUp(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('reset')->willReturn(true);
        $thumb->method('isUp')->willReturn(true);
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('updateCounters')->with(-1, 0)->willReturn(true);
        $result = $this->service->thumbReset($thumb, $post, $this->createMock(MemberRepositoryInterface::class));

        self::assertTrue($result->getResult());
    }

    public function testThumbResetShouldReturnSuccessWhenResetIsDoneWithPostPreviouslyDown(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('reset')->willReturn(true);
        $thumb->method('isUp')->willReturn(false);
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('updateCounters')->with(0, -1)->willReturn(true);
        $result = $this->service->thumbReset($thumb, $post, $this->createMock(MemberRepositoryInterface::class));

        self::assertTrue($result->getResult());
    }

    public function testThumbResetShouldReturnErrorWhenResetThrowsException(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('reset')->willThrowException(new Exception('exc'));
        $result = $this->service->thumbReset(
            $thumb,
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame('exc', $result->getErrors()['exception']->getMessage());
    }

    public function testThumbResetShouldReturnErrorWhenUpdateCountersErrored(): void
    {
        $thumb = $this->createMock(ThumbRepositoryInterface::class);
        $thumb->method('fetchOne')->willReturn(true);
        $thumb->method('reset')->willReturn(true);
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('updateCounters')->willReturn(false);
        $result = $this->service->thumbReset($thumb, $post, $this->createMock(MemberRepositoryInterface::class));

        self::assertFalse($result->getResult());
        self::assertSame('Error while updating post counters!', $result->getErrors()['exception']->getMessage());
    }
}
