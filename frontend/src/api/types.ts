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
    name_change_available_at: string | null;
    role: string;
    email_verified: boolean;
    email_verification_deadline: string | null;
    tutorial_done: boolean;
    share_activity: boolean;
    show_squad_title: boolean;
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
  accept_terms: boolean;
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
  locked_until?: string | null;
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
  friendship: FriendshipState;
  squad: { id: number; display_name: string; title: string | null } | null;
}

export type FriendshipState = 'self' | 'none' | 'friends' | 'outgoing' | 'incoming';

export interface FriendPerson {
  id: number;
  name: string;
  avatar_url: string | null;
}

export interface Friend extends FriendPerson {
  online: boolean;
  coins_cents: number;
  points: number;
}

export interface ComparePerson {
  id: number;
  name: string;
  avatar_url: string | null;
  rank: number;
  points: number;
  coins_cents: number;
  tickets: number;
  world: number;
  chapter: number;
  skill_levels: number;
  badges: number;
  account_days: number;
  active_days: number;
  current_streak: number;
  longest_streak: number;
  earned_cents: number;
}

export interface CompareResponse {
  me: ComparePerson;
  friend: ComparePerson;
  jobs: { code: string; name: string; me: number; friend: number }[];
  skills: { code: string; name: string; world: number | null; me: number; friend: number }[];
}

export type GiftStatus = 'ok' | 'sent' | 'early' | 'limit';

export interface GiftState {
  cents: number;
  base_cents: number;
  multiplier_permille: number;
  active_referrals: number;
  bonus_cents: number;
  totals: { sent_count: number; sent_cents: number; received_count: number; received_cents: number };
  sent_left: number;
  max_per_day: number;
  min_friend_hours: number;
  per_friend: Record<string, { status: GiftStatus; available_at: string | null }>;
}

export interface FriendRankingRow {
  id: number;
  rank: number;
  name: string;
  /** Zdobyte w okresie (punkty albo setne dukata, zaleznie od miary). */
  gained: number;
  /** Aktualny stan (punkty albo setne dukata). */
  total: number;
  me: boolean;
}

export interface FriendRanking {
  period: 'week' | 'month' | 'all';
  metric: 'points' | 'coins';
  offset: number;
  max_offset: number;
  from: string | null;
  to: string | null;
  rows: FriendRankingRow[];
}

export interface ActivityEvent {
  id: number;
  type: 'badge' | 'world';
  user: { id: number; name: string };
  payload: { group?: string | null; tier?: string; world?: number | null; badge_name?: string };
  created_at: string;
}

export interface ActivityFeedResponse {
  enabled: boolean;
  events: ActivityEvent[];
}

export interface FriendsResponse {
  friends: Friend[];
  gifts: GiftState;
  incoming: FriendPerson[];
  outgoing: FriendPerson[];
  limits: { max_friends: number; max_pending: number; online_minutes: number };
}

export interface Announcement {
  id: number;
  title: string;
  body: string;
  published: boolean;
  pinned: boolean;
  update_id: number | null;
  created_at: string;
  updated_at: string;
}

export interface UpdateEntry {
  id: number;
  title: string;
  body: string;
  released_on: string;
  published: boolean;
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
  referrals: ReferralStats;
}

export interface ReferralStats {
  registered: number;
  active: number;
  active_days_required: number;
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

export interface BugReportRow {
  id: number;
  user: { id: number; name: string };
  message: string;
  page: string | null;
  user_agent: string | null;
  status: 'new' | 'in_progress' | 'resolved';
  created_at: string;
  updated_at: string;
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
  too_long: boolean;
}

export interface DayCount {
  day: string;
  count: number;
}

export interface ActivityPoint {
  key: string;
  active: number;
  logins: number;
  registrations: number;
}

export interface ActivitySeries {
  view: 'hours' | 'days';
  span: number;
  tracking_since: string | null;
  points: ActivityPoint[];
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
    gifts_total: number;
    gifts_24h: number;
    gifts_cents_total: number;
  };
  worlds: { world: number; players: number }[];
  registrations: DayCount[];
  logins: DayCount[];
}

// ---- Oddzialy ----

export type SquadJoinMode = 'open' | 'request' | 'invite';
export type SquadRole = 'leader' | 'deputy' | 'guide' | 'quartermaster' | 'chronicler' | 'recruiter' | 'member';

export interface SquadSpec {
  code: string;
  group: string;
  label: string;
}

export interface SquadMeta {
  specializations: SquadSpec[];
  roles: SquadRole[];
  assignable_roles: SquadRole[];
  kick_reasons: string[];
  report_reasons: string[];
  limits: {
    max_members: number;
    min_account_days: number;
    rejoin_cooldown_days: number;
    name_min: number;
    name_max: number;
    name_change_days: number;
    spec_change_days: number;
    recruit_days: number;
    fire_threshold_percent: number;
    fire_min_active: number;
    flame_streaks: number[];
  };
}

