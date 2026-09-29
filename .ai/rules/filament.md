---
paths:
  - 'app/Filament/**'
---

# Filament

## Filament uses the same Actions as the API
Filament pages, resources, and relation managers are adapters. Route reads and writes through Domain Actions and map form state to Data objects; do not duplicate business logic or perform direct Eloquent/filesystem writes.
