# ADR 002: Bez CQRS

## Context

Projekt ma tylko jeden prosty use case zapisu i nie posiada osobnego złożonego modelu odczytowego.

## Decision

Nie wprowadzamy CQRS.

## Consequences

- unikamy nadmiarowych command/query handlerów i dodatkowych DTO tylko po to, żeby "odhaczyć wzorzec",
- rozwiązanie jest prostsze i bardziej proporcjonalne do zadania,
- jeśli kiedyś pojawi się realny read model albo złożone query use case'y, CQRS będzie można dołożyć świadomie.
