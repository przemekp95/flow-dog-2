# ADR 006: Selektywne testy charakteryzacyjne

## Context

Refaktor istniejącego kodu powinien zabezpieczyć wartościowe zachowania starej implementacji, ale nie powinien utrwalać jej błędów.

## Decision

Dodajemy selektywne testy charakteryzacyjne oparte o `legacy/OrderController.php`.

## Consequences

- chronimy istotne behavior biznesowe,
- nie konserwujemy błędów takich jak `500` dla walidacji czy `FREEMONEY`,
- decyzja selektywna jest jawnie opisana, bo stanowi świadome odstępstwo od prostego "odtwórz wszystko 1:1".
