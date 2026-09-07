import { useEffect, useMemo, useState } from 'react';
import { readPageProps } from '../../shared/http';

type Colors = { primary: string; secondary: string; accent: string; background: string; surface: string; text: string };
type Font = { label: string; css: string; pdf: string };
type Props = {
  appearance: { colors: Colors; font: string; logo_path: string };
  fonts: Record<string, Font>; logoUrl: string; csrf: string; status: string | null;
  errors: Record<string, string[]>; actions: { update: string; reset: string };
};

const labels: Record<keyof Colors, string> = {
  primary: 'Primario', secondary: 'Secundario', accent: 'Acento',
  background: 'Fondo', surface: 'Superficie', text: 'Texto',
};
const presets: Record<string, Colors> = {
  Anlux: { primary: '#2563EB', secondary: '#1E3A8A', accent: '#06B6D4', background: '#F8FAFC', surface: '#FFFFFF', text: '#0F172A' },
  Océano: { primary: '#0369A1', secondary: '#0C4A6E', accent: '#0D9488', background: '#F0F9FF', surface: '#FFFFFF', text: '#082F49' },
  Esmeralda: { primary: '#047857', secondary: '#064E3B', accent: '#CA8A04', background: '#F0FDF4', surface: '#FFFFFF', text: '#052E16' },
  Vino: { primary: '#9F1239', secondary: '#4C0519', accent: '#C2410C', background: '#FFF7ED', surface: '#FFFFFF', text: '#431407' },
};

export default function App() {
  const props = readPageProps<Props>();
  const [colors, setColors] = useState<Colors>(props?.appearance.colors || presets.Anlux);
  const [font, setFont] = useState(props?.appearance.font || 'system');
  const [previewLogo, setPreviewLogo] = useState(props?.logoUrl || '');
  const [objectUrl, setObjectUrl] = useState<string | null>(null);
  useEffect(() => () => { if (objectUrl) URL.revokeObjectURL(objectUrl); }, [objectUrl]);
  const fontCss = useMemo(() => props?.fonts[font]?.css || 'sans-serif', [font, props]);
  if (!props) return <p className="p-4 text-red-700">No se cargó la apariencia.</p>;

  const chooseLogo = (file?: File) => {
    if (!file) return;
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    const url = URL.createObjectURL(file);
    setObjectUrl(url);
    setPreviewLogo(url);
  };

  return (
    <div className="anlux-page-card">
      <header className="anlux-page-header">
        <div><p className="anlux-eyebrow">Administración · Marca</p><h1 className="anlux-page-title">Apariencia</h1><p className="anlux-page-description">Personaliza colores, tipografía y logo en web, correos y PDF.</p></div>
      </header>
      <div className="p-4 sm:p-6">
        {props.status ? <div className="mb-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm font-semibold text-green-800">{props.status}</div> : null}
        <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(340px,.8fr)]">
          <form method="POST" action={props.actions.update} encType="multipart/form-data" className="space-y-5">
            <input type="hidden" name="_token" value={props.csrf} /><input type="hidden" name="_method" value="PUT" />
            <section className="anlux-panel">
              <h2 className="text-lg font-bold text-slate-900">Paleta</h2>
              <div className="mt-3 flex flex-wrap gap-2">{Object.entries(presets).map(([name, value]) => <button key={name} type="button" className="anlux-btn-secondary min-h-9 px-3 py-1 text-xs" onClick={() => setColors(value)}>{name}</button>)}</div>
              <div className="mt-4 grid gap-4 sm:grid-cols-2">
                {(Object.keys(labels) as (keyof Colors)[]).map((key) => (
                  <div key={key}>
                    <label className="anlux-label">{labels[key]}</label>
                    <div className="flex gap-2"><input type="color" value={colors[key]} onChange={(e) => setColors({ ...colors, [key]: e.target.value.toUpperCase() })} className="h-11 w-14 rounded border border-slate-300 bg-white p-1" /><input name={`colors[${key}]`} value={colors[key]} onChange={(e) => setColors({ ...colors, [key]: e.target.value.toUpperCase() })} pattern="^#[0-9A-Fa-f]{6}$" className="anlux-control min-w-0 flex-1 font-mono uppercase" /></div>
                    {props.errors[`colors.${key}`]?.[0] ? <p className="mt-1 text-xs font-semibold text-red-600">{props.errors[`colors.${key}`][0]}</p> : null}
                  </div>
                ))}
              </div>
            </section>
            <section className="anlux-panel">
              <h2 className="text-lg font-bold text-slate-900">Tipografía</h2>
              <select name="font" value={font} onChange={(e) => setFont(e.target.value)} className="anlux-control mt-3 w-full">{Object.entries(props.fonts).map(([key, item]) => <option key={key} value={key}>{item.label}</option>)}</select>
              <p className="mt-2 text-xs text-slate-500">Correos y PDF usan una alternativa compatible cuando el visor no dispone de la fuente.</p>
            </section>
            <section className="anlux-panel">
              <h2 className="text-lg font-bold text-slate-900">Logo</h2>
              <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" onChange={(e) => chooseLogo(e.target.files?.[0])} className="mt-3 block w-full text-sm" />
              <p className="mt-2 text-xs text-slate-500">PNG, JPEG o WebP; máximo 2 MB. Recomendado: fondo transparente y mínimo 300 px de ancho.</p>
              {props.errors.logo?.[0] ? <p className="mt-1 text-xs font-semibold text-red-600">{props.errors.logo[0]}</p> : null}
              <label className="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_logo" value="1" /> Volver al logo Anlux original</label>
            </section>
            <div className="flex flex-wrap justify-end gap-3"><button type="submit" className="anlux-btn-primary"><i className="fas fa-save" />Guardar apariencia</button></div>
          </form>

          <aside className="lg:sticky lg:top-4 lg:self-start">
            <div className="overflow-hidden rounded-2xl border shadow-sm" style={{ background: colors.background, color: colors.text, fontFamily: fontCss, borderColor: colors.secondary }}>
              <div className="p-5" style={{ background: colors.surface }}>
                {previewLogo ? <img src={previewLogo} alt="Vista previa del logo" className="mb-5 h-16 max-w-full object-contain object-left" /> : null}
                <p className="text-xs font-bold uppercase tracking-wider" style={{ color: colors.primary }}>Vista previa</p>
                <h2 className="mt-1 text-2xl font-bold">Orden de servicio</h2>
                <p className="mt-2 text-sm opacity-75">Así se verá la identidad principal del sistema.</p>
                <div className="mt-5 rounded-xl border p-4" style={{ borderColor: colors.accent, background: colors.background }}>
                  <p className="font-bold">Panel de ejemplo</p><p className="mt-1 text-sm">Superficies, texto y acentos se adaptan juntos.</p>
                </div>
                <div className="mt-5 flex gap-2"><span className="rounded-lg px-4 py-2 text-sm font-bold text-white" style={{ background: colors.primary }}>Acción principal</span><span className="rounded-lg px-4 py-2 text-sm font-bold text-white" style={{ background: colors.secondary }}>Secundaria</span></div>
              </div>
              <div className="h-3" style={{ background: colors.accent }} />
            </div>
            <form method="POST" action={props.actions.reset} className="mt-4 text-right" onSubmit={(e) => { if (!window.confirm('¿Restablecer colores, tipografía y logo originales?')) e.preventDefault(); }}>
              <input type="hidden" name="_token" value={props.csrf} /><input type="hidden" name="_method" value="DELETE" />
              <button type="submit" className="anlux-btn-danger">Restablecer valores Anlux</button>
            </form>
          </aside>
        </div>
      </div>
    </div>
  );
}
