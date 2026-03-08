# Design: obligations-arrears

## Architecture Touchpoints
- Scheduled command for monthly generation
- Obligation service for fee matrix expansion
- Aggregation query layer for arrears summaries

## Key Decisions
- Use unique key `(student_id, fee_type_id, period_month, period_year)`
- Use conflict-safe insert/upsert

## Risks
- Inconsistent period boundaries
- Large-batch generation performance

## Validation
- Idempotency tests
- Period boundary tests
- Aggregation correctness tests
