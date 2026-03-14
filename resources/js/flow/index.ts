import type { App, Component, Plugin } from 'vue'
import { defineAsyncComponent } from 'vue'
import FlowActionRegistry from '../utils/FlowActionRegistry'

/**
 * Auto-registro dos componentes padrão de Actions do Flow.
 * Podem ser sobrescritos via FlowPluginOptions.overrideActions.
 */
FlowActionRegistry.registerBulk({
  'flow-action-button': defineAsyncComponent(
    () => import('../components/actions/types/FlowActionButton.vue'),
  ),
  'flow-action-confirm': defineAsyncComponent(
    () => import('../components/actions/types/FlowActionConfirm.vue'),
  ),
  'flow-action-notes': defineAsyncComponent(
    () => import('../components/actions/types/FlowActionNotes.vue'),
  ),
  'flow-action-link': defineAsyncComponent(
    () => import('../components/actions/types/FlowActionLink.vue'),
  ),
  'start-action-button': defineAsyncComponent(
    () => import('../components/actions/types/StartActionButton.vue'),
  ),
  'pause-action-button': defineAsyncComponent(
    () => import('../components/actions/types/PauseActionButton.vue'),
  ),
  'resume-action-button': defineAsyncComponent(
    () => import('../components/actions/types/ResumeActionButton.vue'),
  ),
  'abandon-action-button': defineAsyncComponent(
    () => import('../components/actions/types/AbandonActionButton.vue'),
  ),
})

FlowActionRegistry.markAsInitialized()

/**
 * Opções do FlowRaptorPlugin
 */
export interface FlowPluginOptions {
  /** Substitui componentes de action padrão por implementações customizadas */
  overrideActions?: Record<string, Component>
  /** Registra componentes Vue globalmente no app */
  customComponents?: Record<string, Component>
}

const install = (app: App, options: FlowPluginOptions = {}): void => {
  if (options.overrideActions) {
    Object.entries(options.overrideActions).forEach(([name, component]) => {
      FlowActionRegistry.register(name, component)
    })
  }

  if (options.customComponents) {
    Object.entries(options.customComponents).forEach(([name, component]) => {
      app.component(name, component)
    })
  }

  app.provide('flowActionRegistry', FlowActionRegistry)
}

/**
 * FlowRaptorPlugin - Plugin Vue para laravel-raptor-flow
 *
 * Registra todos os componentes de action e permite customização.
 *
 * @example
 * // app.ts
 * import { FlowRaptorPlugin } from 'laravel-raptor-flow'
 * app.use(FlowRaptorPlugin)
 *
 * // Com override
 * app.use(FlowRaptorPlugin, {
 *   overrideActions: {
 *     'flow-action-button': MyCustomButton,
 *   }
 * })
 */
export const FlowRaptorPlugin: Plugin = { install }

export { FlowActionRegistry }
export default FlowRaptorPlugin
