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
  /** @deprecated Visibility is backend-driven via execution.action_visibility / execution.abilities. */
  visibleStatuses?: string[] | null;
  /** @deprecated Visibility is backend-driven via execution.action_visibility / execution.abilities. */
  visible?: boolean;
  confirm?: { title: string; description?: string } | null;
  data?: Record<string, unknown>;
  /** Nome de componente customizado registrado no FlowActionRegistry */
  component?: string | null;
  /** Apenas para type 'notes' */
  placeholder?: string;
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

/** @deprecated Use FlowActionSchema no DetailModalConfig.actions em vez disso */
export interface FlowKanbanActionConfig {
  move?: (workableId: string, fromStepId: string, toStepId: string) => FlowKanbanActionRequest;
  start?: (execution: unknown) => FlowKanbanActionRequest;
  pause?: (execution: unknown) => FlowKanbanActionRequest;
  resume?: (execution: unknown) => FlowKanbanActionRequest;
  abandon?: (execution: unknown) => FlowKanbanActionRequest;
  updateNotes?: (execution: unknown, notes: string) => FlowKanbanActionRequest;
}
