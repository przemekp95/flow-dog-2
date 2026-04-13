# ADR 004: Sync domain events

## Context

Chcemy pokazać gotowość na event-driven, ale bez rozbudowywania brokera i topologii asynchronicznej ponad scope zadania.

## Decision

Po utworzeniu zamówienia publikujemy synchroniczny `OrderCreated`.

## Consequences

- architektura pokazuje punkt rozszerzeń,
- nie rozmywamy celu zadania przez RabbitMQ i konsumentów,
- produkcyjnie naturalnym krokiem byłby outbox i broker.
