export type Gender = 'f' | 'm' | 'x';

export interface Me {
  server_time: string;
  user: {
    id: number;
    email: string;
    character_name: string;
    avatar_id: number | null;
    gender: Gender | null;
    gender_change_available_at: string | null;
    role: string;
    email_verified: boolean;
    tutorial_done: boolean;
  };
  wallet: { coins_cents: number; tickets: number; points: number };
  skills: { code: string; name: string; level: number }[];
  progress: {
    world_id: number;
    chapter_id: number;
    world_ordinal: number;
    world_name: string;
    chapter_ordinal: number;
    chapter_name: string;
  } | null;
  stats: PlayerStats;
  active: { training: unknown; job: unknown };
  notifications_unread: number;
}

export interface PlayerStats {
  registered_at: string;
  account_days: number;
  active_days: number;
  current_streak: number;
  longest_streak: number;
  rank: number;
  earnings: {
    total_cents: number;
    jobs: { code: string; name: string; cents: number }[];
    other_cents: number;
  };
}

export interface Avatar {
  id: number;
  name: string;
  gender: Gender;
  image_url: string;
}

export interface LoginResponse {
  csrf_token: string;
  user: { id: number; character_name: string; tutorial_done: boolean };
}

export interface RegisterInput {
  character_name: string;
  email: string;
  password: string;
  password_confirm: string;
  referral_code: string;
}

export interface SkillInfo {
  id: number;
  code: string;
  name: string;
  description: string | null;
  world_ordinal: number | null;
  locked: boolean;
  level: number;
  in_queue: number;
  max_level: number | null;
  next_level: number | null;
  next_cost_cents: number | null;
  next_duration_seconds: number | null;
}

export interface SkillsResponse {
  server_time: string;
  current_world: number;
  worlds: { ordinal: number; name: string }[];
  skills: SkillInfo[];
}

export interface TrainingItem {
  id: number;
  kind: 'skill' | 'chapter';
  skill: { id: number; code: string; name: string } | null;
  chapter: { id: number; name: string; ordinal: number } | null;
  target_level: number;
  cost_cents: number;
  duration_seconds: number;
  started_at: string | null;
  ends_at: string | null;
  cancellable: boolean;
  refund_cents: number;
}

export interface TrainingState {
  server_time: string;
  max_slots: number;
  active: TrainingItem | null;
  queue: TrainingItem[];
}

export type CancelPolicy = 'proportional' | 'none';

export interface JobInfo {
  id: number;
  code: string;
  name: string;
  description: string | null;
  duration_seconds: number;
  reward_cents: number;
  base_reward_cents: number;
  cooldown_seconds: number;
  cancel_policy: CancelPolicy;
}

export interface ActiveJob {
  id: number;
  job: JobInfo;
  started_at: string;
  ends_at: string;
  earned_cents: number | null;
}

export interface JobsResponse {
  server_time: string;
  jobs: JobInfo[];
  trade: { level: number; bonus_permille: number };
  available_at: string | null;
  active: ActiveJob | null;
  payout_cents?: number;
}

export interface NotificationItem {
  id: number;
  type: string;
  payload: Record<string, string | number>;
  created_at: string;
  read: boolean;
}

export interface WorldChapter {
  id: number;
  ordinal: number;
  name: string;
  status: 'done' | 'current' | 'next' | 'in_queue' | 'locked';
}

export interface ChapterRequirement {
  skill_id: number;
  code: string;
  name: string;
  required: number;
  level: number;
  met: boolean;
}

export interface WorldResponse {
  server_time: string;
  world: { id: number; ordinal: number; name: string } | null;
  chapters: WorldChapter[];
  current: { id: number; ordinal: number; name: string; story_text: string | null } | null;
  next: {
    id: number;
    ordinal: number;
    name: string;
    world_name: string;
    new_world: boolean;
    duration_seconds: number;
    cost_cents: number;
    requirements: ChapterRequirement[];
    world_requirement: {
      world_ordinal: number;
      world_name: string;
      required: number;
      done: number;
      total: number;
      complete: boolean;
      met: boolean;
    } | null;
    requirements_met: boolean;
    in_queue: boolean;
  } | null;
}

export interface RankingRow {
  id: number;
  rank: number;
  name: string;
  points: number;
  world: number | null;
  chapter: number | null;
  me: boolean;
}

export interface RankingResponse {
  total: number;
  page: number;
  pages: number;
  per_page: number;
  per_page_options: number[];
  rows: RankingRow[];
  me: { rank: number; points: number; page: number } | null;
}

