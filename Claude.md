# Claude Code Instructions

## General Rules
- Do not delete, reformat, or reorganise any existing code unless explicitly instructed
- Only make the specific changes listed in the prompt
- Do not add comments or change whitespace outside the modified lines
- Do not "improve" or "clean up" code that was not mentioned in the prompt
- Show me what you plan to do before making any changes
- After making changes, run the test suite: `php artisan test`
- Always ask before generating code — do not assume approval

## Project Context
- Laravel Framework version 13, PHP 8.5 application, on Debian 13
- Local and Production environments are on Docker
- Test suite: PHPUnit — run with `php artisan test`
- Always follow existing conventions — reference nearby files for patterns
- Never modify .env
- Never modify any .env files, whose files start with: ".env"

## Podcast Studio Context
- The Podcast Studio follows an assembly line model for podcast production
- Every episode begins as a draft in `podcast_episode_drafts` — drafts are mandatory
- The assembly line: Drafting → Pre-Production → Episode Creation → Recording → Post-Production → Publishing
- Draft statuses use `PodcastEpisodeDraftStatus` enum; production statuses use `PodcastEpisodeStatus` enum — these are deliberately separate
- The "one-way door": once a `podcast_episodes` record is created via Step3Controller, 30+ fields are derived and locked. Everything upstream of this must be right before crossing
- Controllers listing podcast shows use `private const ACTIVE_SHOWS` for consistent filtering and ordering
- One table per migration file
- Markdown in draft/episode content rendered via `Str::markdown()` with `.markdown-content` CSS class

## Git
- Do not run git add, git commit, or git push — that is the developer's responsibility

## Project Documentation
Before making any changes, read these files for context:
- docs/ARCHITECTURE.md
- docs/CONVENTIONS.md

## Interaction

- One step at a time
- Ask if ready for Claude to proceed
- Be honest in all assessments, critiques, and recommendations
- Ask for what you need
- When asking questions, do not use the modal input box
- Do not be "glib". Be accurate, precise, and thorough
- When identifying friction areas or workflow issues, focus on drawing out the problems before proposing solutions
- The goal of the app is not the app itself — the goal is excelling at podcasting. Keep this perspective in all recommendations