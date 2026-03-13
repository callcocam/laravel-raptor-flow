/**
 * Schema do modal de detalhes genérico do Kanban (laravel-raptor-flow).
 * A aplicação ou backend passa DetailModalConfig para o FlowDetailModal.
 */

export type DetailModalFieldType =
  | 'text'
  | 'textarea'
  | 'editor'
  | 'upload'
  | 'image'
  | 'selectUsers'
  | 'datetime'
  | 'badge'
  | 'timeline'
  | 'custom';

export interface DetailModalFieldConfig {
  key: string;
  type: DetailModalFieldType;
  label?: string;
  placeholder?: string;
  readOnly?: boolean;
  options?: Array<{ value: string | number; label: string }>;
  /** Para type custom: nome do slot ou componente que a app injeta */
  component?: string;
}

export interface DetailModalSectionConfig {
  id: string;
  label?: string;
  fields: DetailModalFieldConfig[];
}

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
  /** Status em que a ação é visível. null/undefined = sempre visível. */
  visibleStatuses?: string[] | null;
  confirm?: { title: string; description?: string } | null;
  data?: Record<string, unknown>;
  /** Apenas para type 'notes' */
  placeholder?: string;
}

export interface DetailModalConfig {
  sections: DetailModalSectionConfig[];
  /** Ações do modal — preferencialmente geradas pelo backend via FlowAction::toArray() */
  actions: FlowActionSchema[];
  links?: DetailModalLinkConfig[];
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
