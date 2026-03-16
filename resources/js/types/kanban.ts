import type { FlowKanbanCardConfig } from './display'
import type { FlowKanbanCardLinkConfig } from './display'
import type { FlowActionSchema } from './detailModal'

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
  /** ID do grupo ao qual este workable pertence (ex: planogram_id, project_id).
   *  Usado para validar drop entre colunas via FlowKanbanGroupConfig. */
  group_id?: string | null;
  group_label?: string | null;
  [key: string]: unknown;
}

export interface FlowKanbanExecutionPermissions {
  can_move: boolean;
  can_perform_actions?: boolean;
  can_start_execution?: boolean;
  can_edit_planogram?: boolean;
}

/** Execução genérica (card do Kanban). */
export interface FlowKanbanExecution {
  id: string;
  status: string;
  status_presentation?: {
    label: string;
    icon: string;
    class: string;
  };
  workflow_step_template_id: string;
  flow_config_step_id?: string;
  current_responsible_id?: string | null;
  execution_started_by?: string | null;
  started_at?: string | null;
  completed_at?: string | null;
  sla_date?: string | null;
  is_overdue?: boolean;
  notes?: string | null;
  workable?: FlowKanbanWorkable;
  currentResponsible?: { id: string; name: string; email?: string } | null;
  startedBy?: { id: string; name: string } | null;
  users?: Array<{ id: string; name: string; email?: string }>;
  config?: { responsible_role?: { slug?: string }; users?: Array<{ id: string }> };
  abilities?: Record<string, boolean> | null;
  action_visibility?: Record<string, boolean>;
  permissions?: FlowKanbanExecutionPermissions;
  modal_actions?: FlowActionSchema[];
  card_actions?: FlowActionSchema[];
  card_links?: FlowKanbanCardLinkConfig[];
  metrics_summary?: {
    count: number;
    latest: {
      id: string;
      total_duration_minutes?: number | null;
      effective_work_minutes?: number | null;
      estimated_duration_minutes?: number | null;
      deviation_minutes?: number | null;
      is_on_time?: boolean;
      is_rework?: boolean;
      rework_count?: number | null;
      started_at?: string | null;
      completed_at?: string | null;
      calculated_at?: string | null;
    } | null;
  };
  notifications_summary?: {
    count: number;
    unread_count: number;
    latest: Array<{
      id: string;
      type?: string;
      priority?: string;
      title?: string;
      message?: string | null;
      is_read: boolean;
      read_at?: string | null;
      created_at?: string | null;
    }>;
  };
  templateNextStep?: { id: string; name: string } | null;
  templatePreviousStep?: { id: string; name: string } | null;
}

export interface FlowKanbanBoardTreeConfigItem {
  id: string;
  name: string | null;
  execution: FlowKanbanExecution | null;
}

export interface FlowKanbanBoardTreeConfigStep {
  id: string;
  order?: number | null;
  configurable_id: string;
  configurable_type?: string | null;
  configurable_label?: string | null;
  configs: FlowKanbanBoardTreeConfigItem[];
}

export interface FlowKanbanBoardTreeNode {
  id: string;
  name: string;
  suggested_order?: number;
  description?: string | null;
  slug?: string;
  color?: string | null;
  templateNextStep?: { id: string; name: string } | null;
  templatePreviousStep?: { id: string; name: string } | null;
  executions: FlowKanbanExecution[];
  /** @deprecated Compatibilidade com payload legado em formato de arvore. */
  configSteps: FlowKanbanBoardTreeConfigStep[];
}

export type FlowKanbanBoardData = FlowKanbanBoardTreeNode[];

export interface FlowKanbanBoardRawData {
  steps: FlowKanbanStep[];
  executions: Record<string, FlowKanbanExecution[]>;
}

export type FlowKanbanBoardPayload = FlowKanbanBoardData | FlowKanbanBoardRawData;

/**
 * Configuração de grupo usada para validar quais colunas (steps) aceitam o drop
 * de um card pertencente a esse grupo.
 *
 * Genérico: substitui o antigo FlowKanbanPlanogramOption.
 * Exemplos de uso: planogramas, projetos, categorias, etc.
 */
export interface FlowKanbanGroupConfig {
  /** Identificador do grupo (ex: planogram.id, project.id) */
  id: string;
  name: string;
  /** IDs dos steps permitidos para este grupo */
  stepIds: string[];
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

export interface FlowKanbanBoardMeta {
  cardConfig?: FlowKanbanCardConfig | null;
}
