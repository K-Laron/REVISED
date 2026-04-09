# Phase 1: Foundation - Context

**Gathered:** 2026-04-09
**Status:** Ready for planning

<domain>
## Phase Boundary

Establishing the dependency injection infrastructure and modernizing core infrastructure classes. This phase delivers the `Container` and refactors `Database`, `Session`, and `Logger` to be instance-based and Container-managed.

</domain>

<decisions>
## Implementation Decisions

### Dependency Injection
- **D-01:** Implement `App\Core\Container` as a PSR-11 compliant service container.
- **D-02:** Use Reflection-based Autowiring as the default resolution strategy.
- **D-03:** Register `Database`, `Session`, and `Logger` as Singletons within the Container.

### Database Refactor
- **D-04:** Convert `App\Core\Database` from static-only methods to instance-based methods.
- **D-05:** Implement a static "Bridge" method or proxy in `Database` to maintain zero-breakage for existing `Database::query()` calls while they are incrementally migrated.

### Session & Logging
- **D-06:** Refactor `App\Core\Session` to be instance-based, allowing it to be managed by the Container.
- **D-07:** Refactor `App\Core\Logger` to be managed by the Container, ensuring a shared Monolog instance.

### the agent's Discretion
- Choice of specific Reflection helper or library for autowiring (prefer native PHP Reflection if possible).
- Exact implementation of the Database Bridge pattern.

</decisions>

<specifics>
## Specific Ideas
- The system currently uses `Database::query()` extensively; the Bridge is critical to avoid a total system failure during the transition.
- The `Container` should be accessible via a global entry point (e.g., `App\Core\App::container()`) for use in the legacy parts of the codebase.

</specifics>

<canonical_refs>
## Canonical References

### Core Framework
- `.planning/codebase/ARCHITECTURE.md` — Current request lifecycle and class roles.
- `src/Core/Database.php` — Current static implementation to be refactored.
- `src/Core/Router.php` — Entry point for controller dispatching (relevant for future DI).

</canonical_refs>

<deferred>
## Deferred Ideas
- Controller-level DI — Phase 5.
- Repository Pattern implementation — Phase 2.
- Full removal of static Bridge — Future Milestone.

</deferred>

---

*Phase: 01-foundation*
*Context gathered: 2026-04-09*
