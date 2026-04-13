# ADR 001: Symfony + light hexagon

## Context

Zadanie dotyczy refaktoru jednego use case'u, ale jednocześnie ma pokazać jakość decyzji projektowych i czytelność rozwiązania.

## Decision

Używamy Symfony 7.4 jako lekkiej warstwy HTTP i kontenera DI oraz organizujemy kod w układzie `Domain / Application / Infrastructure / UI`.
Adnotacje OpenAPI pozostają współlokowane z warstwą HTTP zamiast osobnej warstwy klas schematów, bo scope projektu jest mały i zależy nam na proporcjonalności rozwiązania.

## Consequences

- zyskujemy czytelny podział odpowiedzialności bez budowania pełnej platformy,
- logika biznesowa nie siedzi w kontrolerze,
- dokumentacja OpenAPI pozostaje blisko kontrolera i DTO HTTP jako świadomy trade-off,
- projekt pozostaje mały i łatwy do przeczytania podczas rekrutacji.
