# Vue 3 Best Practices
Use these rules to guide AI generation or to standardize your Vue 3 project architecture.

## 1. Core Logic & API
*   **Syntax:** Always use `<script setup>` for component logic.
*   **Reactivity:** Prefer `ref()` for primitive values and `reactive()` for complex objects.
*   **No Options API:** Explicitly forbid `data()`, `methods`, and `mounted()` blocks to ensure [Composition API consistency](https://vuejs.org). 
*   **Logic Sharing:** Extract reusable logic into [Composables](https://vuejs.org) located in `@/composables`.

## 2. Type Safety & Tooling
*   **Build Tool:** Follow [Vite-based project structures](https://vitejs.dev) for optimal development performance.
*   **State Management:** Use [Pinia](https://pinia.vuejs.org) instead of Vuex for global state.

## 3. Template & Style
*   **Component Naming:** Use PascalCase for file names and multi-word names (e.g., `TodoList.vue`) to avoid [HTML element conflicts](https://vuejs.org).
*   **Directives:** Always provide a unique `:key` with `v-for`. Never use `v-if` and `v-for` on the same element.
*   **Styling:** Use Tailwind 4 CSS utility classes or Scoped CSS (`<style scoped>`) to prevent global style leakage.
*   **Icons:** Shoudl always default to use heroIcons classes.


## 4. Component Architecture
*   **Teleport:** Use `<Teleport to="body">` for modals, popovers, and notifications.
*   **Async Components:** Wrap asynchronous logic in `<Suspense>` for better loading state management.
*   **Events:** Use the `update:modelValue` pattern for custom [v-model bindings](https://vuejs.org).
*   **API Fetch:** Use `axios` to call API endpoints, with default configurable timeout.


