import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';

type DialogIcon = 'success' | 'error' | 'warning' | 'info' | undefined;

export type AlertOptions = {
  title?: string;
  icon?: DialogIcon;
  confirmText?: string;
};

export type ConfirmOptions = {
  title?: string;
  icon?: DialogIcon;
  confirmText?: string;
  cancelText?: string;
};

export type PromptOptions = {
  title?: string;
  icon?: DialogIcon;
  confirmText?: string;
  cancelText?: string;
  defaultValue?: string;
  placeholder?: string;
};

type DialogState =
  | { kind: 'alert'; message: string; options: AlertOptions; resolve: () => void }
  | { kind: 'confirm'; message: string; options: ConfirmOptions; resolve: (value: boolean) => void }
  | { kind: 'prompt'; message: string; options: PromptOptions; resolve: (value: string | null) => void }
  | null;

export type DialogContextValue = {
  showAlert: (message: string, options?: AlertOptions) => Promise<void>;
  showConfirm: (message: string, options?: ConfirmOptions) => Promise<boolean>;
  showPrompt: (message: string, options?: PromptOptions) => Promise<string | null>;
};

const DialogContext = createContext<DialogContextValue | null>(null);

function iconClasses(icon: DialogIcon): { circle: string; icon: string } {
  if (icon === 'success') {
    return { circle: 'bg-emerald-100', icon: 'fas fa-check text-2xl text-emerald-600' };
  }
  if (icon === 'error') {
    return { circle: 'bg-red-100', icon: 'fas fa-times text-2xl text-red-600' };
  }
  if (icon === 'warning') {
    return { circle: 'bg-amber-100', icon: 'fas fa-exclamation text-2xl text-amber-600' };
  }
  return { circle: 'bg-blue-100', icon: 'fas fa-info-circle text-2xl text-blue-600' };
}

function DialogModal({ dialog, onClose }: { dialog: NonNullable<DialogState>; onClose: () => void }) {
  const isConfirm = dialog.kind === 'confirm';
  const isPrompt = dialog.kind === 'prompt';
  const title =
    dialog.options.title
    || (isPrompt ? 'Captura' : isConfirm ? 'Confirmar' : 'Aviso');
  const { circle, icon } = iconClasses(dialog.options.icon);
  const [promptValue, setPromptValue] = useState(
    isPrompt ? String(dialog.options.defaultValue || '') : '',
  );
  const inputRef = useRef<HTMLInputElement | null>(null);

  useEffect(() => {
    if (!isPrompt) return undefined;
    const t = window.setTimeout(() => inputRef.current?.focus(), 40);
    return () => window.clearTimeout(t);
  }, [isPrompt, dialog.message]);

  const finish = (value: boolean | string | null) => {
    if (dialog.kind === 'alert') {
      dialog.resolve();
    } else if (dialog.kind === 'confirm') {
      dialog.resolve(Boolean(value));
    } else {
      dialog.resolve(typeof value === 'string' ? value : null);
    }
    onClose();
  };

  return (
    <div
      className="fixed inset-0 z-[30000] flex items-center justify-center bg-slate-950/70 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="anlux-dialog-title"
      onClick={(e) => {
        if (e.target === e.currentTarget) {
          finish(isConfirm || isPrompt ? (isPrompt ? null : false) : true);
        }
      }}
      onKeyDown={(e) => {
        if (e.key === 'Escape') {
          finish(isConfirm || isPrompt ? (isPrompt ? null : false) : true);
        }
      }}
    >
      <div className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div className="border-b border-slate-200 bg-gradient-to-r from-blue-800 to-blue-600 px-5 py-4">
          <h3 id="anlux-dialog-title" className="text-center text-lg font-bold text-white sm:text-xl">
            {title}
          </h3>
        </div>
        <div className="px-5 py-5">
          {dialog.options.icon ? (
            <div className="mb-4 flex justify-center">
              <div className={`flex h-14 w-14 items-center justify-center rounded-full ${circle}`}>
                <i className={icon} aria-hidden="true" />
              </div>
            </div>
          ) : null}
          <p className="whitespace-pre-line text-center text-sm leading-6 text-slate-700">{dialog.message}</p>
          {isPrompt ? (
            <input
              ref={inputRef}
              type="text"
              value={promptValue}
              onChange={(e) => setPromptValue(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  e.preventDefault();
                  finish(promptValue);
                }
              }}
              placeholder={dialog.options.placeholder || ''}
              className="mt-4 w-full rounded-lg border-2 border-blue-300 px-3 py-2.5 text-sm font-semibold uppercase text-blue-950 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300"
              autoComplete="off"
            />
          ) : null}
        </div>
        <div className={`flex gap-2 border-t border-slate-200 px-5 py-4 ${isConfirm || isPrompt ? 'justify-end' : 'justify-center'}`}>
          {isConfirm || isPrompt ? (
            <button
              type="button"
              className="rounded-lg border-2 border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              onClick={() => finish(isPrompt ? null : false)}
            >
              {dialog.options.cancelText || 'Cancelar'}
            </button>
          ) : null}
          <button
            type="button"
            className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700"
            onClick={() => finish(isPrompt ? promptValue : true)}
            autoFocus={!isPrompt}
          >
            {dialog.options.confirmText || (isConfirm || isPrompt ? 'Continuar' : 'Aceptar')}
          </button>
        </div>
      </div>
    </div>
  );
}

export function DialogProvider({ children }: { children: ReactNode }) {
  const [dialog, setDialog] = useState<DialogState>(null);

  const showAlert = useCallback((message: string, options: AlertOptions = {}) => {
    return new Promise<void>((resolve) => {
      setDialog({ kind: 'alert', message, options, resolve });
    });
  }, []);

  const showConfirm = useCallback((message: string, options: ConfirmOptions = {}) => {
    return new Promise<boolean>((resolve) => {
      setDialog({ kind: 'confirm', message, options, resolve });
    });
  }, []);

  const showPrompt = useCallback((message: string, options: PromptOptions = {}) => {
    return new Promise<string | null>((resolve) => {
      setDialog({ kind: 'prompt', message, options, resolve });
    });
  }, []);

  const value = useMemo(
    () => ({ showAlert, showConfirm, showPrompt }),
    [showAlert, showConfirm, showPrompt],
  );

  return (
    <DialogContext.Provider value={value}>
      {children}
      {dialog ? <DialogModal dialog={dialog} onClose={() => setDialog(null)} /> : null}
    </DialogContext.Provider>
  );
}

export function useAnluxDialog(): DialogContextValue {
  const ctx = useContext(DialogContext);
  if (!ctx) {
    return {
      showAlert: async (message: string) => {
        window.alert(message);
      },
      showConfirm: async (message: string) => window.confirm(message),
      showPrompt: async (message: string, options?: PromptOptions) =>
        window.prompt(message, options?.defaultValue ?? ''),
    };
  }
  return ctx;
}

type LoadingOverlayProps = {
  open: boolean;
  message: string;
};

export function LoadingOverlay({ open, message }: LoadingOverlayProps) {
  if (!open) return null;

  return (
    <div
      id="anluxImpersonationLoading"
      className="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/75 p-4"
      role="alertdialog"
      aria-live="polite"
    >
      <div className="w-full max-w-xs rounded-2xl bg-white px-6 py-7 text-center shadow-2xl">
        <div
          className="mx-auto mb-3 h-12 w-12 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"
          aria-hidden="true"
        />
        <p id="anluxImpersonationLoadingMsg" className="text-sm font-semibold text-blue-900">
          {message || 'Procesando…'}
        </p>
      </div>
    </div>
  );
}
