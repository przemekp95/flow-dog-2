# ADR 005: Tooling quality gates

## Context

Repo ma być publiczne, czytelne i pokazywać dojrzałość nie tylko w kodzie aplikacji, ale też w procesie.

## Decision

Dodajemy `PHPUnit`, `PHPStan`, `PHP CS Fixer`, `Deptrac`, `Prettier`, `composer audit`, `Docker` oraz `Trivy` w CI.

Po zbudowaniu obrazu CI uruchamia też kontener i wykonuje prosty smoke test runtime przeciwko `GET /api/doc.json`, zamiast kończyć walidację obrazu wyłącznie na etapie `docker build` i skanów statycznych.

Spinamy referencyjny patch `PHP 8.4.20` w `config.platform`, CI i obrazach Docker, żeby dependency resolution, quality gate i runtime używały tego samego patcha. Aplikacja nadal deklaruje zgodność z zakresem `>=8.4 <8.5`, ale repo nie utrzymuje własnego polyfilla dla funkcji wprowadzonych natywnie w PHP 8.4 tylko po to, żeby wspierać starszy interpreter.

Wymuszamy też sekwencyjny tryb działania `PHP CS Fixer`, bo równoległe lintowanie potrafiło zgłaszać fałszywy błąd dla `ApiExceptionSubscriber.php` w naszym toolchainie, mimo że pojedynczy plik przechodził poprawnie. To również jest świadome odstępstwo od domyślnej, szybszej konfiguracji, wybrane na rzecz stabilności i powtarzalności quality gate'a.

## Consequences

- utrzymujemy jakość kodu, architektury i dokumentacji,
- Prettier obejmuje pliki repo inne niż PHP,
- Trivy domyka podstawowe security hygiene dla repo i obrazu Docker,
- smoke test obrazu łapie podstawowe regresje runtime, których nie pokaże sam build,
- `config.platform`, CI i obrazy Docker używają tego samego, pinned patcha `PHP 8.4.20`,
- nie utrzymujemy własnego, repozytoryjnego polyfilla tylko po to, żeby wspierać starszy runtime,
- akceptujemy wolniejsze uruchamianie fixera w zamian za stabilniejsze lintowanie.
