# Claude Code Instructions

## General Rules
- Do not delete, reformat, or reorganise any existing code unless explicitly instructed
- Only make the specific changes listed in the prompt
- Do not add comments or change whitespace outside the modified lines
- Do not "improve" or "clean up" code that was not mentioned in the prompt
- Show me what you plan to do before making any changes
- After making changes, run the test suite: `php artisan test`

## Project Context
- Laravel Framework version 13, PHP 8.5 application, on Debian 13
- Local and Production environments are on Docker
- Test suite: PHPUnit — run with `php artisan test`
- Always follow existing conventions — reference nearby files for patterns
- Never modify .env
- Never modify any .env files, whose files start with: ".env"

## Git
- Do not run git add, git commit, or git push — that is the developer's responsibility

## Project Documentation
Before making any changes, read these files for context:
- docs/ARCHITECTURE.md
- docs/CONVENTIONS.md

## Interaction

- One step at a time
- Ask if ready for Claude to proceed.
- Be honest in all assessments, critiques, and recommendations
- Ask for what you need
- When asking questions, do not use the modal input box
- Do not be "glib". Be accurate, precise, and thorough 