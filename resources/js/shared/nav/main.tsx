import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { readPageProps } from '../http';
import { NavAdminBar } from './NavAdminBar';
import { NavAppBar } from './NavAppBar';
import type { NavAdminBootstrap, NavAppBootstrap, NavBootstrap } from './types';

function readNavProps(): NavBootstrap | null {
  return readPageProps<NavBootstrap>('anlux-nav-props');
}

const appRoot = document.getElementById('anlux-nav-app-root');
if (appRoot) {
  const props = readNavProps();
  if (props && props.variant === 'app') {
    createRoot(appRoot).render(
      <StrictMode>
        <NavAppBar {...(props as NavAppBootstrap)} />
      </StrictMode>,
    );
  }
}

const adminRoot = document.getElementById('anlux-nav-admin-root');
if (adminRoot) {
  const props = readNavProps();
  if (props && props.variant === 'admin') {
    createRoot(adminRoot).render(
      <StrictMode>
        <NavAdminBar {...(props as NavAdminBootstrap)} />
      </StrictMode>,
    );
  }
}
