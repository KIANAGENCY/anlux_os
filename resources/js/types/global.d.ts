/// <reference types="vite/client" />

interface Window {
  ANLUX_CSRF_TOKEN?: string;
  ANLUX_BASE_URL?: string;
  ANLUX_WHATSAPP_ENABLED?: boolean;
  anluxShowAlert?: (message: string, options?: { title?: string; icon?: string }) => Promise<void> | void;
  anluxPermitirSalidaOrdenForm?: () => void;
}
