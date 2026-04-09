# Phase 1: Foundation - Research

**Analysis Date:** 2026-04-09

## Research Objectives
1.  Define the structure for a lightweight Service Container.
2.  Determine the strategy for refactoring static core classes to instance-based logic.
3.  Establish a non-breaking Bridge pattern for the Database layer.

## Findings

### 1. Service Container Design
A minimal PSR-11 compliant container is sufficient for this project.
- **Location:** `src/Core/Container.php`.
- **Logic:**
    - `bindings` array for factory resolvers.
    - `instances` array for shared singletons.
    - `resolve(string $abstract)` method using `ReflectionClass`.
- **Autowiring:** If a constructor parameter has a type hint, the container will recursively resolve it. This is essential for the future decoupling of Services.

### 2. Core Refactoring Strategy
Currently, `Database`, `Session`, and `Logger` are static-heavy.
- **Logger:** Simple to convert. Monolog already provides an instance-based logger; we just need a thin wrapper.
- **Session:** Should be converted from a collection of static methods to an object that holds session state and configuration. This allows for easier testing of session-dependent code (like `AuthService`).
- **Database:** The most critical. Must handle nested transactions (savepoints) as instance state.

### 3. Database Bridge Pattern
To avoid breaking 700+ function calls, the `Database` class will retain its static signatures as "Proxies" to the container-resolved instance.

```php
// BEFORE
public static function query(string $sql, array $bindings = []) { ... }

// AFTER (Bridge)
public static function query(string $sql, array $bindings = []): PDOStatement
{
    // Resolve singleton from container
    return App::container()->get(Database::class)->query($sql, $bindings);
}
```

## Validation Architecture
- **Criteria 1:** `ContainerTest` must pass, verifying singleton vs factory resolution.
- **Criteria 2:** `Database` instance must correctly handle nested savepoint names without static interference.
- **Criteria 3:** Existing integration tests (e.g., `AnimalKennelCoordinatorTest`) must pass without modification to their `Database::query` usage.

---

*Phase: 01-foundation*
*Research complete: 2026-04-09*
