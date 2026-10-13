<?php

function getPeopleSuggestions(PDO $pdo, int $userId, int $limit = 6): array
{
    $limit = max(1, min($limit, 12));

    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.profile_image, u.department, u.class_name, u.school_name,
            CASE
                WHEN NULLIF(TRIM(me.class_name), '') IS NOT NULL
                  AND LOWER(TRIM(u.class_name)) = LOWER(TRIM(me.class_name)) THEN 'class'
                WHEN u.role = 'student' AND me.role = 'student'
                  AND (NULLIF(TRIM(u.class_name), '') IS NULL OR NULLIF(TRIM(me.class_name), '') IS NULL)
                  AND CHAR_LENGTH(COALESCE(NULLIF(u.student_id, ''), NULLIF(u.user_code, ''))) >= 3
                  AND LEFT(COALESCE(NULLIF(u.student_id, ''), NULLIF(u.user_code, '')), 3)
                      = LEFT(COALESCE(NULLIF(me.student_id, ''), NULLIF(me.user_code, '')), 3) THEN 'id_group'
                WHEN NULLIF(TRIM(me.department), '') IS NOT NULL
                  AND LOWER(TRIM(u.department)) = LOWER(TRIM(me.department)) THEN 'department'
                WHEN NULLIF(TRIM(me.school_name), '') IS NOT NULL
                  AND LOWER(TRIM(u.school_name)) = LOWER(TRIM(me.school_name)) THEN 'school'
                ELSE 'platform'
            END AS match_reason,
            CASE
                WHEN NULLIF(TRIM(me.class_name), '') IS NOT NULL
                  AND LOWER(TRIM(u.class_name)) = LOWER(TRIM(me.class_name)) THEN 0
                WHEN u.role = 'student' AND me.role = 'student'
                  AND (NULLIF(TRIM(u.class_name), '') IS NULL OR NULLIF(TRIM(me.class_name), '') IS NULL)
                  AND CHAR_LENGTH(COALESCE(NULLIF(u.student_id, ''), NULLIF(u.user_code, ''))) >= 3
                  AND LEFT(COALESCE(NULLIF(u.student_id, ''), NULLIF(u.user_code, '')), 3)
                      = LEFT(COALESCE(NULLIF(me.student_id, ''), NULLIF(me.user_code, '')), 3) THEN 1
                WHEN NULLIF(TRIM(me.department), '') IS NOT NULL
                  AND LOWER(TRIM(u.department)) = LOWER(TRIM(me.department)) THEN 2
                WHEN NULLIF(TRIM(me.school_name), '') IS NOT NULL
                  AND LOWER(TRIM(u.school_name)) = LOWER(TRIM(me.school_name)) THEN 3
                ELSE 4
            END AS match_priority
        FROM users u
        JOIN users me ON me.id = ?
        WHERE u.id <> ?
          AND NOT EXISTS (
              SELECT 1 FROM follows f
              WHERE f.follower_id = me.id AND f.following_id = u.id
          )
          AND NOT EXISTS (
              SELECT 1 FROM friend_requests fr
              WHERE (fr.sender_id = me.id AND fr.receiver_id = u.id)
                 OR (fr.sender_id = u.id AND fr.receiver_id = me.id)
          )
          AND NOT EXISTS (
              SELECT 1 FROM user_blocks b
              WHERE (b.blocker_id = me.id AND b.blocked_id = u.id)
                 OR (b.blocker_id = u.id AND b.blocked_id = me.id)
          )
        ORDER BY match_priority ASC, u.created_at DESC
        LIMIT {$limit}
    ");
    $stmt->execute([$userId, $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
