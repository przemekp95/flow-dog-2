# ADR 003: File-based persistence

## Context

Treść zadania nie wymaga bazy danych, migracji ani query use case'ów.

## Decision

Zamówienia zapisujemy do plików JSON w `var/orders/<env>/<id>.json`.

## Consequences

- zachowujemy trwałość efektu utworzenia zamówienia,
- nie dokładamy niepotrzebnej infrastruktury do zadania rekrutacyjnego,
- zapis pozostaje łatwy do ręcznej demonstracji i jest czytelny dzięki pretty-printed JSON.
