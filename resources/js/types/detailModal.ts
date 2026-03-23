/**
 * Schema do modal de detalhes genérico do Kanban (laravel-raptor-flow).
 * A aplicação ou backend passa DetailModalConfig para o FlowDetailModal.
 */

import type { DisplaySectionConfig, NotesBlockConfig } from './display'

export type DetailModalSectionConfig = DisplaySectionConfig

export interface DetailModalLinkConfig {
  key: string;
  label: string;
  url: string | ((execution: unknown) => string);
  external?: boolean;
}

/**
 * Ação serializada pelo backend (PHP FlowAction::toArray()).
 *
 * - type 'action'  → botão que faz uma requisição Inertia
 * - type 'notes'   → textarea de notas com botão salvar
 * - type 'link'    → link/âncora
 *
 * O campo `url` pode conter placeholders {param} que o frontend resolve
 * com os dados da execução. Ex: /flow/executions/{id}/start
 */
export interface FlowActionSchema {
  id: string;
  type: 'action' | 'notes' | 'link';
  label: string;
  icon?: string | null;
  method?: 'get' | 'post' | 'patch' | 'put' | 'delete';
  url?: string;
  variant?: 'default' | 'outline' | 'destructive' | 'ghost' | 'secondary';
  confirm?: { title: string; description?: string } | null;
  data?: Record<string, unknown>;
  /** Nome de componente customizado registrado no FlowActionRegistry */
  component?: string | null;
  /** Apenas para type 'notes' */
  placeholder?: string;
  target?: '_self' | '_blank' | undefined;
}

export interface DetailModalConfig {
  sections: DetailModalSectionConfig[];
  /** Ações do modal — preferencialmente geradas pelo backend via FlowAction::toArray() */
  actions: FlowActionSchema[];
  links?: DetailModalLinkConfig[];
  notes?: NotesBlockConfig[];
}

/** Resultado de uma ação para o pacote executar via router (Inertia) */
export interface FlowKanbanActionRequest {
  url: string;
  method?: 'get' | 'post' | 'patch' | 'put' | 'delete';
  data?: Record<string, unknown>;
}
