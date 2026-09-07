import { useState } from 'react';
import { readPageProps } from '../../shared/http';

type Props = {
  action: string;
  csrf: string;
  old: Record<string, string>;
  success: string | null;
  error: string | null;
};

export default function App() {
  const props = readPageProps<Props>() || {
    action: '/admin/registro',
    csrf: '',
    old: {},
    success: null,
    error: null,
  };
  const [showPwd, setShowPwd] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [nombre, setNombre] = useState(props.old.nombre || '');
  const [usuario, setUsuario] = useState(props.old.nombre_usuario || '');
  const [celular, setCelular] = useState(props.old.celular || '');

  return (
    <>
      <h1 className="mb-8 text-center text-2xl font-bold text-blue-700 sm:text-3xl">Registro de usuarios</h1>
      {props.success ? <div className="mb-5 rounded-lg bg-green-500 p-4 text-white">{props.success}</div> : null}
      {props.error ? <div className="mb-5 rounded-lg bg-red-500 p-4 text-white">{props.error}</div> : null}
      <form action={props.action} method="POST" className="space-y-5">
        <input type="hidden" name="_token" value={props.csrf} />
        <div>
          <label htmlFor="nombre" className="mb-2 block font-bold text-blue-700">Nombre completo:</label>
          <input id="nombre" name="nombre" required value={nombre} onChange={(e) => setNombre(e.target.value.toUpperCase())} className="w-full rounded-lg border border-blue-300 px-3 py-2 uppercase focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300" />
        </div>
        <div>
          <label htmlFor="nombre_usuario" className="mb-2 block font-bold text-blue-700">Nombre de usuario:</label>
          <input id="nombre_usuario" name="nombre_usuario" required maxLength={64} pattern="[A-Za-z0-9._\-]+" value={usuario} onChange={(e) => setUsuario(e.target.value.toUpperCase())} className="w-full rounded-lg border border-blue-300 px-3 py-2 uppercase focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300" />
        </div>
        <div>
          <label htmlFor="email" className="mb-2 block font-bold text-blue-700">Email:</label>
          <input id="email" name="email" type="email" required defaultValue={props.old.email || ''} className="w-full rounded-lg border border-blue-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300" />
        </div>
        <div>
          <label htmlFor="password" className="mb-2 block font-bold text-blue-700">Contraseña:</label>
          <div className="relative">
            <input id="password" name="password" type={showPwd ? 'text' : 'password'} minLength={8} required className="w-full rounded-lg border border-blue-300 px-3 py-2 pr-12 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300" />
            <button type="button" className="absolute right-3 top-1/2 -translate-y-1/2 text-blue-600" onClick={() => setShowPwd((v) => !v)}><i className={`fas ${showPwd ? 'fa-eye-slash' : 'fa-eye'}`} /></button>
          </div>
        </div>
        <div>
          <label htmlFor="confirm_password" className="mb-2 block font-bold text-blue-700">Confirmar contraseña:</label>
          <div className="relative">
            <input id="confirm_password" name="confirm_password" type={showConfirm ? 'text' : 'password'} minLength={8} required className="w-full rounded-lg border border-blue-300 px-3 py-2 pr-12 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300" />
            <button type="button" className="absolute right-3 top-1/2 -translate-y-1/2 text-blue-600" onClick={() => setShowConfirm((v) => !v)}><i className={`fas ${showConfirm ? 'fa-eye-slash' : 'fa-eye'}`} /></button>
          </div>
        </div>
        <div>
          <label htmlFor="celular" className="mb-2 block font-bold text-blue-700">Celular:</label>
          <input id="celular" name="celular" required maxLength={15} inputMode="numeric" value={celular} onChange={(e) => setCelular(e.target.value.replace(/\D+/g, ''))} className="w-full rounded-lg border border-blue-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300" />
        </div>
        <div>
          <label className="mb-3 block font-bold text-blue-700">Perfil:</label>
          <div className="flex flex-col gap-3 sm:flex-row sm:gap-5">
            <label className="flex cursor-pointer items-center gap-2">
              <input type="radio" name="perfil" value="tecnico" defaultChecked={props.old.perfil === 'tecnico'} required />
              <span className="text-blue-700">Técnico</span>
            </label>
            <label className="flex cursor-pointer items-center gap-2">
              <input type="radio" name="perfil" value="administrador" defaultChecked={props.old.perfil === 'administrador'} required />
              <span className="text-blue-700">Administrador</span>
            </label>
          </div>
        </div>
        <button type="submit" className="w-full rounded-lg bg-gradient-to-r from-blue-500 to-blue-700 py-3 font-bold text-white transition-transform hover:-translate-y-1 hover:shadow-lg">Registrarse</button>
      </form>
    </>
  );
}
