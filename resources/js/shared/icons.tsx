import type { ReactNode, SVGProps } from 'react';

type IconProps = SVGProps<SVGSVGElement> & { size?: number };

function IconBase({ size = 24, children, ...props }: IconProps & { children: ReactNode }) {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width={size}
      height={size}
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
      strokeWidth={1.5}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden={props['aria-hidden'] ?? true}
      {...props}
    >
      {children}
    </svg>
  );
}

export function IconClipboard({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M9 5h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
      <path d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" />
    </IconBase>
  );
}

export function IconPlus({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M12 5v14M5 12h14" />
    </IconBase>
  );
}

export function IconFilePdf({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M14 3v5h5" />
      <path d="M14 3H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8Z" />
      <path d="M9 13h1.5a1.5 1.5 0 0 1 0 3H9v2M13 13v5h2.5" />
    </IconBase>
  );
}

export function IconSignOut({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2" />
      <path d="M15 12H4m0 0 3-3m-3 3 3 3" />
    </IconBase>
  );
}

export function IconUser({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <circle cx="12" cy="8" r="3.25" />
      <path d="M5.5 19a6.5 6.5 0 0 1 13 0" />
    </IconBase>
  );
}

export function IconCheck({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M5 12.5 9.5 17 19 7.5" />
    </IconBase>
  );
}

export function IconWrench({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M14.5 6.5a3.5 3.5 0 0 1 4.2 4.2L12 17.4 8.6 14l6.7-6.7Z" />
      <path d="M8.6 14 5 17.6V19h1.4L10 15.4" />
    </IconBase>
  );
}

export function IconPackage({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M12 3 20 7.5v9L12 21 4 16.5v-9L12 3Z" />
      <path d="M12 12 20 7.5M12 12v9M12 12 4 7.5" />
    </IconBase>
  );
}

export function IconLock({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <rect x="5" y="11" width="14" height="10" rx="2" />
      <path d="M8 11V8a4 4 0 0 1 8 0v3" />
    </IconBase>
  );
}

export function IconMagnifyingGlass({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <circle cx="11" cy="11" r="6.5" />
      <path d="m16 16 4 4" />
    </IconBase>
  );
}

export function IconPencil({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M4 20h4L19 9l-4-4L4 16v4Z" />
      <path d="m12 8 4 4" />
    </IconBase>
  );
}

export function IconDownload({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M12 4v12m0 0 4-4m-4 4-4-4" />
      <path d="M5 20h14" />
    </IconBase>
  );
}

export function IconEye({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M3 12s3.5-7 9-7 9 7 9 7-3.5 7-9 7-9-7-9-7Z" />
      <circle cx="12" cy="12" r="2.5" />
    </IconBase>
  );
}

export function IconWarning({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M12 4 3 19h18L12 4Z" />
      <path d="M12 10v4M12 16.5v.5" />
    </IconBase>
  );
}

export function IconSort({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M8 6v12M8 18l-3-3M8 18l3-3M16 18V6m0 0-3 3m3-3 3 3" />
    </IconBase>
  );
}

export function IconCaretLeft({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="m14.5 5-7 7 7 7" />
    </IconBase>
  );
}

export function IconCaretRight({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="m9.5 5 7 7-7 7" />
    </IconBase>
  );
}

export function IconWhatsApp({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M19.5 12.2A7.5 7.5 0 0 1 7.4 18.6L5 19.5l.9-2.4A7.5 7.5 0 1 1 19.5 12.2Z" />
      <path d="M9.2 9.4c.2-.5.4-.5.7-.5h.5c.2 0 .4 0 .5.4.2.5.7 1.8.7 1.9s0 .3-.2.5-.4.5-.6.7 0 .4.2.6c.2.3.8 1.3 1.8 2.1 1.2 1 2.1 1.1 2.4 1.2s.5 0 .7-.2.7-.8.9-1.1.3-.4.5-.3.9.4 1.1.5.3.1.4.2 0 .7-.2 1.1c-.2.5-1.1 1-1.5 1.1s-.8.2-2.8-.6-3.7-2.9-4.2-3.9-.8-2.3-.8-2.7.3-1.1.6-1.5Z" />
    </IconBase>
  );
}

export function IconTruck({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M3 7h11v8H3V7Z" />
      <path d="M14 10h4l3 3v2h-7v-5Z" />
      <circle cx="7" cy="17" r="1.5" />
      <circle cx="17" cy="17" r="1.5" />
    </IconBase>
  );
}

export function IconFloppy({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M5 5h11l3 3v11H5V5Z" />
      <path d="M8 5v5h8V5M8 19v-5h8v5" />
    </IconBase>
  );
}

export function IconArrowLeft({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} {...props}>
      <path d="M19 12H5m0 0 6-6M5 12l6 6" />
    </IconBase>
  );
}

export function IconSpinner({ size, ...props }: IconProps) {
  return (
    <IconBase size={size} className={`animate-spin ${props.className || ''}`} {...props}>
      <path d="M12 4a8 8 0 1 1-8 8" />
    </IconBase>
  );
}
