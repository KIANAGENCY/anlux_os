import { readPageProps } from '../../shared/http';

type MailSettings = {
  enabled: boolean; host: string; port: number; scheme: string; username: string;
  from_address: string; from_name: string; has_password: boolean;
};
type WhatsappSettings = {
  enabled: boolean; base_url: string; graph_version: string; phone_number_id: string;
  language: string; default_country_code: string; template_include_document: boolean;
  webhook_verify_signature: boolean; templates: Record<string, string>;
  has_access_token: boolean; has_verify_token: boolean; has_app_secret: boolean;
};
type Props = {
  settings: { app_url: string; source: string; updated_at?: string | null; mail: MailSettings; whatsapp: WhatsappSettings; tests: Record<string, { status: string; message: string; tested_at: string }> };
  csrf: string; status: string | null; error: string | null; errors: Record<string, string[]>;
  actions: { update: string; testMail: string; testWhatsapp: string }; webhookUrl: string;
};

function StatusBadge({ enabled, configured, test }: { enabled: boolean; configured: boolean; test?: { status: string; message: string } }) {
  const status = !enabled ? 'Inactivo' : test?.status === 'tested' ? 'Probado' : test?.status === 'error' ? 'Error' : configured ? 'Configurado' : 'Incompleto';
  const classes = status === 'Probado' ? 'border-green-200 bg-green-50 text-green-800' : status === 'Error' || status === 'Incompleto' ? 'border-red-200 bg-red-50 text-red-800' : 'border-slate-200 bg-slate-50 text-slate-700';
  return <span title={test?.message || status} className={`rounded-full border px-3 py-1 text-xs font-bold ${classes}`}>{status}</span>;
}

function FieldError({ name, errors }: { name: string; errors: Record<string, string[]> }) {
  const message = errors[name]?.[0];
  return message ? <p className="mt-1 text-xs font-semibold text-red-600">{message}</p> : null;
}

function SecretField({ name, removeName, label, configured, errors }: { name: string; removeName: string; label: string; configured: boolean; errors: Record<string, string[]> }) {
  return (
    <div>
      <label className="anlux-label">{label}</label>
      <input type="password" name={name} autoComplete="new-password" className="anlux-control w-full" placeholder={configured ? '•••••••• (dejar vacío para conservar)' : 'Captura el secreto'} />
      <label className="mt-2 flex items-center gap-2 text-xs text-slate-600">
        <input type="checkbox" name={removeName} value="1" /> Eliminar valor guardado
      </label>
      <FieldError name={name.replace('[', '.').replace(']', '')} errors={errors} />
    </div>
  );
}

