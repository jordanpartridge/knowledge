# Knowledge Gateway — Build Plan

## Project

**Repo**: `jordanpartridge/knowledge` — [github.com/jordanpartridge/knowledge](https://github.com/jordanpartridge/knowledge)
**Location on Mac**: `~/Sites/jp-knowledge/`
**Stack**: Laravel Zero (standalone CLI), Pest v4, PHP 8.3+

Personal knowledge gateway connecting 8,500+ knowledge entries (prefrontal-cortex) to 213+ AI models (opencode serve).

---

## What's Built (Issues #1-6, #8, #10, #11) — ALL PASSING: 79 tests, 135 assertions

### Services (`app/Services/`)

| Service | File | Purpose |
|---------|------|---------|
| **OpenCodeClient** | `OpenCodeClient.php` | HTTP client for opencode serve API. Uses real API: `POST /session/{id}/message` with `parts` array. Handles model errors in response body. Auto-creates sessions. |
| **ModelRouter** | `ModelRouter.php` | Intent-based routing. Classifies queries (what/how/why/analyze/code/search) → maps to provider/model/tier. Supports explicit overrides and shortcuts (grok, opus, claude, gemini, llama). |
| **PrefrontalClient** | `PrefrontalClient.php` | HTTP client for prefrontal-cortex knowledge API. Search, filter, store, context endpoints with bearer token auth. |
| **KnowledgeAiService** | `KnowledgeAiService.php` | Glue layer: fetches knowledge context → builds enriched prompt → routes to AI via OpenCodeClient. Supports query, queryDirect, consensus. Has ResponseCache integration. |
| **ResponseCache** | `ResponseCache.php` | Query-level caching with configurable TTL and enable/disable. Keys on query+model hash. |

### Commands (`app/Commands/`)

| Command | Signature | Purpose |
|---------|-----------|---------|
| **query** | `query {question} [--model] [--consensus] [--no-context] [--json] [--limit]` | Primary command. Query knowledge + AI. |
| **consensus** | `consensus {question} [--models] [--json]` | Multi-model comparison (default: claude, grok, gemini). |
| **models** | `models [--provider] [--tier] [--json]` | Display routing table with filters. |
| **health** | `health [--json]` | Check opencode + prefrontal service status. |
| **remember** | `remember {content} [--title] [--tags] [--json]` | Store knowledge entries. |

### Config

```php
// config/knowledge.php
'opencode' => ['host' => '127.0.0.1', 'port' => 4096],
'prefrontal' => ['url' => '...', 'token' => env('PREFRONTAL_API_TOKEN')],
'cache' => ['ttl' => 3600, 'enabled' => true],
```

### DI (AppServiceProvider)

All services registered as singletons: OpenCodeClient, ModelRouter, PrefrontalClient, ResponseCache, KnowledgeAiService.

---

## opencode serve API — Real Structure (Verified Live)

**Start**: `opencode serve --port 4096`
**Health**: `GET /` returns web UI; `GET /doc` returns OpenAPI 3.1.1 spec

### Key Endpoints

```
POST   /session                          → Create session (body: {})
POST   /session/{id}/message             → Send prompt (sync, returns full response)
POST   /session/{id}/prompt_async        → Send prompt (async, returns 204)
POST   /session/{id}/shell               → Run shell command
POST   /session/{id}/command             → Send command
GET    /session/{id}/message             → Get all messages
GET    /session/{id}/todo                → Get session todos
POST   /session/{id}/fork               → Fork session
POST   /session/{id}/share              → Share session
GET    /provider                         → List all providers + models
POST   /mcp/{name}/connect              → Connect MCP server
```

### Message Request Body

```json
{
  "parts": [{"type": "text", "text": "your prompt"}],
  "model": {"providerID": "anthropic", "modelID": "claude-sonnet-4-5-20250929"},
  "system": "optional system prompt"
}
```

### Message Response

```json
{
  "info": {
    "id": "msg_...",
    "tokens": {"input": 10, "output": 5, "reasoning": 0},
    "cost": 0.001,
    "error": null
  },
  "parts": [
    {"type": "text", "text": "The AI response"}
  ]
}
```

### Confirmed Working Providers

| Provider | Status | Notes |
|----------|--------|-------|
| **anthropic** | WORKING | All Claude models available |
| **openrouter** | WORKING | 178+ models, universal fallback |
| **groq** | API key invalid | Needs GROQ_API_KEY |
| **xai** | Returns empty body | Needs XAI_API_KEY or different config |
| **google** | Returns empty body | Needs GOOGLE_API_KEY |

### Model Routing Table (Current)

| Intent | Provider | Model | Tier |
|--------|----------|-------|------|
| what | groq | llama-3.3-70b-versatile | fast |
| how | xai | grok-3 | balanced |
| why | anthropic | claude-sonnet-4-5-20250929 | premium |
| analyze | anthropic | claude-opus-4-6 | premium |
| code | openrouter | google/gemini-2.5-pro | balanced |
| search | groq | llama-3.3-70b-versatile | fast |
| default | xai | grok-3 | balanced |

**Note**: groq and xai routes will fail until API keys are configured. Consider routing through openrouter as universal fallback.

---

## Remaining Issues (Open)

### Issue #7: SSE Streaming for Real-Time Responses
- `POST /session/{id}/prompt_async` returns 204 immediately
- Need to subscribe to SSE event stream for live token streaming
- Would add `--stream` flag to query/consensus commands
- **Priority**: Medium — current sync API works fine for CLI

### Issue #9: Bridge to conduit-knowledge SemanticSearchService
- `conduit-ui/knowledge` has a completely stubbed `SemanticSearchService`
- This issue connects jp-knowledge's AI layer as the backend
- **Priority**: Phase 3 — requires conduit-knowledge changes

### Issue #12: OpenCode Hooks for Automatic Knowledge Capture
- Use opencode's hook system to automatically capture insights
- Every session/conversation → extract learnings → store via `remember`
- **Priority**: High — this is the "always learning" loop

---

## The Vision: Ultimate AI IDE Architecture

```
You (nvim/terminal)
    ↓
opencode serve (port 4096) ← always running
    ↓
┌─────────────────────────────────┐
│  knowledge gateway (this app)   │
│  ┌───────────┐ ┌──────────────┐ │
│  │ ModelRouter│ │ResponseCache │ │
│  └───────────┘ └──────────────┘ │
│  ┌───────────────────────────┐  │
│  │   KnowledgeAiService      │  │
│  │   (enriches every query   │  │
│  │    with your 8500+ entries)│  │
│  └───────────────────────────┘  │
└─────────────────────────────────┘
    ↓                    ↓
prefrontal-cortex    213+ AI models
(knowledge store)    (via opencode)
```

### Three Things Left to Build

1. **`know conductor`** — Orchestration layer: intent → plan → dispatch → execute → learn
2. **`know mcp`** — Register knowledge as an MCP server so opencode sessions auto-access it
3. **`know gate`** — Quality gate integration with synapse-sentinel

### MCP Integration (Highest Impact Next Step)

opencode supports MCP servers via `POST /mcp/{name}/connect`. If we register this knowledge app as an MCP tool, then EVERY opencode session (including nvim integration) automatically has access to the knowledge base. No manual `query` commands needed.

---

## Getting Started on Odin

```bash
# Clone
git clone git@github.com:jordanpartridge/knowledge.git ~/Sites/jp-knowledge
cd ~/Sites/jp-knowledge

# Install
composer install

# Verify
./vendor/bin/pest  # Should show 79 tests passing

# Start opencode serve (if not running)
opencode serve --port 4096 &

# Test live
php knowledge health
php knowledge models
php knowledge query "what is Laravel"
php knowledge consensus "best caching strategy"
php knowledge remember "important insight" --tags=architecture
```

### Environment Needed

```bash
export PREFRONTAL_API_TOKEN="LWz360AtJGKAsN0UtBGE4qnClaa5CT8J8RI23NA8"
# Optional: GROQ_API_KEY, XAI_API_KEY for direct provider access
# OpenRouter key should be in opencode config already
```

---

## Related GitHub Resources

- **This repo**: [jordanpartridge/knowledge](https://github.com/jordanpartridge/knowledge)
- **conduit-knowledge**: [conduit-ui/knowledge](https://github.com/conduit-ui/knowledge) — existing CLI (SemanticSearchService is stubbed)
- **prefrontal-cortex**: Knowledge store API (8,500+ entries)
- **synapse-sentinel**: Quality gate system
- **opencode**: [sst/opencode](https://github.com/sst/opencode) — v1.1.65 installed

## File Inventory

```
app/
├── Commands/
│   ├── ConsensusCommand.php    # Multi-model queries
│   ├── HealthCommand.php       # Service health checks
│   ├── ModelsCommand.php       # Routing table display
│   ├── QueryCommand.php        # Primary query command
│   └── RememberCommand.php     # Knowledge storage
├── Providers/
│   └── AppServiceProvider.php  # DI bindings
└── Services/
    ├── KnowledgeAiService.php  # Core glue layer
    ├── ModelRouter.php         # Intent-based routing
    ├── OpenCodeClient.php      # opencode serve HTTP client
    ├── PrefrontalClient.php    # Knowledge API client
    └── ResponseCache.php       # Query caching

tests/Feature/
├── BootstrapTest.php           # 3 tests
├── ConsensusCommandTest.php    # 3 tests
├── HealthCommandTest.php       # 4 tests
├── KnowledgeAiServiceTest.php  # 9 tests
├── ModelRouterTest.php         # 13 tests
├── ModelsCommandTest.php       # 4 tests
├── OpenCodeClientTest.php      # 13 tests
├── PrefrontalClientTest.php    # 8 tests
├── QueryCommandTest.php        # 7 tests
├── RememberCommandTest.php     # 6 tests
└── ResponseCacheTest.php       # 7 tests

config/knowledge.php            # All configuration
CLAUDE.md                       # Project documentation
PLAN.md                         # This file
```
