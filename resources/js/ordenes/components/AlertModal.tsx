type AlertModalProps = {
  open: boolean;
  title: string;
  message: string;
  onClose: () => void;
};

export function AlertModal({ open, title, message, onClose }: AlertModalProps) {
  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-[85] flex items-center justify-center bg-slate-950/70 p-4"
      role="dialog"
      aria-modal="true"
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
      onKeyDown={(e) => {
        if (e.key === 'Escape') onClose();
      }}
    >
      <div className="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div className="border-b border-slate-200 px-5 py-4">
          <h3 className="text-center text-xl font-bold text-blue-900">{title}</h3>
        </div>
        <div className="px-5 py-5">
          <p className="whitespace-pre-line text-center text-sm leading-6 text-slate-700">{message}</p>
        </div>
        <div className="flex justify-center border-t border-slate-200 px-5 py-4">
          <button
            type="button"
            className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700"
            onClick={onClose}
            autoFocus
          >
            Aceptar
          </button>
        </div>
      </div>
    </div>
  );
}
