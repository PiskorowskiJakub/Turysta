<?php

declare(strict_types=1);

namespace Turysta\Social;

/**
 * Moment ostatniej aktywnosci gracza (UTC): najnowszy z trzech sladow - aktywnej sesji (dokladnosc do minuty),
 * godziny ostatniej aktywnosci w grze i ostatniego logowania. Sesja znika po wylogowaniu, wiec pozostale dwa
 * sladu pozwalaja pokazac "ostatnio aktywny" takze dla wylogowanych.
 */
final class LastSeen
{
    /** Wyrazenie SQL (DATETIME albo NULL) dla gracza o id w kolumnie $userCol, np. 'u.id'. */
    public static function sql(string $userCol): string
    {
        return "NULLIF(GREATEST(
                    COALESCE((SELECT MAX(ls_s.last_activity) FROM sessions ls_s WHERE ls_s.user_id = $userCol), '1970-01-01'),
                    COALESCE((SELECT ls_t.last_active_hour FROM user_stats ls_t WHERE ls_t.user_id = $userCol), '1970-01-01'),
                    COALESCE((SELECT ls_u.last_login_at FROM users ls_u WHERE ls_u.id = $userCol), '1970-01-01')
                ), '1970-01-01')";
    }
}
