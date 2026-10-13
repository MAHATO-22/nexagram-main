<?php
// message_helper.php - shared direct message helpers
require_once 'config.php';

function isFriends($pdo, $a, $b) {
    $s = $pdo->prepare("SELECT 1 FROM friend_requests WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'accepted' LIMIT 1");
    $s->execute([$a, $b, $b, $a]);
    return (bool)$s->fetchColumn();
}

// Returns 'mine' (I blocked peer), 'peer' (peer blocked me), or null
function blockState($pdo, $a, $b) {
    $s = $pdo->prepare("SELECT blocker_id FROM user_blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?) LIMIT 1");
    $s->execute([$a, $b, $b, $a]);
    $r = $s->fetch();
    if (!$r) return null;
    return ((int)$r['blocker_id'] === (int)$a) ? 'mine' : 'peer';
}

// Thread state seen by $who relative to $other
function threadState($pdo, $who, $other) {
    $blocked = blockState($pdo, $who, $other);
    if ($blocked === 'mine') return 'blocked_mine';
    if ($blocked === 'peer') return 'blocked_peer';

    $s = $pdo->prepare("SELECT 1 FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_request = 1 LIMIT 1");
    $s->execute([$other, $who]);
    if ($s->fetchColumn()) return 'request'; // they asked, I must reply or block

    $s->execute([$who, $other]);
    if ($s->fetchColumn()) return 'waiting'; // I asked, waiting for their reply

    return 'ok';
}
