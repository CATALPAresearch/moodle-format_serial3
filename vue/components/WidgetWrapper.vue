<template>
  <div :id="widgetId" class="grid-stack-item" :gs-id="widgetId">
    <div class="grid-stack-item-content" style="overflow: auto">
      <span
        v-if="editMode"
        class="remove"
        :title="removeTitle"
        @click="handleRemove"
      >
        <i class="fa fa-close"></i>
      </span>
      <component
        :is="widgetComponent"
        v-if="widgetComponent"
        ref="widgetComponent"
      />
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from "vue";

export default {
  name: "WidgetWrapper",

  props: {
    widgetId: {
      type: String,
      required: true,
    },
    widgetInfo: {
      type: Object,
      required: true,
    },
    editMode: {
      type: Boolean,
      default: false,
    },
    removeTitle: {
      type: String,
      default: "Remove",
    },
  },

  emits: ["remove"],

  data() {
    return {
      widgetComponent: null,
    };
  },

  async mounted() {
    await this.loadWidgetComponent();
  },

  methods: {
    async loadWidgetComponent() {
      try {
        const componentModule = await this.widgetInfo.component();
        this.widgetComponent = componentModule.default || componentModule;
      } catch (error) {
        console.error(
          `Failed to load widget component ${this.widgetId}:`,
          error,
        );
      }
    },

    handleRemove() {
      this.$emit("remove", this.widgetId);
    },

    // Expose method for parent to call refresh
    async loadData() {
      // Access the actual widget component instance
      const widgetInstance = this.$refs.widgetComponent;
      if (widgetInstance && typeof widgetInstance.loadData === "function") {
        await widgetInstance.loadData();
      }
    },
  },
};
</script>

<style scoped>
.remove {
  position: absolute;
  right: 8px;
  top: 0;
  cursor: pointer;
  color: #666666;
  z-index: 10;
}

.remove:hover {
  color: black;
}
</style>
