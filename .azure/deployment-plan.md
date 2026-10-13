# Azure Deployment Plan

## Status
Blocked — no Azure subscription is available in the current Azure extension context.

## Goal
Provision an Azure AI Translator resource to generate static UI translation catalogs for Nexagram.

## Requirements
- Classification: Development/test.
- Application/workload: One-time generation of static UI translation catalogs for all 104 locales in `languages/lang.docs`.
- Budget: Free tier only.
- Compliance/data residency: No geographic restriction requested; only static interface strings will be submitted, never user posts, messages, or account data.
- Resource type: Azure AI Translator (Translator F0, if available and suitable).
- Subscription: Azure tools report no available subscriptions; user needs to connect an eligible subscription.
- Region: To be confirmed.
- Resource name and resource group: To be confirmed.
- Usage expectation: One translation-generation run; no app-time calls to Translator.

## Architecture
Pending subscription, region, and resource details. Planned use is a one-time translation build that saves generated catalogs locally.

## Security
- Keep Translator credentials out of source control.
- Use local environment configuration for catalog generation.

## Policy Constraints
Not checked because no subscription is available.

## Execution
No Azure resources will be created until subscription and region are confirmed, the plan is finalized, and the user approves it.
