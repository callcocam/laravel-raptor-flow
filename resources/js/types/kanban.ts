/**
 * Tipos genéricos para o Kanban do laravel-raptor-flow.
 * A aplicação pode estender ou fazer cast dos dados do backend para estes formatos.
 */

export interface FlowKanbanStep {
  id: string;
  name: string;
  description?: string | null;
  slug?: string;
  color?: string | null;
  suggested_order?: number;
  templateNextStep?: { id: string; name: string };
  templatePreviousStep?: { id: string; name: string };
}

export interface FlowKanbanWorkable {
  id: string;
  name: string;
  [key: string]: unknown;
}

export interface FlowKanbanExecutionPermissions {
  can_move: boolean;
  can_perform_actions?: boolean;
  can_start_execution?: boolean;
  can_edit_planogram?: boolean;
}

/** Execução genérica (card do Kanban). workable substitui gondola para ser polimórfico. */
export interface FlowKanbanExecution {
  id: string;
  status: string;
  workflow_step_template_id: string;
  current_responsible_id?: string | null;
  execution_started_by?: string | null;
  started_at?: string | null;
  completed_at?: string | null;
  sla_date?: string | null;
  is_overdue?: boolean;
  notes?: string | null;
  workable?: FlowKanbanWorkable;
  /** Compatibilidade: app pode enviar gondola em vez de workable */
  gondola?: FlowKanbanWorkable & { planogram_id?: string; planogram?: { id: string; name: string; edit_url?: string; can_edit?: boolean }; route_gondolas?: string };
  currentResponsible?: { id: string; name: string; email?: string } | null;
  startedBy?: { id: string; name: string } | null;
  users?: Array<{ id: string; name: string; email?: string }>;
  config?: { responsible_role?: { slug?: string }; users?: Array<{ id: string }> };
  permissions?: FlowKanbanExecutionPermissions;
}

export interface FlowKanbanBoardData {
  steps: FlowKanbanStep[];
  executions: Record<string, FlowKanbanExecution[]>;
}

export interface FlowKanbanConfigOption {
  id: string;
  name: string;
  color?: string;
  suggested_order?: number;
}

export interface FlowKanbanPlanogramOption {
  id: string;
  name: string;
  configs: FlowKanbanConfigOption[];
}

export interface FlowKanbanFilterOption {
  value: string | number;
  label: string;
}

export interface FlowKanbanFilterConfig {
  name: string;
  label: string;
  options?: FlowKanbanFilterOption[];
  placeholder?: string;
  classes?: string;
}

export interface FlowKanbanFiltersState {
  data: FlowKanbanFilterConfig[] | null;
  values: Record<string, unknown>;
}
