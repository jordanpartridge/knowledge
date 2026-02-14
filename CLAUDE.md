# CLAUDE.md

## Project: knowledge

Personal knowledge gateway — connects 8,500+ knowledge entries (via prefrontal-cortex) to 213+ AI models (via opencode serve).

## Tech Stack
- Laravel Zero (standalone CLI app)
- Pest (testing framework)
- PHPStan level 9
- Laravel Pint (code style)

## Development Commands

```bash
composer install
./vendor/bin/pest           # Run tests
./vendor/bin/pint           # Code formatting
./vendor/bin/phpstan analyze # Static analysis
php knowledge query "your question"  # Query knowledge with AI
```

## Architecture
- `app/Services/` — Core services (OpenCodeClient, ModelRouter, KnowledgeAiService, PrefrontalClient)
- `app/Commands/` — CLI commands (QueryCommand, ModelsCommand, RememberCommand, etc.)
- `config/knowledge.php` — Configuration

## Key Integration Points
- **opencode serve** (port 4096) — AI model gateway, SSE streaming, 213+ models
- **prefrontal-cortex API** — Knowledge base REST API with bearer token auth
- **conduit-knowledge** — Existing knowledge CLI (this app enriches it with AI)

## Quality Standards
- 100% test coverage enforced
- PHPStan level 9 with strict rules
- Laravel Pint code style
- TDD workflow: RED → GREEN → REFACTOR
