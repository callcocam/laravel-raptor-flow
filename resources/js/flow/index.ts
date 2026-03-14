import type { App, Component, Plugin } from 'vue'
import { defineAsyncComponent } from 'vue'
import FlowActionRegistry from '../utils/FlowActionRegistry'
import FlowDisplayRegistry from '../utils/FlowDisplayRegistry'

/**
 * Auto-registro dos componentes padrao de acoes do Flow.
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

FlowDisplayRegistry.registerBulk({
  'flow-display-field': defineAsyncComponent(
    () => import('../components/kanban/DisplayFieldRenderer.vue'),
  ),
  'flow-display-note-block': defineAsyncComponent(
    () => import('../components/kanban/NoteBlockRenderer.vue'),
  ),
  'flow-display-text': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayText.vue'),
  ),
  'flow-display-label': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayLabel.vue'),
  ),
  'flow-display-textarea': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayTextarea.vue'),
  ),
  'flow-display-date': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayDate.vue'),
  ),
  'flow-display-datetime': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayDatetime.vue'),
  ),
  'flow-display-badge': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayBadge.vue'),
  ),
  'flow-display-link': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayLink.vue'),
  ),
  'flow-display-cards': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayCards.vue'),
  ),
  'flow-display-timeline': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayTimeline.vue'),
  ),
  'flow-display-select-users': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplaySelectUsers.vue'),
  ),
  'flow-display-custom': defineAsyncComponent(
    () => import('../components/display/types/FlowDisplayCustom.vue'),
  ),
})

FlowDisplayRegistry.markAsInitialized()

/**
 * Opções do FlowRaptorPlugin
 */
export interface FlowPluginOptions {
  /** Substitui componentes de acao padrao por implementacoes customizadas */
  overrideActions?: Record<string, Component>
  /** Registra ou sobrescreve componentes customizados para DisplayField.component */
  overrideDisplayComponents?: Record<string, Component>
  /** Registra componentes Vue globalmente na aplicacao */
  customComponents?: Record<string, Component>
}

const install = (app: App, options: FlowPluginOptions = {}): void => {
  if (options.overrideActions) {
    Object.entries(options.overrideActions).forEach(([name, component]) => {
      FlowActionRegistry.register(name, component)
    })
  }

  if (options.overrideDisplayComponents) {
    Object.entries(options.overrideDisplayComponents).forEach(([name, component]) => {
      FlowDisplayRegistry.register(name, component)
    })
  }

  if (options.customComponents) {
    Object.entries(options.customComponents).forEach(([name, component]) => {
      app.component(name, component)
    })
  }

  app.provide('flowActionRegistry', FlowActionRegistry)
  app.provide('flowDisplayRegistry', FlowDisplayRegistry)
}

/**
 * FlowRaptorPlugin - Plugin Vue para laravel-raptor-flow
 *
 * Registra todos os componentes de acao e permite customizacao.
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
export { FlowDisplayRegistry }
export default FlowRaptorPlugin
