export * from './components/kanban'
export * from './types/kanban'
export * from './types/detailModal'

// Plugin + Registry
export { FlowRaptorPlugin, FlowActionRegistry } from './flow/index'
export type { FlowPluginOptions } from './flow/index'
export { default as FlowRaptorPluginDefault } from './flow/index'

// Action renderer (para uso direto quando necessário)
export { default as FlowActionRenderer } from './components/actions/FlowActionRenderer.vue'