export interface SquadCard {
  id: number;
  name: string;
  display_name: string;
  specialization: string;
  join_mode: SquadJoinMode;
  members: number;
  max_members: number;
  flame_level: number;
}

export interface SquadMember {
  id: number;
  name: string;
  avatar_url: string | null;
  title: SquadRole | null;
  joined_at: string;
  recruit: boolean;
  /** Pola tylko dla czlonkow oddzialu. */
  role?: SquadRole;
  online?: boolean;
}

export interface SquadEligibility {
  ok: boolean;
  reason: 'already_member' | 'email_unverified' | 'account_too_young' | 'squad_cooldown' | null;
  until: string | null;
}

export interface SquadFireState {
  level: number;
  streak: number;
  best_streak: number;
  lit_days: number;
  ember: number;
  next_level: { level: number; in_days: number } | null;
  today: { day: string; members: number; active: number; needed: number; lit: boolean };
  history: { day: string; members: number; active: number; needed: number; lit: boolean; ember: number }[];
}

export interface SquadChronicleEntry {
  id: number;
  type: string;
  created_at: string;
  user?: { id: number | null; name: string | null };
  target?: { id: number | null; name: string | null };
  payload: Record<string, string | number | boolean>;
}

export interface SquadPerson {
  id: number;
  name: string;
  created_at: string;
}

export interface SquadProfile extends SquadCard {
  created_at: string;
  name_locked: boolean;
  name_forced: boolean;
  badges: unknown[];
  member_list: SquadMember[];
  me: {
    is_member: boolean;
    role: SquadRole | null;
    can_manage: boolean;
    can_recruit: boolean;
    is_leader: boolean;
    eligibility: SquadEligibility | null;
    pending: 'request' | 'invite' | null;
  };
  name_change_available_at?: string | null;
  spec_change_available_at?: string | null;
  fire?: SquadFireState;
  chronicle?: SquadChronicleEntry[];
  requests?: { requests: SquadPerson[]; invites: SquadPerson[] };
}

export interface SquadMine {
  squad: SquadProfile | null;
  invites: SquadCard[];
  requests: SquadCard[];
  eligibility: SquadEligibility;
  meta: SquadMeta;
}

export interface SquadCatalog {
  squads: SquadCard[];
  total: number;
  page: number;
  pages: number;
  meta: SquadMeta;
}

export interface AdminSquadRow {
  id: number;
  name: string;
  display_name: string;
  specialization: string;
  members: number;
  ember: number;
  flame_level: number;
  reports: number;
  name_locked: boolean;
  name_forced: boolean;
  archived: boolean;
  created_at: string;
}

export interface AdminSquadReport {
  id: number;
  squad_id: number;
  reported_name: string;
  current_name: string;
  display_name: string;
  archived: boolean;
  reason: string;
  reporter: string;
  status: 'new' | 'resolved' | 'dismissed';
  created_at: string;
}

export interface AdminSquadDetail {
  id: number;
  name: string;
  display_name: string;
  specialization: string;
  join_mode: SquadJoinMode;
  ember: number;
  flame_streak: number;
  name_locked: boolean;
  name_forced: boolean;
  archived: boolean;
  created_at: string;
  members: { id: number; name: string; role: SquadRole; joined_at: string }[];
  name_history: { id: number; name: string; by: string | null; created_at: string }[];
  reports: AdminSquadReport[];
}

export type TrailDifficulty = 'easy' | 'medium' | 'hard';

export interface SquadTrailParticipant {
  user_id: number;
  name: string;
  joined_day: string;
  active_days: number;
  march_days: number;
  km: number;
  eligible: boolean;
  is_me: boolean;
}

export interface SquadTrail {
  id: number;
  week_start: string;
  week_end: string;
  join_until: string;
  plan_until: string;
  difficulty: TrailDifficulty;
  theme: { code: string; name: string; description: string };
  length_km: number;
  percent: number;
  km: number;
  goal_km: number;
  my_km: number;
  thresholds: number[];
  max_stamps: number;
  stamps_now: number;
  min_eligible: number;
  min_active_days: number;
  fee_cents: number;
  reward_per_stamp_cents: number;
  joined: boolean;
  can_join: boolean;
  can_march: boolean;
  marched_today: boolean;
  can_plan: boolean;
  participants: SquadTrailParticipant[];
  squad_stamps: number;
}

export interface SquadTrailHistoryItem {
  id: number;
  week_start: string;
  difficulty: TrailDifficulty;
  theme: { code: string; name: string; description: string };
  length_km: number;
  percent: number;
  stamps: number;
  participants: number;
  eligible: number;
  joined: boolean;
  my_reward_cents: number;
  my_eligible: boolean;
}
