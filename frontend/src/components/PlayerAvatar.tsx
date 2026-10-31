import type { CSSProperties } from 'react';
import AvatarImage from './AvatarImage';

interface Props {
  src?: string | null;
  name: string;
  /** Srednica w px; ramka i awatar skaluja sie proporcjonalnie. */
  size?: number;
  /** Ramka awatara (klasa frame-*). Na razie jedna, w przyszlosci wybierana przez gracza. */
  frame?: string;
}

/** Awatar gracza z ramka: ten sam wyglad w profilu i na gorze strony, tylko w roznych rozmiarach. */
export default function PlayerAvatar({ src, name, size = 132, frame = 'default' }: Props) {
  return (
    <div className={`avatar-frame frame-${frame}`} style={{ '--s': `${size}px` } as CSSProperties}>
      <span className="avatar-frame-inner">
        <AvatarImage src={src} name={name} />
      </span>
    </div>
  );
}
