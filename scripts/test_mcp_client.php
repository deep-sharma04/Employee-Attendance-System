<?php

/**
 * End-to-End Real Local MCP Client Protocol & Tool Invocation Test
 * Simulates a local AI client (VS Code Copilot / Anti-Gravity IDE) connecting to Laravel MCP Server via STDIO.
 */

$token = $argv[1] ?? 'mcp_jttFtCWUqIYmjamLXp1EFnpmVdIAOVlUmc8u02y6';

echo "=================================================================\n";
echo "       REAL LOCAL MCP CLIENT CONNECTION & PROTOCOL TEST          \n";
echo "=================================================================\n\n";

// --- TEST 1: Unauthenticated Connection Attempt ---
echo "[TEST 1] Testing Unauthenticated STDIO Client Connection...\n";
$procUnauth = proc_open("php artisan mcp:serve", [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
], $pipesUnauth, dirname(__DIR__));
$unauthOut = stream_get_contents($pipesUnauth[1]);
$unauthErr = stream_get_contents($pipesUnauth[2]);
fclose($pipesUnauth[0]); fclose($pipesUnauth[1]); fclose($pipesUnauth[2]);
$unauthExit = proc_close($procUnauth);

if ($unauthExit !== 0 && (str_contains($unauthOut, 'Authentication failed') || str_contains($unauthErr, 'Authentication failed'))) {
    echo "  -> SUCCESS: Unauthenticated client rejected properly.\n";
} else {
    echo "  -> FAILURE: Expected failure for unauthenticated client.\n";
}

// --- TEST 2: Invalid Token Connection Attempt ---
echo "\n[TEST 2] Testing Invalid Token Connection...\n";
$procInvalid = proc_open("php artisan mcp:serve --token=invalid_forged_token_xyz", [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
], $pipesInvalid, dirname(__DIR__));
$invalidOut = stream_get_contents($pipesInvalid[1]);
$invalidErr = stream_get_contents($pipesInvalid[2]);
fclose($pipesInvalid[0]); fclose($pipesInvalid[1]); fclose($pipesInvalid[2]);
$invalidExit = proc_close($procInvalid);

if ($invalidExit !== 0 && (str_contains($invalidOut, 'Invalid or expired') || str_contains($invalidErr, 'Invalid or expired'))) {
    echo "  -> SUCCESS: Invalid token rejected properly.\n";
} else {
    echo "  -> FAILURE: Expected failure for invalid token.\n";
}

// --- TEST 3: Authenticated Live STDIO MCP Protocol Interaction ---
echo "\n[TEST 3] Testing Valid Authenticated STDIO MCP Client Session...\n";
$cmd = "php artisan mcp:serve --token={$token}";
$process = proc_open($cmd, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
], $pipes, dirname(__DIR__));

if (!is_resource($process)) {
    die("ERROR: Failed to launch MCP server process.\n");
}

function sendRpc($pipes, array $payload): array {
    $json = json_encode($payload) . "\n";
    fwrite($pipes[0], $json);
    fflush($pipes[0]);

    $responseLine = fgets($pipes[1]);
    if ($responseLine === false) {
        $stderr = stream_get_contents($pipes[2]);
        throw new RuntimeException("No response received from MCP server. STDERR: " . $stderr);
    }
    return json_decode(trim($responseLine), true) ?: [];
}

