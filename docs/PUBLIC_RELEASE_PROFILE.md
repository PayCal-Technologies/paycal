# Public release profile

This checkout uses its own Vigil configuration. The private source checkout runs its complete compliance and SOC2 evidence gates before promotion. Public promotion excludes those evidence trees and private administrative features.

Public gates retain Composer freshness, PHPStan without baselines, quick tests, dependency security audits, secret scanning, JavaScript lint and tests, staged accessibility checks, documentation and promotion boundaries. The new profile does not import private checkout housekeeping rules for assistant files, icon deduplication or the private CSS overhaul ratchet. Those rules were not present in this public checkout's prior hook scripts.

Composer dependencies remain vendored according to the existing public repository contract. Lockfiles and audits verify dependencies; vendor sample keys are excluded from the staged assignment detector consistently with the existing Gitleaks vendor exclusion. PayCal source remains scanned.

Shared public translations and README changes are reviewed as public deltas. Private complete versions of shared files must not be copied wholesale. The public exclusion list continues to reject private privacy controls, compliance evidence and administrative implementations.
