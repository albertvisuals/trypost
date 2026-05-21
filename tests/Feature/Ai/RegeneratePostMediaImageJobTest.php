<?php

declare(strict_types=1);

use App\Jobs\Ai\RegeneratePostMediaImage;
use App\Models\Post;
use App\Models\User;
use App\Models\Workspace;

test('job fallback source context uses post content when source_meta is missing', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'user_id' => $user->id,
        'account_id' => $user->account_id,
    ]);

    $post = Post::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'content' => "Headline with typo ECP\nBody line for fallback context.",
        'media' => [],
    ]);

    $job = new RegeneratePostMediaImage(
        workspaceId: $workspace->id,
        postId: $post->id,
        userId: $user->id,
        mediaId: 'media-ai-1',
        regenerationId: '0196f5ca-bf2e-7d15-9a22-5709ab10d6c9',
        instruction: 'Fix typo from ECP to ICP.',
    );

    $method = new ReflectionMethod(RegeneratePostMediaImage::class, 'buildSourceContext');
    $method->setAccessible(true);

    $context = $method->invoke($job, [], $post, $workspace);

    expect(data_get($context, 'title'))->toBe('Headline with typo ECP');
    expect((string) data_get($context, 'body'))->toContain('Body line for fallback context.');
    expect(data_get($context, 'keywords'))->toBeArray()->not->toBeEmpty();
    expect(data_get($context, 'width'))->toBe(1080);
    expect(data_get($context, 'height'))->toBe(1350);
});