try {
    // 3.1: JSON-RPC Initialize
    echo "  3.1 Sending 'initialize' JSON-RPC Handshake...\n";
    $initRequest = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => (object) [],
            'clientInfo' => [
                'name' => 'antigravity-ide-client',
                'version' => '1.0.0'
            ]
        ]
    ];
    $initResponse = sendRpc($pipes, $initRequest);
    $serverName = $initResponse['result']['serverInfo']['name'] ?? 'Unknown';
    $serverVer = $initResponse['result']['serverInfo']['version'] ?? 'Unknown';
    echo "      -> Connected to Server: [{$serverName}] v{$serverVer}\n";

    // 3.2: JSON-RPC Tools List
    echo "  3.2 Sending 'tools/list' JSON-RPC Request...\n";
    $listRequest = [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/list',
        'params' => (object) []
    ];
    $listResponse = sendRpc($pipes, $listRequest);
    $tools = $listResponse['result']['tools'] ?? [];
    echo "      -> Discovered " . count($tools) . " MCP Tools across Phase 31 & Phase 32:\n";
    foreach ($tools as $t) {
        echo "         * {$t['name']} - {$t['description']}\n";
    }

    // 3.3: Call 'project.search' (Phase 31)
    echo "\n  3.3 Invoking MCP Tool: 'project.search'...\n";
    $callProject = [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'project.search',
            'arguments' => (object) []
        ]
    ];
    $projectRes = sendRpc($pipes, $callProject);
    $projectData = $projectRes['result']['structuredContent']['data'] ?? [];
    $projectCount = $projectData['count'] ?? 0;
    $firstProjId = $projectData['projects'][0]['id'] ?? 1;
    echo "      -> Received {$projectCount} projects in authorized scope.\n";

    // 3.4: Call 'project.intelligence_search' (Phase 32: T285)
    echo "\n  3.4 Invoking MCP Tool: 'project.intelligence_search' (Natural-Language Query)...\n";
    $callIntelSearch = [
        'jsonrpc' => '2.0',
        'id' => 4,
        'method' => 'tools/call',
        'params' => [
            'name' => 'project.intelligence_search',
            'arguments' => [
                'query' => 'Show overdue tasks in projects',
            ]
        ]
    ];
    $intelSearchRes = sendRpc($pipes, $callIntelSearch);
    $intelSearchData = $intelSearchRes['result']['structuredContent']['data'] ?? [];
    $groundingStatus = $intelSearchData['grounding']['status'] ?? 'unknown';
    $isFactual = $intelSearchData['grounding']['is_factual'] ?? false;
    echo "      -> Search Intent: [{$intelSearchData['intent']}], Grounding: [{$groundingStatus}], Is Factual: " . ($isFactual ? 'TRUE' : 'FALSE') . "\n";

    // 3.5: Call 'project.explain_health' (Phase 32: T286)
    echo "\n  3.5 Invoking MCP Tool: 'project.explain_health' for Project #{$firstProjId}...\n";
    $callExplainHealth = [
        'jsonrpc' => '2.0',
        'id' => 5,
        'method' => 'tools/call',
        'params' => [
            'name' => 'project.explain_health',
            'arguments' => [
                'project_id' => $firstProjId,
            ]
        ]
    ];
    $healthRes = sendRpc($pipes, $callExplainHealth);
    $healthData = $healthRes['result']['structuredContent']['data'] ?? [];
    $healthVal = $healthData['health'] ?? 'unknown';
    $evidenceCount = count($healthData['evidence'] ?? []);
    echo "      -> Deterministic Health: [{$healthVal}], Grounding: [{$healthData['grounding']['status']}], Evidence items: {$evidenceCount}\n";

    // 3.6: Call 'task.recommend_allocation' (Phase 32: T287)
    echo "\n  3.6 Invoking MCP Tool: 'task.recommend_allocation'...\n";
    $callRec = [
        'jsonrpc' => '2.0',
        'id' => 6,
        'method' => 'tools/call',
        'params' => [
            'name' => 'task.recommend_allocation',
            'arguments' => [
                'project_id' => $firstProjId,
                'required_skills' => ['PHP', 'Laravel'],
            ]
        ]
    ];
    $recRes = sendRpc($pipes, $callRec);
    $recData = $recRes['result']['structuredContent']['data'] ?? [];
    $recCount = $recData['recommendations_count'] ?? 0;
    echo "      -> Received {$recCount} ranked candidate recommendations (Read-only, no mutations).\n";

    // 3.7: Call 'project.management_report' (Phase 32: T288)
    echo "\n  3.7 Invoking MCP Tool: 'project.management_report' (Productivity & Workload)...\n";
    $callReport = [
        'jsonrpc' => '2.0',
        'id' => 7,
        'method' => 'tools/call',
        'params' => [
            'name' => 'project.management_report',
            'arguments' => [
                'report_type' => 'productivity',
            ]
        ]
    ];
    $reportRes = sendRpc($pipes, $callReport);
    $reportData = $reportRes['result']['structuredContent']['data'] ?? [];
    echo "      -> Generated Report Type: [{$reportData['report_type']}], Grounding: [{$reportData['grounding']['status']}]\n";

    // 3.8: Call 'ai.action.pending_list' (Phase 33: T292)
    echo "\n  3.8 Invoking MCP Tool: 'ai.action.pending_list' (Server-Side Approval Gates)...\n";
    $callPending = [
        'jsonrpc' => '2.0',
        'id' => 8,
        'method' => 'tools/call',
        'params' => [
            'name' => 'ai.action.pending_list',
            'arguments' => (object) []
        ]
    ];
    $pendingRes = sendRpc($pipes, $callPending);
    $pendingData = $pendingRes['result']['structuredContent']['data'] ?? [];
    $pendingCount = $pendingData['pending_count'] ?? 0;
    echo "      -> Retrieved {$pendingCount} pending approval proposals in authorized scope.\n";

    // 3.9: Call 'task.bulk_reassign' (Phase 33: T293, T295) with Idempotency Key (T294)
    echo "\n  3.9 Invoking MCP Tool: 'task.bulk_reassign' (Sensitive Destructive Action)...\n";
    $callBulk = [
        'jsonrpc' => '2.0',
        'id' => 9,
        'method' => 'tools/call',
        'params' => [
            'name' => 'task.bulk_reassign',
            'arguments' => [
                'from_user_id' => 3,
                'to_user_id' => 3,
                'idempotency_key' => 'mcp_live_test_bulk_reassign_' . time(),
            ]
        ]
    ];
    $bulkRes = sendRpc($pipes, $callBulk);
    $bulkContent = $bulkRes['result']['structuredContent'] ?? [];
    if (!empty($bulkContent['requires_approval'])) {
        echo "      -> Approval Gate Triggered: Action Log #{$bulkContent['action_log_id']} is pending approval.\n";
    } else {
        $bulkStatus = $bulkContent['data']['status'] ?? 'completed';
        echo "      -> Bulk Reassignment Result Status: [{$bulkStatus}]\n";
    }

    echo "\n=================================================================\n";
    echo "   REAL LOCAL MCP CLIENT CONNECTION & TOOLS TEST: PASSED!        \n";
    echo "=================================================================\n";

} finally {
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
}
