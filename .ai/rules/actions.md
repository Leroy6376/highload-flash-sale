---
paths:
  - 'app/Domain/*/Actions/**'
---

# Actions

## One Action per application use case
Represent every read or write use case with a final Action exposing handle(). Actions orchestrate models and services and own transaction, locking, and cross-aggregate coordination boundaries. Keep transport concerns out of Actions.
