<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { echo } from '@laravel/echo-vue';
import { trans } from 'laravel-vue-i18n';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { regenerateMedia as regeneratePostAiMedia } from '@/routes/app/posts/ai';

interface MediaItem {
    id: string;
    path: string;
    url: string;
    type?: string;
    mime_type?: string;
    original_filename?: string;
    size?: number;
    source?: 'ai' | 'unsplash' | 'giphy';
    source_meta?: Record<string, unknown>;
    meta?: { width?: number; height?: number; duration?: number };
}

interface RegenerationPayload {
    media: MediaItem;
    targetMediaId: string;
}

const props = defineProps<{
    postId: string;
    mediaItem: MediaItem | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const emit = defineEmits<{
    (e: 'regenerated', payload: RegenerationPayload): void;
}>();

const instruction = ref('');
const errorMessage = ref<string | null>(null);
const status = ref<'idle' | 'starting' | 'processing'>('idle');

let subscribedChannel: string | null = null;
let regenerationTimeout: ReturnType<typeof setTimeout> | null = null;

const REGENERATION_TIMEOUT_MS = 180_000;

const httpRegenerate = useHttp<{ instruction: string }>({
    instruction: '',
});

const isBusy = computed(() => status.value !== 'idle');

const unsubscribe = () => {
    if (subscribedChannel) {
        echo().leave(`private-${subscribedChannel}`);
        subscribedChannel = null;
    }
};

const clearRegenerationTimeout = () => {
    if (regenerationTimeout !== null) {
        clearTimeout(regenerationTimeout);
        regenerationTimeout = null;
    }
};

const resetState = () => {
    instruction.value = '';
    errorMessage.value = null;
    status.value = 'idle';
    clearRegenerationTimeout();
    unsubscribe();
};

const blockDismissWhileBusy = (event: Event) => {
    if (isBusy.value) {
        event.preventDefault();
    }
};

const subscribe = (channel: string) => {
    subscribedChannel = channel;
    status.value = 'processing';

    clearRegenerationTimeout();
    regenerationTimeout = setTimeout(() => {
        errorMessage.value = trans('posts.ai.image_regenerate.errors.timeout');
        status.value = 'idle';
        unsubscribe();
    }, REGENERATION_TIMEOUT_MS);

    echo()
        .private(channel)
        .listen('.ai.media.regenerated', (event: {
            media: MediaItem | null;
            error?: string | null;
        }) => {
            clearRegenerationTimeout();

            if (event.error || !event.media || !props.mediaItem) {
                errorMessage.value = event.error ?? trans('posts.ai.image_regenerate.errors.unavailable');
                status.value = 'idle';
                unsubscribe();
                return;
            }

            toast.success(trans('posts.ai.image_regenerate.success'));

            emit('regenerated', {
                media: event.media,
                targetMediaId: props.mediaItem.id,
            });

            resetState();
            open.value = false;
        });
};

const submit = async () => {
    if (!props.mediaItem) return;
    if (!instruction.value.trim()) {
        errorMessage.value = trans('posts.ai.image_regenerate.errors.required');
        return;
    }

    errorMessage.value = null;
    status.value = 'starting';
    httpRegenerate.instruction = instruction.value.trim();

    try {
        const response = await httpRegenerate.post(
            regeneratePostAiMedia.url({ post: props.postId, mediaId: props.mediaItem.id }),
        ) as { channel?: string };
        const channel = String(response.channel ?? '');
        if (!channel) {
            throw new Error('Missing channel in regeneration response.');
        }
        subscribe(channel);
    } catch (error: unknown) {
        status.value = 'idle';
        const responseMessage = (error as { response?: { data?: { message?: string } } })?.response?.data?.message;
        errorMessage.value = responseMessage ?? trans('posts.ai.image_regenerate.errors.start_failed');
    }
};

watch(open, (isOpen) => {
    if (!isOpen) {
        if (status.value === 'processing') {
            open.value = true;
            return;
        }
        resetState();
    }
});

onBeforeUnmount(() => {
    unsubscribe();
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="sm:max-w-xl"
            :show-close-button="!isBusy"
            @pointer-down-outside="blockDismissWhileBusy"
            @escape-key-down="blockDismissWhileBusy"
        >
            <DialogHeader>
                <DialogTitle>{{ $t('posts.ai.image_regenerate.title') }}</DialogTitle>
                <DialogDescription>{{ $t('posts.ai.image_regenerate.description') }}</DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="ai-image-instruction">{{ $t('posts.ai.image_regenerate.instruction_label') }}</Label>
                    <Textarea
                        id="ai-image-instruction"
                        v-model="instruction"
                        :disabled="isBusy"
                        :placeholder="$t('posts.ai.image_regenerate.instruction_placeholder')"
                        rows="4"
                    />
                </div>

                <p v-if="status === 'processing'" class="text-sm text-foreground/70">
                    {{ $t('posts.ai.image_regenerate.processing') }}
                </p>
                <p v-if="errorMessage" class="text-sm font-semibold text-rose-700">{{ errorMessage }}</p>
            </div>

            <DialogFooter>
                <Button
                    :loading="isBusy"
                    :disabled="!instruction.trim()"
                    @click="submit"
                >
                    {{ $t('posts.ai.image_regenerate.submit') }}
                </Button>
                <Button variant="outline" :disabled="isBusy" @click="open = false">
                    {{ $t('posts.ai.image_regenerate.cancel') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
