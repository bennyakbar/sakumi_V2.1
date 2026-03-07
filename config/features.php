<?php

return [
    // Accounting engine is now mandatory — always enabled.
    // The feature flag is retained only for backward-compatible config reads;
    // it cannot be disabled.  All financial operations MUST post GL entries.
    'accounting_engine_v2' => true,
];
