# ADR 007: Realny Swagger do demonstracyjnego testowania

## Context

Projekt ma być czytelny dla rekrutera i łatwy do demonstracyjnego uruchomienia bez dodatkowych narzędzi.

## Decision

Dodajemy OpenAPI generowane z kodu oraz Swagger UI pod `/api/doc` i surowy, pretty-printed JSON pod `/api/doc.json`.

## Consequences

- kontrakt API jest living documentation,
- endpoint można przetestować ręcznie z poziomu przeglądarki,
- dokumentacja nie rozjeżdża się z kodem tak łatwo jak przy ręcznie utrzymywanym pliku.
