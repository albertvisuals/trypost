<?php

declare(strict_types=1);

namespace App\Jobs\Ai;

use App\Ai\Agents\PostImageRegenerator;
use App\Enums\Media\Source;
use App\Enums\Media\Type as MediaType;
use App\Events\Ai\PostMediaRegenerated;
use App\Models\Media;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\Workspace;
use App\Services\Ai\RecordAiUsage;
use App\Services\Image\TemplateImageGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RegeneratePostMediaImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $workspaceId,
        public string $postId,
        public string $userId,
        public string $mediaId,
        public string $regenerationId,
        public string $instruction,
    ) {
        $this->onQueue('ai');
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('RegeneratePostMediaImage failed', [
            'post_id' => $this->postId,
            'media_id' => $this->mediaId,
            'regeneration_id' => $this->regenerationId,
            'error' => $exception?->getMessage(),
        ]);

        PostMediaRegenerated::dispatch(
            userId: $this->userId,
            regenerationId: $this->regenerationId,
            postId: $this->postId,
            media: null,
            error: __('posts.ai.image_regenerate.errors.unavailable'),
        );
    }

    public function handle(): void
    {
        $workspace = Workspace::query()->findOrFail($this->workspaceId);
        $post = Post::query()
            ->where('workspace_id', $workspace->id)
            ->with(['postPlatforms.socialAccount', 'workspace'])
            ->findOrFail($this->postId);

        $mediaItems = collect($post->media ?? []);
        $targetIndex = $mediaItems->search(fn ($item) => data_get($item, 'id') === $this->mediaId);
        if ($targetIndex === false) {
            throw new \RuntimeException('Media item no longer exists in post.');
        }

        $target = $mediaItems->get($targetIndex);
        if (data_get($target, 'source') !== Source::Ai->value) {
            throw new \RuntimeException('Only AI media can be regenerated.');
        }

        $sourceMeta = data_get($target, 'source_meta');
        $baseContext = $this->buildSourceContext(
            sourceMeta: is_array($sourceMeta) ? $sourceMeta : [],
            post: $post,
            workspace: $workspace,
        );

        /** @var PostImageRegenerator $agent */
        $agent = app(PostImageRegenerator::class, ['workspace' => $workspace]);

        $response = $agent->prompt(json_encode([
            'instruction' => $this->instruction,
            'title' => $baseContext['title'],
            'body' => $baseContext['body'],
            'keywords' => $baseContext['keywords'],
            'language' => $baseContext['language'],
        ], JSON_THROW_ON_ERROR));

        RecordAiUsage::recordText(
            workspace: $workspace,
            promptTokens: $response->usage?->promptTokens ?? 0,
            completionTokens: $response->usage?->completionTokens ?? 0,
            provider: (string) config('ai.default'),
            model: (string) config('ai.default_text_model'),
            userId: $this->userId,
            postId: $post->id,
            metadata: ['agent' => 'post_image_regenerator'],
        );

        $structured = $response->structured ?? [];

        $title = trim((string) data_get($structured, 'title', $baseContext['title']));
        $body = trim((string) data_get($structured, 'body', $baseContext['body']));
        $keywords = collect(data_get($structured, 'keywords', $baseContext['keywords']))
            ->filter(fn ($keyword) => is_string($keyword) && trim($keyword) !== '')
            ->map(fn (string $keyword) => trim($keyword))
            ->values()
            ->all();

        if ($keywords === []) {
            $keywords = $baseContext['keywords'];
        }

        $socialAccount = $this->resolveSocialAccount($post, $workspace);
        if (! $socialAccount) {
            throw new \RuntimeException('No social account available for image footer rendering.');
        }

        $generator = app(TemplateImageGenerator::class);
        $rendered = $generator->render(
            workspace: $workspace,
            socialAccount: $socialAccount,
            title: $title,
            body: $body,
            imageKeywords: $keywords,
            width: $baseContext['width'],
            height: $baseContext['height'],
        );

        if (! $rendered) {
            throw new \RuntimeException('Image generator failed to produce media.');
        }

        $renderedPath = (string) data_get($rendered, 'path');

        try {
            $newMediaItem = DB::transaction(function () use ($post, $rendered, $target, $workspace) {
                $newMediaItem = $this->buildAiMediaItem($workspace, $rendered);

                $fresh = Post::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
                $items = collect($fresh->media ?? []);

                $currentIndex = $items->search(fn ($item) => data_get($item, 'id') === $this->mediaId);
                if ($currentIndex === false) {
                    throw new \RuntimeException('Media item changed before regeneration completed.');
                }

                $items->put($currentIndex, $newMediaItem);

                $fresh->update(['media' => $items->values()->all()]);

                $oldMediaId = data_get($target, 'id');
                Media::query()->where('id', $oldMediaId)->first()?->delete();

                return $newMediaItem;
            });
        } catch (Throwable $exception) {
            $this->discardRenderedFile($renderedPath);

            throw $exception;
        }

        PostMediaRegenerated::dispatch(
            userId: $this->userId,
            regenerationId: $this->regenerationId,
            postId: $post->id,
            media: $newMediaItem,
            error: null,
        );
    }

    private function discardRenderedFile(string $path): void
    {
        if ($path !== '' && Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    /**
     * @param  array<string, mixed>  $sourceMeta
     * @return array{
     *   title: string,
     *   body: string,
     *   keywords: array<int, string>,
     *   language: string,
     *   width: int,
     *   height: int
     * }
     */
    private function buildSourceContext(array $sourceMeta, Post $post, Workspace $workspace): array
    {
        $title = trim((string) data_get($sourceMeta, 'title', ''));
        $body = trim((string) data_get($sourceMeta, 'body', ''));
        $keywords = collect(data_get($sourceMeta, 'keywords', []))
            ->filter(fn ($keyword) => is_string($keyword) && trim($keyword) !== '')
            ->map(fn (string $keyword) => trim($keyword))
            ->values()
            ->all();

        // Fallback path for older AI media without source metadata.
        if ($title === '' && $body === '') {
            $derived = trim((string) $post->content);
            if ($derived !== '') {
                $lines = preg_split('/\R+/', $derived) ?: [];
                $title = trim((string) data_get($lines, 0, ''));
                $body = trim(collect($lines)->slice(1)->implode(' '));
            }
        }

        if ($title === '') {
            $title = __('posts.ai.image_regenerate.fallback_title');
        }

        if ($keywords === []) {
            $keywords = collect(preg_split('/\s+/', "{$title} {$body}") ?: [])
                ->filter(fn ($word) => is_string($word) && mb_strlen(trim($word)) >= 4)
                ->map(fn (string $word) => trim($word, ".,!?;:\"'()[]{}"))
                ->filter()
                ->take(8)
                ->values()
                ->all();
        }

        if ($keywords === []) {
            $keywords = ['social media', 'marketing'];
        }

        return [
            'title' => $title,
            'body' => $body,
            'keywords' => $keywords,
            'language' => (string) data_get($sourceMeta, 'language', $workspace->content_language),
            'width' => (int) data_get($sourceMeta, 'width', TemplateImageGenerator::DEFAULT_WIDTH),
            'height' => (int) data_get($sourceMeta, 'height', TemplateImageGenerator::DEFAULT_HEIGHT),
        ];
    }

    private function resolveSocialAccount(Post $post, Workspace $workspace): ?SocialAccount
    {
        $enabledAccount = $post->postPlatforms
            ->first(fn ($platform) => $platform->enabled && $platform->socialAccount);

        if ($enabledAccount?->socialAccount) {
            return $enabledAccount->socialAccount;
        }

        $anyAccount = $post->postPlatforms
            ->first(fn ($platform) => $platform->socialAccount);

        return $anyAccount?->socialAccount
            ?? $workspace->socialAccounts()->first();
    }

    /**
     * @param  array{path: string, source_meta: array<string, mixed>}  $rendered
     * @return array<string, mixed>
     */
    private function buildAiMediaItem(Workspace $workspace, array $rendered): array
    {
        $media = $workspace->media()->create([
            'collection' => 'ai-generated',
            'type' => MediaType::Image,
            'path' => $rendered['path'],
            'original_filename' => basename($rendered['path']),
            'mime_type' => 'image/webp',
            'size' => Storage::size($rendered['path']),
            'order' => 0,
        ]);

        return [
            'id' => $media->id,
            'path' => $media->path,
            'url' => $media->url,
            'type' => 'image',
            'mime_type' => 'image/webp',
            'source' => Source::Ai->value,
            'source_meta' => $rendered['source_meta'],
        ];
    }
}
