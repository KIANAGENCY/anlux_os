import { useEffect, useRef, useState, type FocusEvent, type KeyboardEvent } from 'react';
import { anluxMontoConIva, anluxMontoSinIvaDesdeTotal, anluxRound2 } from '../lib/iva';
import { registerNetoFlusher } from '../lib/netoFlush';

type Props = {
  /** Valor persistido: monto sin IVA. */
  value: string;
  disabled?: boolean;
  className?: string;
  placeholder?: string;
  fieldId?: string;
  onChangeSinIva: (sinIva: string) => void;
};

/**
 * Paridad Exacto: al enfocar se edita el neto c/IVA; al blur se convierte a sin IVA.
 */
export default function NetoIvaNumberInput({
  value,
  disabled,
  className = '',
  placeholder = 'Neto c/IVA',
  fieldId,
  onChangeSinIva,
}: Props) {
  const [editing, setEditing] = useState(false);
  const [display, setDisplay] = useState(value);
  const editingRef = useRef(false);
  const displayRef = useRef(value);
  const onChangeRef = useRef(onChangeSinIva);

  useEffect(() => {
    onChangeRef.current = onChangeSinIva;
  }, [onChangeSinIva]);

  useEffect(() => {
    if (!editingRef.current) {
      setDisplay(value);
      displayRef.current = value;
    }
  }, [value]);

  const commitFrom = (rawDisplay: string) => {
    if (!editingRef.current) return;
    const neto = parseFloat(rawDisplay);
    editingRef.current = false;
    setEditing(false);
    if (!Number.isFinite(neto) || rawDisplay.trim() === '') {
      onChangeRef.current('');
      setDisplay('');
      displayRef.current = '';
      return;
    }
    const sin = anluxMontoSinIvaDesdeTotal(neto);
    const sinStr = sin === 0 && neto === 0 ? '0' : String(anluxRound2(sin));
    onChangeRef.current(sinStr);
    setDisplay(sinStr);
    displayRef.current = sinStr;
  };

  useEffect(() => {
    return registerNetoFlusher(() => {
      if (editingRef.current) {
        commitFrom(displayRef.current);
      }
    });
  }, []);

  const titleFor = (sinIvaStr: string, netoStr: string, isEditing: boolean) => {
    const sin = Number(sinIvaStr) || 0;
    const neto = Number(netoStr) || 0;
    if (isEditing) {
      if (neto > 0) {
        return `Neto $${neto.toFixed(2)} → sin IVA $${anluxMontoSinIvaDesdeTotal(neto).toFixed(2)}`;
      }
      return 'Escribe el precio neto (con IVA). Al salir se convierte a sin IVA.';
    }
    if (sin > 0) {
      return `Sin IVA $${sin.toFixed(2)} (equivale a neto $${anluxMontoConIva(sin).toFixed(2)})`;
    }
    return 'Escribe el precio neto (con IVA). Al salir se convierte a sin IVA.';
  };

  const onFocus = (e: FocusEvent<HTMLInputElement>) => {
    editingRef.current = true;
    setEditing(true);
    const sin = Number(value) || 0;
    const neto = sin > 0 || String(value).trim() !== '' ? anluxMontoConIva(sin) : 0;
    const netoStr = String(value).trim() === '' ? '' : neto.toFixed(2);
    setDisplay(netoStr);
    displayRef.current = netoStr;
    window.setTimeout(() => {
      try {
        e.target.select();
      } catch {
        /* ignore */
      }
    }, 0);
  };

  const onKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      (e.target as HTMLInputElement).blur();
      return;
    }
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown' || e.key === 'e' || e.key === 'E' || e.key === '+') {
      e.preventDefault();
    }
  };

  return (
    <input
      type="number"
      step="0.01"
      className={className}
      disabled={disabled}
      value={display}
      placeholder={placeholder}
      title={titleFor(value, display, editing)}
      data-anlux-field={fieldId}
      onFocus={onFocus}
      onChange={(e) => {
        setDisplay(e.target.value);
        displayRef.current = e.target.value;
      }}
      onBlur={() => commitFrom(display)}
      onKeyDown={onKeyDown}
    />
  );
}
