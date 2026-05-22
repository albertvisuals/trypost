---
subject: "Changelog: TryPost v1.0.0 — Facebook fixes, AI brand colors, and weekly updates"
---

# Changelog: TryPost v1.0.0 — Facebook fixes, AI brand colors, and weekly updates

By Paulo Castellano • Release v1.0.0

Hello! v1.0.0 marks our first tagged release. We've been shipping for a while — this is just the moment we started cutting weekly tags. Going forward you'll get one of these every Friday. Here's what's new this week.

## Reliable Facebook posting

We fixed two issues affecting Facebook this week. Multi-image posts were sometimes publishing as text-only (the photos getting dropped silently), and Page Stories were accepting image uploads that Facebook actually rejects. Both now work correctly: multi-image posts include all attached photos, and Stories accept only video, which is the format Facebook supports there.

## AI image generation with your brand colors

When you regenerate an AI-generated post image, TryPost now uses your brand palette to keep the look consistent across your posts. Previously each regenerated image picked colors from scratch, which made the gallery feel disconnected. If you've set up brand colors in Settings, regenerations respect them automatically.

## Self-hosting improvements

For anyone running TryPost on their own infrastructure: we added a DigitalOcean Spaces disk driver (an alternative to S3 for media storage) and a self-hosted registration gate that closes signups by default and lets the first admin seed itself. Both are documented in the env reference.

## Fixes

- Deleting a team member no longer affects the shared account.
- Better error messages when a social platform is temporarily down vs. when your connected account's token has expired.

Cheers,
Paulo Castellano from TryPost.it
