export type DisplayFieldType =
  | 'text'
  | 'label'
  | 'textarea'
  | 'date'
  | 'datetime'
  | 'badge'
  | 'link'
  | 'cards'
  | 'timeline'
  | 'selectUsers'
  | 'custom'

export interface DisplayCardItemConfig {
  key: string
  label?: string
  format?: 'text' | 'date' | 'datetime' | 'badge'
  icon?: string
  variant?: string
}

export interface DisplayFieldConfig {
  key: string
  type: DisplayFieldType
  label?: string
  format?: 'text' | 'date' | 'datetime' | 'badge'
  url?: string
  external?: boolean
  variant?: string
  component?: string
  placeholder?: string
  cards?: DisplayCardItemConfig[]
  meta?: Record<string, unknown>
}

export interface DisplayRowConfig {
  fields: DisplayFieldConfig[]
}

export interface DisplaySectionConfig {
  id: string
  label?: string
  columnSpan?: number
  rows?: DisplayRowConfig[]
  fields?: DisplayFieldConfig[]
}

export interface DisplayColumnConfig {
  id: string
  label?: string
  style?: string
  fields: DisplayFieldConfig[]
}

export interface NotesBlockConfig {
  id: string
  label: string
  url: string
  placeholder?: string
}

export interface FlowKanbanCardConfig {
  columns: DisplayColumnConfig[]
}
