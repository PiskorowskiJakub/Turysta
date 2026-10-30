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
