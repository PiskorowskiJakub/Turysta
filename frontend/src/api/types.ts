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
  progress: { world_id: number; chapter_id: number } | null;
  active: { training: unknown; job: unknown };
  notifications_unread: number;
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
  level: number;
  in_queue: number;
  max_level: number | null;
  next_level: number | null;
  next_cost_cents: number | null;
  next_duration_seconds: number | null;
}

export interface SkillsResponse {
  server_time: string;
  skills: SkillInfo[];
}

export interface TrainingItem {
  id: number;
  skill: { id: number; code: string; name: string };
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
