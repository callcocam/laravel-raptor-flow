import type { Component } from 'vue'

type ComponentMap = Record<string, Component>

class FlowDisplayRegistryClass {
  private components: ComponentMap = {}
  private initialized = false

  register(name: string, component: Component): void {
    this.components[name] = component
  }

  registerBulk(components: ComponentMap): void {
    Object.entries(components).forEach(([name, component]) => {
      this.register(name, component)
    })
  }

  get(name: string): Component | undefined {
    return this.components[name]
  }

  has(name: string): boolean {
    return name in this.components
  }

  getAll(): ComponentMap {
    return { ...this.components }
  }

  list(): string[] {
    return Object.keys(this.components)
  }

  unregister(name: string): void {
    delete this.components[name]
  }

  clear(): void {
    this.components = {}
    this.initialized = false
  }

  markAsInitialized(): void {
    this.initialized = true
  }

  isInitialized(): boolean {
    return this.initialized
  }
}

export const FlowDisplayRegistry = new FlowDisplayRegistryClass()
export { FlowDisplayRegistryClass }
export default FlowDisplayRegistry
