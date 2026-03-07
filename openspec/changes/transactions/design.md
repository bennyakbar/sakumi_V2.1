# Design: transactions

## Architecture Touchpoints
- Transaction service
- DB transaction boundaries
- DB trigger/check constraints
- Audit hooks

## Key Decisions
- Number generation guarded by pessimistic lock
- Financial writes are atomic
- Corrections are cancel + replacement only

## Risks
- Race conditions in numbering
- Trigger mismatch with app logic

## Validation
- Concurrency tests for numbering
- Feature tests for cancel flow
- DB-level immutability tests
