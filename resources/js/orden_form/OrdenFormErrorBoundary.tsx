import { Component, type ErrorInfo, type ReactNode } from 'react';

type Props = { children: ReactNode };

type State = { error: Error | null };

export class OrdenFormErrorBoundary extends Component<Props, State> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error('[anlux] Error en formulario de orden:', error, info.componentStack);
  }

  render(): ReactNode {
    if (this.state.error) {
      return (
        <div className="rounded-lg border-2 border-red-300 bg-red-50 p-6 text-red-900">
          <p className="font-bold">
            <i className="fas fa-exclamation-triangle mr-2" />
            No se pudo cargar el formulario de orden
          </p>
          <p className="mt-2 text-sm">{this.state.error.message}</p>
          <p className="mt-2 text-xs text-red-800">Recarga con Ctrl+F5. Si persiste, avisa a soporte.</p>
        </div>
      );
    }
    return this.props.children;
  }
}
