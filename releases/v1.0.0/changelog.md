## What's Changed
* fix: correct Google OAuth config key in token refresh by @niravsutariya in https://github.com/trypostit/trypost/pull/1
* Fix deprecated timezone validation on registration by @paulocastellano in https://github.com/trypostit/trypost/pull/6
* Remove docs directory by @paulocastellano in https://github.com/trypostit/trypost/pull/7
* Fix nullable content across all social publishers by @paulocastellano in https://github.com/trypostit/trypost/pull/5
* Move helpers to app/Helpers/upload.php by @paulocastellano in https://github.com/trypostit/trypost/pull/8
* Upgrade to Laravel 13 and Inertia v3 by @paulocastellano in https://github.com/trypostit/trypost/pull/9
* refactor: single-domain routing, actions, policies, Google login, PostHog, full test coverage by @paulocastellano in https://github.com/trypostit/trypost/pull/10
* feat: add YouTube Analytics by @paulocastellano in https://github.com/trypostit/trypost/pull/13
* feat: Google Auth toggle — TRYPOST_GOOGLE_AUTH_ENABLED by @paulocastellano in https://github.com/trypostit/trypost/pull/11
* feat: complete create + publish post flow via MCP and REST API by @paulocastellano in https://github.com/trypostit/trypost/pull/14
* feat: add multipart media upload endpoint and split URL flow by @paulocastellano in https://github.com/trypostit/trypost/pull/15
* feat: capture signup UTMs/IP and add GitHub OAuth login by @paulocastellano in https://github.com/trypostit/trypost/pull/16
* fix: let authenticated users connect Google/GitHub from Settings by @paulocastellano in https://github.com/trypostit/trypost/pull/17
* feat: GTM dataLayer context + checkout/purchase events by @paulocastellano in https://github.com/trypostit/trypost/pull/18
* feat: design system polish (in-page headers, indies tabs, sidebar collapse removal) by @paulocastellano in https://github.com/trypostit/trypost/pull/19
* feat: end-to-end PostHog tracking with reactive group metrics by @paulocastellano in https://github.com/trypostit/trypost/pull/20
* fix: cast cached post count to int (Redis serializer crash) by @paulocastellano in https://github.com/trypostit/trypost/pull/21
* feat: AI image generation overhaul + post creation flow + realtime broadcast by @paulocastellano in https://github.com/trypostit/trypost/pull/22
* fix: post show layout matches editor and lightbox supports navigation by @paulocastellano in https://github.com/trypostit/trypost/pull/23
* feat(tiktok): photo carousel support + Content Sharing API UX compliance by @paulocastellano in https://github.com/trypostit/trypost/pull/25
* fix(posts): block scheduling when content exceeds platform char limit by @paulocastellano in https://github.com/trypostit/trypost/pull/26
* chore: remove unused PlatformRules registry and rule classes by @paulocastellano in https://github.com/trypostit/trypost/pull/27
* feat(tracking): push Google Ads conversion data to dataLayer on purchase by @paulocastellano in https://github.com/trypostit/trypost/pull/28
* fix(social): unify token-expired handling across all publishers by @paulocastellano in https://github.com/trypostit/trypost/pull/29
* fix(social): proactive token refresh actually refreshes (not just verifies) by @paulocastellano in https://github.com/trypostit/trypost/pull/30
* fix(x): drop chunked upload chunk size from 5MB to 1MB by @paulocastellano in https://github.com/trypostit/trypost/pull/31
* fix(facebook): use upload_url + file_url for reel transfer phase by @paulocastellano in https://github.com/trypostit/trypost/pull/32
* feat(posts): multi-select label filter on the posts list by @paulocastellano in https://github.com/trypostit/trypost/pull/34
* fix(linkedin,pinterest): split CSV/space-joined OAuth scopes before saving by @paulocastellano in https://github.com/trypostit/trypost/pull/35
* fix(linkedin-page): persist OAuth scopes through the page-picker flow by @paulocastellano in https://github.com/trypostit/trypost/pull/36
* feat(signup): no-card 7-day trial on Starter plan by @paulocastellano in https://github.com/trypostit/trypost/pull/38
* fix(pinterest): restore board picker + require board_id in validation by @paulocastellano in https://github.com/trypostit/trypost/pull/39
* fix(posts): drop redundant 'publishing' toast on publish by @paulocastellano in https://github.com/trypostit/trypost/pull/40
* feat(mcp): media upload via signed-URL flow by @paulocastellano in https://github.com/trypostit/trypost/pull/42
* feat(docker): single-command Docker stack for local development by @andrefrd in https://github.com/trypostit/trypost/pull/24
* fix(assets): always chunk uploads in GalleryBrowser by @paulocastellano in https://github.com/trypostit/trypost/pull/45
* fix(social): distinguish platform-down from token-expired by @paulocastellano in https://github.com/trypostit/trypost/pull/49
* feat(auth): self-hosted registration gate + admin seeder by @paulocastellano in https://github.com/trypostit/trypost/pull/51
* fix(profile): member delete must not destroy the shared account by @paulocastellano in https://github.com/trypostit/trypost/pull/52
* Fix: JSON columns with default('[]') fail on MySQL 8 by @sebastian-works in https://github.com/trypostit/trypost/pull/47
* feat(storage): DigitalOcean Spaces disk + env documentation by @paulocastellano in https://github.com/trypostit/trypost/pull/53
* fix(facebook): empty-message rejection + state consistency + no re-publish on terminal by @paulocastellano in https://github.com/trypostit/trypost/pull/41
* feat(posthog): keep social_accounts_count and posts_count fresh on account group by @paulocastellano in https://github.com/trypostit/trypost/pull/43
* chore(cursor): add Cursor project rules by @paulocastellano in https://github.com/trypostit/trypost/pull/54
* fix(billing): require card-backed trial again by @paulocastellano in https://github.com/trypostit/trypost/pull/56
* feat: regenerate AI post images with brand palette by @paulocastellano in https://github.com/trypostit/trypost/pull/57
* fix(security): resolve npm audit vulnerabilities by @paulocastellano in https://github.com/trypostit/trypost/pull/58
* fix(facebook): multi-image posts publish as text-only by @paulocastellano in https://github.com/trypostit/trypost/pull/59
* fix(facebook): restrict Page Stories to video-only by @paulocastellano in https://github.com/trypostit/trypost/pull/60
* chore(release): add /release slash command for weekly ritual by @paulocastellano in https://github.com/trypostit/trypost/pull/62

## New Contributors
* @niravsutariya made their first contribution in https://github.com/trypostit/trypost/pull/1
* @paulocastellano made their first contribution in https://github.com/trypostit/trypost/pull/6
* @andrefrd made their first contribution in https://github.com/trypostit/trypost/pull/24
* @sebastian-works made their first contribution in https://github.com/trypostit/trypost/pull/47

**Full Changelog**: https://github.com/trypostit/trypost/commits/v1.0.0
