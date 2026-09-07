import {
  forwardRef,
  useCallback,
  useEffect,
  useImperativeHandle,
  useRef,
  type PointerEvent as ReactPointerEvent,
} from 'react';
import type { SignaturePadHandle } from '../types';

type Props = {
  disabled?: boolean;
  className?: string;
  label?: string;
  /** Altura mínima del canvas (px). Default 150; salida temporal usa ~88. */
  minHeight?: number;
  hideClear?: boolean;
  onDirty?: () => void;
};

function pintarBlanco(canvas: HTMLCanvasElement, ctx: CanvasRenderingContext2D): void {
  ctx.save();
  ctx.setTransform(1, 0, 0, 1, 0, 0);
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.restore();
}

const SignaturePad = forwardRef<SignaturePadHandle, Props>(function SignaturePad(
  { disabled = false, className = '', label, minHeight = 150, hideClear = false, onDirty },
  ref,
) {
  const wrapRef = useRef<HTMLDivElement | null>(null);
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const ctxRef = useRef<CanvasRenderingContext2D | null>(null);
  const drawingRef = useRef(false);
  const strokeRef = useRef(false);
  const scrollLockRef = useRef(0);
  const onDirtyRef = useRef(onDirty);
  const minH = Math.max(40, minHeight);

  useEffect(() => {
    onDirtyRef.current = onDirty;
  }, [onDirty]);

  const lockPageScroll = useCallback(() => {
    scrollLockRef.current += 1;
    if (scrollLockRef.current === 1) {
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';
      document.body.style.touchAction = 'none';
    }
  }, []);

  const unlockPageScroll = useCallback(() => {
    scrollLockRef.current = Math.max(0, scrollLockRef.current - 1);
    if (scrollLockRef.current === 0) {
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
      document.body.style.touchAction = '';
    }
  }, []);

  const syncSize = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const parent = canvas.parentElement;
    const rect = parent?.getBoundingClientRect();
    const w = Math.max(1, Math.round(rect?.width || canvas.clientWidth || 320));
    const h = Math.max(minH, Math.round(rect?.height || minH));
    const prev = canvas.toDataURL('image/png');
    const hadStroke = strokeRef.current;
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctxRef.current = ctx;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.lineWidth = 2.2;
    ctx.strokeStyle = '#0f172a';
    pintarBlanco(canvas, ctx);
    if (hadStroke && prev) {
      const img = new Image();
      img.onload = () => {
        ctx.drawImage(img, 0, 0, w, h);
      };
      img.src = prev;
    }
  }, [minH]);

  useEffect(() => {
    syncSize();
    const onResize = () => syncSize();
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, [syncSize]);

  // iOS/Android: touchmove pasivo en React no siempre bloquea el scroll; listeners nativos no-pasivos sí.
  useEffect(() => {
    const canvas = canvasRef.current;
    const wrap = wrapRef.current;
    if (!canvas) return undefined;

    const blockScroll = (e: TouchEvent) => {
      if (disabled) return;
      e.preventDefault();
    };

    const opts: AddEventListenerOptions = { passive: false };
    canvas.addEventListener('touchstart', blockScroll, opts);
    canvas.addEventListener('touchmove', blockScroll, opts);
    canvas.addEventListener('touchend', blockScroll, opts);
    wrap?.addEventListener('touchstart', blockScroll, opts);
    wrap?.addEventListener('touchmove', blockScroll, opts);

    return () => {
      canvas.removeEventListener('touchstart', blockScroll, opts);
      canvas.removeEventListener('touchmove', blockScroll, opts);
      canvas.removeEventListener('touchend', blockScroll, opts);
      wrap?.removeEventListener('touchstart', blockScroll, opts);
      wrap?.removeEventListener('touchmove', blockScroll, opts);
      if (drawingRef.current) {
        drawingRef.current = false;
        unlockPageScroll();
      }
    };
  }, [disabled, unlockPageScroll]);

  const pos = (e: ReactPointerEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    if (!canvas) return { x: 0, y: 0 };
    const r = canvas.getBoundingClientRect();
    return {
      x: ((e.clientX - r.left) / Math.max(r.width, 1)) * canvas.width,
      y: ((e.clientY - r.top) / Math.max(r.height, 1)) * canvas.height,
    };
  };

  const onPointerDown = (e: ReactPointerEvent<HTMLCanvasElement>) => {
    if (disabled) return;
    e.preventDefault();
    e.stopPropagation();
    const ctx = ctxRef.current;
    const canvas = canvasRef.current;
    if (!ctx || !canvas) return;
    canvas.setPointerCapture(e.pointerId);
    if (!drawingRef.current) {
      drawingRef.current = true;
      lockPageScroll();
    }
    const p = pos(e);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
  };

  const onPointerMove = (e: ReactPointerEvent<HTMLCanvasElement>) => {
    if (disabled || !drawingRef.current) return;
    e.preventDefault();
    e.stopPropagation();
    const ctx = ctxRef.current;
    if (!ctx) return;
    const p = pos(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    strokeRef.current = true;
  };

  const endStroke = (e: ReactPointerEvent<HTMLCanvasElement>) => {
    if (!drawingRef.current) return;
    e.preventDefault();
    drawingRef.current = false;
    unlockPageScroll();
    try {
      canvasRef.current?.releasePointerCapture(e.pointerId);
    } catch {
      /* ignore */
    }
    if (strokeRef.current) {
      onDirtyRef.current?.();
    }
  };

  useImperativeHandle(ref, () => ({
    clear() {
      const canvas = canvasRef.current;
      const ctx = ctxRef.current;
      if (!canvas || !ctx) return;
      pintarBlanco(canvas, ctx);
      strokeRef.current = false;
      onDirtyRef.current?.();
    },
    async loadFromDataUrl(dataUrl) {
      const canvas = canvasRef.current;
      const ctx = ctxRef.current;
      if (!canvas || !ctx || !dataUrl) return;
      await new Promise<void>((resolve) => {
        const img = new Image();
        img.onload = () => {
          pintarBlanco(canvas, ctx);
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          strokeRef.current = true;
          resolve();
        };
        img.onerror = () => resolve();
        img.src = dataUrl;
      });
    },
    getDataUrl() {
      const canvas = canvasRef.current;
      return canvas ? canvas.toDataURL('image/png') : '';
    },
    hasStroke() {
      return strokeRef.current;
    },
  }));

  return (
    <div className={className}>
      {label ? <p className="mb-2 text-center text-sm font-bold text-blue-900 sm:text-lg">{label}</p> : null}
      <div
        ref={wrapRef}
        className="anlux-signature-pad overflow-hidden rounded-lg border-2 border-blue-400 bg-white"
        style={{
          height: minH,
          minHeight: minH,
          backgroundColor: 'rgba(255,255,255,0.96)',
          touchAction: 'none',
          overscrollBehavior: 'none',
          WebkitUserSelect: 'none',
          userSelect: 'none',
        }}
      >
        <canvas
          ref={canvasRef}
          className={`h-full w-full rounded-lg ${disabled ? 'cursor-not-allowed opacity-70' : 'cursor-crosshair'}`}
          style={{
            display: 'block',
            background: 'white',
            touchAction: 'none',
            overscrollBehavior: 'none',
            height: '100%',
            width: '100%',
            WebkitUserSelect: 'none',
            userSelect: 'none',
          }}
          onPointerDown={onPointerDown}
          onPointerMove={onPointerMove}
          onPointerUp={endStroke}
          onPointerCancel={endStroke}
        />
      </div>
      {hideClear ? null : (
        <button
          type="button"
          disabled={disabled}
          onClick={() => {
            const canvas = canvasRef.current;
            const ctx = ctxRef.current;
            if (!canvas || !ctx) return;
            pintarBlanco(canvas, ctx);
            strokeRef.current = false;
            onDirtyRef.current?.();
          }}
          className="mt-2 rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <i className="fas fa-eraser mr-2" />
          Limpiar
        </button>
      )}
    </div>
  );
});

export default SignaturePad;
