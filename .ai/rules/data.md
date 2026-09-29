---
paths:
  - 'app/Domain/*/Data/**'
---

# Data

## Data objects are readonly DTOs
Use final readonly Data classes for structured inputs or outputs crossing an adapter/use-case boundary. Data objects are not Form Requests and contain no validation, persistence, or orchestration; do not wrap isolated scalars or models without a structural need.
