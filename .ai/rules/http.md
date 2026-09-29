---
paths:
  - 'app/Http/**'
---

# Http

## HTTP adapters delegate use cases
Controllers stay thin: authorize, accept validated input, invoke an Action, and return a Resource or response. Form Requests own transport validation and map structured validated payloads to readonly Data objects. Do not perform Eloquent or filesystem writes in HTTP adapters.
