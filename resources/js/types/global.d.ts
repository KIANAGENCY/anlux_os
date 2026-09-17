/// <reference types="vite/client" />

interface Window {
  ANLUX_CSRF_TOKEN?: string;
  ANLUX_BASE_URL?: string;
  ANLUX_WHATSAPP_ENABLED?: boolean;
  __anluxLoggingOut?: boolean;
  __anluxRedirectingToLogin?: boolean;
  __anluxAuthDead?: boolean;
  __anluxCuentasAlertShown?: boolean;
  anluxShowAlert?: (message: string, options?: { title?: string; icon?: string }) => Promise<void> | void;
  anluxShowConfirm?: (message: string, options?: { title?: string; icon?: string }) => Promise<boolean> | boolean;
  anluxPermitirSalidaOrdenForm?: () => void;
}
