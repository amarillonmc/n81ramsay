<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Core/Utils.php';
require_once __DIR__ . '/../includes/Core/Database.php';
require_once __DIR__ . '/../includes/Models/Card.php';
require_once __DIR__ . '/../includes/Models/Vote.php';

$voteModel = new Vote();
$suspiciousVotes = $voteModel->listSuspiciousVotes();

$targetIds = array_slice($argv, 1);
if (!empty($targetIds)) {
    $targetIds = array_fill_keys($targetIds, true);
    $suspiciousVotes = array_values(array_filter($suspiciousVotes, function ($vote) use ($targetIds) {
        return isset($targetIds[$vote['vote_link']]);
    }));
}

foreach ($suspiciousVotes as $vote) {
    $flags = array();
    if (trim((string) $vote['reason']) === '') {
        $flags[] = 'empty_reason';
    }
    if (trim((string) $vote['initiator_id']) === '') {
        $flags[] = 'empty_initiator';
    }
    if ((int) $vote['record_count'] <= 1) {
        $flags[] = 'low_interaction';
    }
    if (!empty($vote['created_ip'])) {
        $flags[] = 'created_ip:' . $vote['created_ip'];
    }
    echo json_encode([
        'vote_link' => $vote['vote_link'],
        'card_id' => $vote['card_id'],
        'created_at' => $vote['created_at'],
        'created_via' => $vote['created_via'],
        'created_ip' => $vote['created_ip'],
        'record_count' => (int) $vote['record_count'],
        'flags' => $flags,
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
