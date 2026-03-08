# Design: core-reports

## Architecture Touchpoints
- Report service query composition
- Export classes (PDF/Excel)
- Caching/index tuning for frequent queries

## Key Decisions
- Keep report query logic centralized
- Use explicit period/date filters and deterministic grouping

## Risks
- Mismatch between report and ledger totals
- Slow exports on large data volumes

## Validation
- Snapshot tests for report totals
- Export integration tests
- Query performance checks
