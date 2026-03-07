# Spec Delta: transactions

## ADDED Requirements

### Requirement: Concurrency-Safe Transaction Numbering
System SHALL generate unique sequential numbers per transaction type and year under concurrent requests.

### Requirement: Immutable Completed Transactions
System SHALL prevent edits/deletes of completed transactions; correction SHALL use cancellation + replacement.

### Requirement: Atomic Posting
System SHALL persist transaction header and items atomically.
