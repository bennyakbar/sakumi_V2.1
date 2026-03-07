# Design: whatsapp-notifications

## Architecture Touchpoints
- Event/listener and scheduled command integration
- WhatsApp service client
- Notifications persistence and retry jobs

## Key Decisions
- Queue all outbound sends
- Keep `whatsapp_api_key` in env only
- Store only non-secret gateway URL/template settings in DB

## Risks
- Provider outages/timeouts
- Invalid parent phone numbers

## Validation
- Integration tests with mocked provider
- Retry and failure-path tests
