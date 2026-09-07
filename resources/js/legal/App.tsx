import { readPageProps } from '../shared/http';

type Block =
  | { type: 'p'; html: string }
  | { type: 'h2'; text: string }
  | { type: 'ul'; items: string[] };

type Props = {
  title: string;
  updatedAt: string;
  blocks: Block[];
  homeUrl: string;
  terminosUrl: string;
  privacidadUrl: string;
  eliminarUrl: string;
};

function BlockView({ block }: { block: Block }) {
  if (block.type === 'h2') {
    return <h2>{block.text}</h2>;
  }
  if (block.type === 'ul') {
    return (
      <ul>
        {block.items.map((item, i) => (
          <li key={i} dangerouslySetInnerHTML={{ __html: item }} />
        ))}
      </ul>
    );
  }
  return <p dangerouslySetInnerHTML={{ __html: block.html }} />;
}

export default function App() {
  const props = readPageProps<Props>();
  if (!props || !Array.isArray(props.blocks) || props.blocks.length === 0) {
    return (
      <div className="legal-shell">
        <div className="legal-top">
          <a href="/" className="legal-back">
            <i className="fas fa-arrow-left" />
            {' '}
            Volver al inicio
          </a>
        </div>
        <article className="legal-card">
          <h1>No se pudo cargar el contenido</h1>
          <p>Recarga la pagina o vuelve al inicio.</p>
        </article>
      </div>
    );
  }

  return (
    <div className="legal-shell">
      <div className="legal-top">
        <a href={props.homeUrl} className="legal-back">
          <i className="fas fa-arrow-left" />
          {' '}
          Volver al inicio
        </a>
      </div>
      <article className="legal-card">
        <h1>{props.title}</h1>
        {props.updatedAt ? (
          <p className="legal-updated">
            Ultima actualizacion:
            {' '}
            {props.updatedAt}
          </p>
        ) : null}
        {props.blocks.map((block, i) => (
          <BlockView key={i} block={block} />
        ))}
      </article>
      <footer className="legal-footer">
        <span>Anlux | Sistema interno de ordenes y administracion</span>
        <nav aria-label="Enlaces legales">
          <a href={props.terminosUrl}>Terminos y condiciones</a>
          <span className="sep" aria-hidden="true">|</span>
          <a href={props.privacidadUrl}>Aviso de privacidad</a>
          <span className="sep" aria-hidden="true">|</span>
          <a href={props.eliminarUrl}>Eliminacion de datos</a>
        </nav>
      </footer>
    </div>
  );
}
