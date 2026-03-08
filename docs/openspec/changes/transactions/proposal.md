# Proposal: transactions

## Summary
Implement immutable income/expense transaction posting with concurrency-safe numbering.

## Problem
Financial recording requires strict correctness under concurrency and correction-by-cancellation.

## Scope
- Transaction and transaction item creation
- Concurrency-safe numbering
- Cancel + replacement flow
- Database immutability trigger/constraints

## Out of Scope
- Receipt PDF generation
- Obligation generation and arrears jobs
- Reporting/dashboard

## Dependencies
- master-data

## Success Criteria
- No duplicate transaction numbers under concurrency
- Completed transactions cannot be edited
- Cancellation flow works and is audited
