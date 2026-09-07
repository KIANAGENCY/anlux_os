export type AccountStatus = 'online' | 'in_use' | 'offline' | 'inactive' | 'self';

export interface SwitchAccount {
  id: number;
  nombre: string;
  status: AccountStatus;
  status_label: string;
  selectable: boolean;
}

export interface NavBootstrapBase {
  csrf: string;
  logoUrl: string;
  logoutUrl: string;
  ordenesUrl: string;
  nombreTecnico: string;
  isImpersonating: boolean;
  canSwitchAccount: boolean;
  userId: number;
  accounts: SwitchAccount[];
  isAdmin: boolean;
  isTechnician: boolean;
}

export interface AdminNavLink {
  key: string;
  href: string;
  icon: string;
  label: string;
}

export interface NavAppBootstrap extends NavBootstrapBase {
  variant: 'app';
}

export interface NavAdminBootstrap extends NavBootstrapBase {
  variant: 'admin';
  navAdminActivo: string;
  adminLinks: AdminNavLink[];
}

export type NavBootstrap = NavAppBootstrap | NavAdminBootstrap;

export interface ImpersonationApiAccount extends SwitchAccount {
  hint?: string;
}

export interface PendingRequest {
  id: number;
  requester_nombre?: string;
}

export interface SolicitarResponse {
  success: boolean;
  token?: string;
  instant_apply?: boolean;
  can_apply?: boolean;
  message?: string;
}

export interface EstadoResponse {
  success: boolean;
  status?: 'pending' | 'approved' | 'denied' | 'expired' | 'invalid';
  can_apply?: boolean;
  message?: string;
}

export interface ApiSuccessResponse {
  success: boolean;
  message?: string;
}

export interface CuentasResponse {
  success: boolean;
  data?: SwitchAccount[];
  message?: string;
}

export interface PendientesResponse {
  success: boolean;
  data?: PendingRequest[];
}