export interface EarnedBadge {
  group: string;
  tier: string;
  min: number;
  earned_at: string;
}

export interface BadgesResponse {
  badges: EarnedBadge[];
  featured: string[];
}

/** Publiczny profil gracza; nazwy swiata i rozdzialu sa null, gdy ogladajacy jeszcze nie dotarl do tego swiata. */
export interface PlayerProfile {
  id: number;
  name: string;
  gender: Gender | null;
  avatar_name: string | null;
  avatar_url: string | null;
  world: { ordinal: number; name: string | null } | null;
  chapter: { ordinal: number; name: string | null } | null;
  rank: number;
  points: number;
  registered_at: string;
  longest_streak: number;
  skill_levels: number;
  badges: EarnedBadge[];
  featured: string[];
}

export interface Announcement {
  id: number;
  title: string;
  body: string;
  published: boolean;
  pinned: boolean;
  created_at: string;
  updated_at: string;
}

export interface DailyRewardDay {
  day: number;
  coins_cents: number;
  points: number;
}

export interface DailyRewardState {
  days: DailyRewardDay[];
  /** Ile dni biezacego cyklu jest juz odebranych (0-7). */
  claimed_through: number;
  claimed_today: boolean;
  next_day: number | null;
  can_claim: boolean;
}

export interface TopRow {
  id: number;
  rank: number;
  name: string;
  points: number;
  me: boolean;
}

export interface HomeResponse {
  announcements: Announcement[];
  daily: DailyRewardState;
  top: TopRow[];
  referral_code: string | null;
}

export interface ClaimResponse {
  daily: DailyRewardState;
  reward: DailyRewardDay;
}


export interface Paged<T> {
  total: number;
  page: number;
  pages: number;
  per_page: number;
  rows: T[];
}

export type AccountStatus = 'active' | 'suspended' | 'banned' | 'pending';
export type Role = 'player' | 'moderator' | 'admin';

export interface AdminUserRow {
  id: number;
  name: string;
  email: string;
  role: Role;
  status: AccountStatus;
  email_verified: boolean;
  registered_at: string;
  last_login_at: string | null;
  points: number;
  coins_cents: number;
  world: number | null;
  chapter: number | null;
}

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: Role;
  status: AccountStatus;
  status_reason: string | null;
  suspended_until: string | null;
  email_verified: boolean;
  tutorial_done: boolean;
  gender: Gender | null;
  gender_changed_at: string | null;
  avatar_id: number | null;
  avatar_name: string | null;
  registered_at: string;
  last_login_at: string | null;
  wallet: { coins_cents: number; tickets: number; points: number };
  stats: { last_active_day: string | null; active_days: number; current_streak: number; longest_streak: number };
  progress: { world_ordinal: number; world_name: string; chapter_ordinal: number; chapter_name: string } | null;
  counts: { logins: number; jobs_done: number; trainings_done: number; badges: number; referrals: number; sessions: number };
  referral_code: string | null;
  referred_by: { id: number; name: string } | null;
}

export interface LoginRow {
  id: number;
  user_id: number;
  name: string;
  logged_in_at: string;
  logged_out_at: string | null;
  ip: string | null;
  user_agent: string | null;
}

export interface LedgerRow {
  id: number;
  currency: 'coins' | 'tickets' | 'points';
  amount: number;
  balance_after: number;
  reason: string;
  created_at: string;
}

export interface AuditRow {
  id: number;
  action: string;
  admin: { id: number; name: string } | null;
  target: { id: number; name: string } | null;
  details: Record<string, unknown> | null;
  ip: string | null;
  created_at: string;
}

export interface Fact {
  id: number;
  text: string;
  active: boolean;
}

export interface DayCount {
  day: string;
  count: number;
}

export interface AdminStats {
  generated_at: string;
  accounts: {
    total: number;
    active: number;
    suspended: number;
    banned: number;
    unverified: number;
    admins: number;
    new_24h: number;
    new_7d: number;
    new_30d: number;
    tutorial_done: number;
    referred: number;
  };
  activity: {
    dau: number;
    wau: number;
    mau: number;
    online_now: number;
    logins_24h: number;
    logins_7d: number;
    avg_active_days: number;
    daily_claims_today: number;
    daily_claims_total: number;
  };
  economy: {
    coins_cents: number;
    tickets: number;
    points: number;
    jobs_done_24h: number;
    jobs_done_total: number;
    trainings_done_24h: number;
    trainings_done_total: number;
    badges_earned: number;
  };
  worlds: { world: number; players: number }[];
  registrations: DayCount[];
  logins: DayCount[];
}
