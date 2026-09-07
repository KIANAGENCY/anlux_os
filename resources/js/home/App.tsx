import { readPageProps } from '../shared/http';

export type HomeProps = {
  authenticated: boolean;
  isAdmin: boolean;
  nombreSesion: string;
  logoUrl: string;
  urls: {
    login: string;
    ordenes: string;
    nuevaOrden: string;
    historial: string;
    profile: string;
    admin: string;
    usuarios: string;
    seguridad: string;
    terminos: string;
    privacidad: string;
  };
};

export default function App() {
  const props = readPageProps<HomeProps>() || {
    authenticated: false,
    isAdmin: false,
    nombreSesion: '',
    logoUrl: '',
    urls: {
      login: '/login',
      ordenes: '/ordenes',
      nuevaOrden: '/orden_servicio',
      historial: '/historial',
      profile: '/profile',
      admin: '/admin',
      usuarios: '/admin/usuarios',
      seguridad: '/admin/seguridad',
      terminos: '/terminos-y-condiciones',
      privacidad: '/aviso-de-privacidad',
    },
  };

  return (
    <div className="page-shell">
      <section className="hero">
        <div className="hero-inner">
          <div>
            <div className="brand-row">
              {props.logoUrl ? (
                <img src={props.logoUrl} alt="Anlux" className="brand-logo" />
              ) : null}
              <span className="eyebrow">
                <i className="fas fa-lock" />
                Uso interno
              </span>
            </div>
            <h1>Menu principal Anlux</h1>
            {props.authenticated ? (
              <div className="session-pill">
                <i className="fas fa-circle-check" />
                Sesion activa:
                {' '}
                {props.nombreSesion}
              </div>
            ) : null}
          </div>

          {props.authenticated ? (
            <div className="hero-panel">
              <h2>Acceso inmediato</h2>
              <div className="btn-row">
                <a href={props.urls.ordenes} className="btn btn-primary">
                  <i className="fas fa-grid-2" />
                  Abrir sistema
                </a>
                <a href={props.urls.profile} className="btn btn-secondary">
                  <i className="fas fa-user-gear" />
                  Mi perfil
                </a>
              </div>
            </div>
          ) : null}
        </div>
      </section>

      <div className="content-grid">
        {props.authenticated ? (
          <section className="section-card">
            <div className="section-head">
              <div>
                <h2>Modulos principales</h2>
              </div>
              <span className="badge">
                <i className="fas fa-bolt" />
                Acceso rapido
              </span>
            </div>
            <div className="menu-grid">
              <a href={props.urls.ordenes} className="menu-card">
                <span className="menu-icon icon-blue"><i className="fas fa-clipboard-list" /></span>
                <h3>Ordenes</h3>
                <span className="menu-link">
                  Entrar
                  <i className="fas fa-arrow-right" />
                </span>
              </a>
              <a href={props.urls.nuevaOrden} className="menu-card">
                <span className="menu-icon icon-green"><i className="fas fa-file-circle-plus" /></span>
                <h3>Nueva orden</h3>
                <span className="menu-link">
                  Crear
                  <i className="fas fa-arrow-right" />
                </span>
              </a>
              <a href={props.urls.historial} className="menu-card">
                <span className="menu-icon icon-amber"><i className="fas fa-clock-rotate-left" /></span>
                <h3>Historial</h3>
                <span className="menu-link">
                  Consultar
                  <i className="fas fa-arrow-right" />
                </span>
              </a>
              {props.isAdmin ? (
                <>
                  <a href={props.urls.admin} className="menu-card">
                    <span className="menu-icon icon-violet"><i className="fas fa-shield-halved" /></span>
                    <h3>Panel admin</h3>
                    <span className="menu-link">
                      Abrir panel
                      <i className="fas fa-arrow-right" />
                    </span>
                  </a>
                  <a href={props.urls.usuarios} className="menu-card">
                    <span className="menu-icon icon-sky"><i className="fas fa-users" /></span>
                    <h3>Usuarios</h3>
                    <span className="menu-link">
                      Administrar
                      <i className="fas fa-arrow-right" />
                    </span>
                  </a>
                  <a href={props.urls.seguridad} className="menu-card">
                    <span className="menu-icon icon-rose"><i className="fas fa-user-shield" /></span>
                    <h3>Seguridad</h3>
                    <span className="menu-link">
                      Revisar
                      <i className="fas fa-arrow-right" />
                    </span>
                  </a>
                </>
              ) : null}
            </div>
          </section>
        ) : (
          <section className="section-card">
            <div className="login-card">
              <div className="login-copy">
                <h2>Acceso interno al proyecto</h2>
                <div className="btn-row" style={{ marginTop: 18 }}>
                  <a href={props.urls.login} className="btn" style={{ background: 'var(--primary)', color: '#fff' }}>
                    <i className="fas fa-right-to-bracket" />
                    Ir a inicio de sesion
                  </a>
                </div>
              </div>
              <div className="info-box">
                <strong>Que puedes hacer aqui</strong>
                <ul>
                  <li>Entrar</li>
                  <li>Acceder al menu principal</li>
                  <li>Usar esta portada como inicio</li>
                </ul>
              </div>
            </div>
          </section>
        )}
      </div>

      <div className="footer-note">
        <span>Anlux | Sistema interno de ordenes y administracion</span>
        <nav className="footer-legal" aria-label="Enlaces legales">
          <a href={props.urls.terminos}>Terminos y condiciones</a>
          <span className="footer-sep" aria-hidden="true">|</span>
          <a href={props.urls.privacidad}>Aviso de privacidad</a>
        </nav>
      </div>
    </div>
  );
}
