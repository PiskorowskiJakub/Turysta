export type Gender = 'f' | 'm' | 'x';

export interface Me {
  server_time: string;
  user: {
    id: number;
    email: string;
    character_name: string;
    avatar_id: number | null;
    gender: Gender | null;
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
