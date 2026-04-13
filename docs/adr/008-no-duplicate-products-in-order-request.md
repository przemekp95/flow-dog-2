# ADR 008: No duplicate products in order request

## Context

Request tworzenia zamówienia przyjmuje listę `items`, ale powtórzenie tego samego `productId` w wielu liniach rozmywa reguły walidacji stocku.

Bez jednoznacznej decyzji kontrakt staje się nieczytelny:

- nie wiadomo, czy stock ma być sprawdzany per linia czy po zsumowaniu,
- duplikaty komplikują zachowanie dla błędów `404` i `409`,
- agregacja nie wnosi wartości biznesowej, bo ten sam efekt da się wyrazić pojedynczą linią z większą ilością.

## Decision

Jeden request tworzenia zamówienia może zawierać dany `productId` najwyżej raz.

Duplikaty są odrzucane na wejściu do use case'a `CreateOrder` jako `422 invalid_items`, zanim nastąpi lookup katalogu produktów.
Model domenowy powtarza ten check jako safeguard inwariantów, ale nie jest pierwszą linią obrony w flow HTTP.

## Consequences

- walidacja stocku pozostaje prosta i spójna, bo działa per linia bez dodatkowej agregacji,
- kontrakt API jest jednoznaczny również dla wywołań spoza HTTP, bo reguła jest egzekwowana już na wejściu use case'a,
- duplikat produktu ma pierwszeństwo nad ewentualnym `404 product_not_found` dla tego samego powtórzonego identyfikatora.
