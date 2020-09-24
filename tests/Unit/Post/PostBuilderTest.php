<?php

declare(strict_types=1);

namespace Podium\Tests\Unit\Post;

use Exception;
use Podium\Api\Interfaces\ForumRepositoryInterface;
use Podium\Api\Interfaces\MemberRepositoryInterface;
use Podium\Api\Interfaces\PostRepositoryInterface;
use Podium\Api\Interfaces\RepositoryInterface;
use Podium\Api\Interfaces\ThreadRepositoryInterface;
use Podium\Api\Services\Post\PostBuilder;
use Podium\Tests\AppTestCase;
use Yii;
use yii\db\Connection;
use yii\db\Transaction;

class PostBuilderTest extends AppTestCase
{
    private PostBuilder $service;

    protected function setUp(): void
    {
        $this->service = new PostBuilder();
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction')->willReturn($this->createMock(Transaction::class));
        Yii::$app->set('db', $connection);
    }

    public function testBeforeCreateShouldReturnTrue(): void
    {
        self::assertTrue($this->service->beforeCreate());
    }

    public function testCreateShouldReturnErrorWhenRepositoryIsWrong(): void
    {
        $result = $this->service->create(
            $this->createMock(RepositoryInterface::class),
            $this->createMock(MemberRepositoryInterface::class),
            $this->createMock(RepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
    }

    public function testCreateShouldReturnErrorWhenCreatingErrored(): void
    {
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('create')->willReturn(false);
        $post->method('getErrors')->willReturn([1]);
        $result = $this->service->create(
            $post,
            $this->createMock(MemberRepositoryInterface::class),
            $this->createMock(ThreadRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame([1], $result->getErrors());
    }

    public function testCreateShouldReturnSuccessWhenCreatingIsDone(): void
    {
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('create')->willReturn(true);
        $thread = $this->createMock(ThreadRepositoryInterface::class);
        $thread->method('updateCounters')->willReturn(true);
        $forum = $this->createMock(ForumRepositoryInterface::class);
        $forum->method('updateCounters')->willReturn(true);
        $thread->method('getParent')->willReturn($forum);
        $result = $this->service->create($post, $this->createMock(MemberRepositoryInterface::class), $thread);

        self::assertTrue($result->getResult());
    }

    public function testCreateShouldReturnErrorWhenCreatingThrowsException(): void
    {
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('create')->willThrowException(new Exception('exc'));
        $result = $this->service->create(
            $post,
            $this->createMock(MemberRepositoryInterface::class),
            $this->createMock(ThreadRepositoryInterface::class)
        );

        self::assertFalse($result->getResult());
        self::assertSame('exc', $result->getErrors()['exception']->getMessage());
    }

    public function testCreateShouldReturnErrorWhenUpdatingThreadCountersErrored(): void
    {
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('create')->willReturn(true);
        $thread = $this->createMock(ThreadRepositoryInterface::class);
        $thread->method('updateCounters')->willReturn(false);
        $result = $this->service->create($post, $this->createMock(MemberRepositoryInterface::class), $thread);

        self::assertFalse($result->getResult());
        self::assertSame('Error while updating thread counters!', $result->getErrors()['exception']->getMessage());
    }

    public function testCreateShouldReturnErrorWhenUpdatingForumCountersErrored(): void
    {
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('create')->willReturn(true);
        $thread = $this->createMock(ThreadRepositoryInterface::class);
        $thread->method('updateCounters')->willReturn(true);
        $forum = $this->createMock(ForumRepositoryInterface::class);
        $forum->method('updateCounters')->willReturn(false);
        $thread->method('getParent')->willReturn($forum);
        $result = $this->service->create($post, $this->createMock(MemberRepositoryInterface::class), $thread);

        self::assertFalse($result->getResult());
        self::assertSame('Error while updating forum counters!', $result->getErrors()['exception']->getMessage());
    }

    public function testBeforeEditShouldReturnTrue(): void
    {
        self::assertTrue($this->service->beforeEdit());
    }

    public function testEditShouldReturnErrorWhenRepositoryIsWrong(): void
    {
        $result = $this->service->edit($this->createMock(RepositoryInterface::class));

        self::assertFalse($result->getResult());
    }

    public function testEditShouldReturnErrorWhenEditingErrored(): void
    {
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('edit')->willReturn(false);
        $post->method('getErrors')->willReturn([1]);
        $result = $this->service->edit($post);

        self::assertFalse($result->getResult());
        self::assertSame([1], $result->getErrors());
    }

    public function testEditShouldReturnSuccessWhenEditingIsDone(): void
    {
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('edit')->willReturn(true);
        $result = $this->service->edit($post);

        self::assertTrue($result->getResult());
    }

    public function testEditShouldReturnErrorWhenEditingThrowsException(): void
    {
        $post = $this->createMock(PostRepositoryInterface::class);
        $post->method('edit')->willThrowException(new Exception('exc'));
        $result = $this->service->edit($post);

        self::assertFalse($result->getResult());
        self::assertSame('exc', $result->getErrors()['exception']->getMessage());
    }
}