export default function App() {
  const props = readPageProps<Props>();
  if (!props) return <p className="p-4 text-red-700">No se cargó la configuración.</p>;
  const { settings: s, errors } = props;
  const Csrf = () => <input type="hidden" name="_token" value={props.csrf} />;
  const mailConfigured = Boolean(s.mail.host && s.mail.from_address && (!s.mail.username || s.mail.has_password));
  const whatsappConfigured = Boolean(s.whatsapp.phone_number_id && s.whatsapp.has_access_token && s.whatsapp.has_verify_token
    && s.whatsapp.templates.recepcion && s.whatsapp.templates.terminado && s.whatsapp.templates.entregado);

  return (
    <div className="anlux-page-card">
      <header className="anlux-page-header">
        <div>
          <p className="anlux-eyebrow">Administración · Integraciones</p>
          <h1 className="anlux-page-title">Dominio y comunicaciones</h1>
          <p className="anlux-page-description">Configura la URL pública, el correo SMTP y WhatsApp Cloud sin editar el archivo .env.</p>
        </div>
        <span className="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-800">
          Fuente actual: {s.source === 'panel' ? 'Panel' : '.env'}
        </span>
      </header>
      <div className="space-y-6 p-4 sm:p-6">
        {props.status ? <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm font-semibold text-green-800">{props.status}</div> : null}
        {props.error ? <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800">{props.error}</div> : null}

        <form method="POST" action={props.actions.update} className="space-y-6">
          <Csrf /><input type="hidden" name="_method" value="PUT" />
          <section className="anlux-panel">
            <h2 className="text-lg font-bold text-slate-900"><i className="fas fa-globe mr-2 text-blue-600" />Dominio público</h2>
            <p className="mt-1 text-sm text-slate-600">Esto controla enlaces firmados y callbacks. El DNS y el certificado SSL se configuran en tu proveedor de hosting.</p>
            <label className="anlux-label mt-4">URL canónica</label>
            <input name="app_url" type="url" required defaultValue={s.app_url} className="anlux-control w-full" placeholder="https://soporte.ejemplo.com" />
            <FieldError name="app_url" errors={errors} />
            <div className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
              <p className="text-xs font-bold uppercase text-slate-500">Callback para Meta</p>
              <code className="mt-1 block break-all text-sm text-slate-800">{props.webhookUrl}</code>
            </div>
          </section>

          <section className="anlux-panel">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div><h2 className="text-lg font-bold text-slate-900"><i className="fas fa-envelope mr-2 text-blue-600" />Correo SMTP</h2><p className="text-sm text-slate-600">Servidor saliente para notificaciones de órdenes.</p></div>
              <div className="flex items-center gap-3"><StatusBadge enabled={s.mail.enabled} configured={mailConfigured} test={s.tests.mail} /><label className="flex items-center gap-2 font-semibold"><input type="hidden" name="mail[enabled]" value="0" /><input type="checkbox" name="mail[enabled]" value="1" defaultChecked={s.mail.enabled} /> Activar SMTP</label></div>
            </div>
            <div className="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              <div><label className="anlux-label">Host</label><input name="mail[host]" defaultValue={s.mail.host} className="anlux-control w-full" placeholder="mail.ejemplo.com" /><FieldError name="mail.host" errors={errors} /></div>
              <div><label className="anlux-label">Puerto</label><input name="mail[port]" type="number" min="1" max="65535" defaultValue={s.mail.port} className="anlux-control w-full" /><FieldError name="mail.port" errors={errors} /></div>
              <div><label className="anlux-label">Seguridad</label><select name="mail[scheme]" defaultValue={s.mail.scheme || ''} className="anlux-control w-full"><option value="">Automática/TLS</option><option value="smtp">SMTP</option><option value="smtps">SMTPS</option></select></div>
              <div><label className="anlux-label">Usuario</label><input name="mail[username]" defaultValue={s.mail.username} className="anlux-control w-full" autoComplete="username" /></div>
              <SecretField name="mail[password]" removeName="mail[remove_password]" label="Contraseña" configured={s.mail.has_password} errors={errors} />
              <div><label className="anlux-label">Correo remitente</label><input name="mail[from_address]" type="email" defaultValue={s.mail.from_address} className="anlux-control w-full" /><FieldError name="mail.from_address" errors={errors} /></div>
              <div><label className="anlux-label">Nombre remitente</label><input name="mail[from_name]" defaultValue={s.mail.from_name} className="anlux-control w-full" /><FieldError name="mail.from_name" errors={errors} /></div>
            </div>
          </section>

          <section className="anlux-panel">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div><h2 className="text-lg font-bold text-slate-900"><i className="fab fa-whatsapp mr-2 text-green-600" />WhatsApp Cloud</h2><p className="text-sm text-slate-600">Credenciales y plantillas aprobadas en Meta Business.</p></div>
              <div className="flex items-center gap-3"><StatusBadge enabled={s.whatsapp.enabled} configured={whatsappConfigured} test={s.tests.whatsapp} /><label className="flex items-center gap-2 font-semibold"><input type="hidden" name="whatsapp[enabled]" value="0" /><input type="checkbox" name="whatsapp[enabled]" value="1" defaultChecked={s.whatsapp.enabled} /> Activar WhatsApp</label></div>
            </div>
            <div className="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              <div><label className="anlux-label">URL Graph API</label><input name="whatsapp[base_url]" type="url" defaultValue={s.whatsapp.base_url} className="anlux-control w-full" /></div>
              <div><label className="anlux-label">Versión Graph</label><input name="whatsapp[graph_version]" defaultValue={s.whatsapp.graph_version} className="anlux-control w-full" placeholder="v20.0" /></div>
              <div><label className="anlux-label">Phone Number ID</label><input name="whatsapp[phone_number_id]" inputMode="numeric" defaultValue={s.whatsapp.phone_number_id} className="anlux-control w-full" /><FieldError name="whatsapp.phone_number_id" errors={errors} /></div>
              <SecretField name="whatsapp[access_token]" removeName="whatsapp[remove_access_token]" label="Access token" configured={s.whatsapp.has_access_token} errors={errors} />
              <SecretField name="whatsapp[verify_token]" removeName="whatsapp[remove_verify_token]" label="Verify token" configured={s.whatsapp.has_verify_token} errors={errors} />
              <SecretField name="whatsapp[app_secret]" removeName="whatsapp[remove_app_secret]" label="App secret" configured={s.whatsapp.has_app_secret} errors={errors} />
              <div><label className="anlux-label">Idioma</label><input name="whatsapp[language]" defaultValue={s.whatsapp.language} className="anlux-control w-full" /></div>
              <div><label className="anlux-label">Código de país</label><input name="whatsapp[default_country_code]" inputMode="numeric" defaultValue={s.whatsapp.default_country_code} className="anlux-control w-full" /></div>
              {(['recepcion', 'terminado', 'entregado'] as const).map((key) => <div key={key}><label className="anlux-label">Plantilla {key}</label><input name={`whatsapp[templates][${key}]`} defaultValue={s.whatsapp.templates[key] || ''} className="anlux-control w-full" /><FieldError name={`whatsapp.templates.${key}`} errors={errors} /></div>)}
            </div>
            <div className="mt-4 flex flex-wrap gap-5">
              <label className="flex items-center gap-2 text-sm"><input type="hidden" name="whatsapp[webhook_verify_signature]" value="0" /><input type="checkbox" name="whatsapp[webhook_verify_signature]" value="1" defaultChecked={s.whatsapp.webhook_verify_signature} /> Validar firma del webhook</label>
              <label className="flex items-center gap-2 text-sm"><input type="hidden" name="whatsapp[template_include_document]" value="0" /><input type="checkbox" name="whatsapp[template_include_document]" value="1" defaultChecked={s.whatsapp.template_include_document} /> Adjuntar PDF de la orden</label>
            </div>
          </section>

          <div className="flex justify-end"><button className="anlux-btn-primary" type="submit"><i className="fas fa-save" />Guardar y aplicar</button></div>
        </form>

        <section className="grid gap-5 lg:grid-cols-2">
          <form method="POST" action={props.actions.testMail} className="anlux-panel"><Csrf /><h2 className="font-bold text-slate-900">Prueba SMTP</h2><p className="mt-1 text-sm text-slate-600">Guarda primero la configuración y envía un correo real.</p><div className="mt-3 flex flex-col gap-2 sm:flex-row"><input type="email" required name="test_email" className="anlux-control flex-1" placeholder="destino@ejemplo.com" /><button className="anlux-btn-secondary" type="submit">Enviar prueba</button></div></form>
          <form method="POST" action={props.actions.testWhatsapp} className="anlux-panel"><Csrf /><h2 className="font-bold text-slate-900">Prueba WhatsApp</h2><p className="mt-1 text-sm text-slate-600">Sin número solo verifica la cuenta; con número también envía una plantilla.</p><div className="mt-3 grid gap-2 sm:grid-cols-[1fr_150px_auto]"><input name="test_phone" className="anlux-control" placeholder="+52…" /><select name="test_status" className="anlux-control"><option value="recepcion">Recepción</option><option value="terminado">Terminado</option><option value="entregado">Entregado</option></select><button className="anlux-btn-secondary" type="submit">Verificar</button></div></form>
        </section>
      </div>
    </div>
  );
}
