<template>
  <div class="d-flex align-items-center">
    <button
      class="button"
      :title="strings.menuBarEditDashboard"
      @click="$emit('editmode')"
    >
      <i aria-hidden="true" class="fa fa-gear icon m-0"></i>
    </button>
    <button
      v-if="isModerator"
      class="button"
      data-target="#widgetManagementModal"
      data-toggle="modal"
      title="Manage Widgets"
      type="button"
    >
      <i aria-hidden="true" class="fa fa-sliders icon m-0"></i>
    </button>
    <button
      class="button"
      data-target="#informationModal"
      data-toggle="modal"
      :title="strings.menuBarDashboardInfo"
      type="button"
    >
      <i aria-hidden="true" class="fa fa-info icon m-0"></i>
    </button>
    <button
      hidden
      class="button"
      data-target="#linkModal"
      data-toggle="modal"
      :title="strings.menuBarHelpfulLinks"
      type="button"
    >
      <i aria-hidden="true" class="fa fa-chain icon m-0"></i>
    </button>

    <a
      href="https://aple.fernuni-hagen.de/mod/usenet/view.php?id=5081&forceview=1"
    >
      <button class="button" :title="strings.menuBarViewNewsgroupPosts">
        <i aria-hidden="true" class="fa fa-comments-o icon m-0"></i>
      </button>
    </a>
    <button
      hidden
      class="button pr-0"
      :title="strings.menuBarContactInstructor"
    >
      <i aria-hidden="true" class="fa fa-send-o icon m-0"></i>
    </button>

    <div
      id="informationModal"
      aria-hidden="true"
      aria-labelledby="informationModalLabel"
      class="modal fade"
      role="dialog"
      tabindex="-1"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 id="informationModalLabel" class="modal-title">
              {{ strings.informationTitle }}
            </h5>
            <button
              aria-label="Close"
              class="close"
              data-dismiss="modal"
              type="button"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" v-html="strings.informationContent"></div>
        </div>
      </div>
    </div>

    <div
      id="linkModal"
      aria-hidden="true"
      aria-labelledby="linkModalLabel"
      class="modal fade"
      role="dialog"
      tabindex="-1"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 id="informationModalLabel" class="modal-title">
              {{ strings.linkTitle }}
            </h5>
            <button
              aria-label="Close"
              class="close"
              data-dismiss="modal"
              type="button"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            {{ strings.linkContent }}
          </div>
        </div>
      </div>
    </div>

    <!-- Widget Management Modal (Teachers Only) -->
    <div
      id="widgetManagementModal"
      aria-hidden="true"
      aria-labelledby="widgetManagementModalLabel"
      class="modal fade"
      role="dialog"
      tabindex="-1"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 id="widgetManagementModalLabel" class="modal-title">
              Widget Management
            </h5>
            <button
              aria-label="Close"
              class="close"
              data-dismiss="modal"
              type="button"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p class="mb-3">
              Select which widgets students can add to their dashboard. As a
              teacher, you can always see all widgets.
            </p>

            <div v-if="loading" class="text-center">
              <i class="fa fa-spinner fa-spin fa-2x"></i>
              <p>Loading widget configuration...</p>
            </div>

            <div v-else>
              <div
                class="alert alert-info"
                v-if="availableWidgets.length === 0"
              >
                No widgets available.
              </div>

              <div class="widget-list">
                <div
                  v-for="widget in availableWidgets"
                  :key="widget.id"
                  class="widget-item"
                >
                  <label class="d-flex align-items-start mb-1">
                    <input
                      type="checkbox"
                      :value="widget.id"
                      v-model="selectedWidgets"
                      class="mr-2 mt-1"
                    />
                    <div class="flex-grow-1">
                      <span class="widget-name d-block">{{ widget.name }}</span>
                      <span
                        class="widget-description text-muted small d-block"
                        v-if="widget.description"
                      >
                        {{ widget.description }}
                      </span>
                    </div>
                  </label>
                </div>
              </div>
            </div>

            <div v-if="saveError" class="alert alert-danger mt-3">
              {{ saveError }}
            </div>

            <div v-if="saveSuccess" class="alert alert-success mt-3">
              Widget configuration saved successfully!
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-dismiss="modal"
            >
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-primary"
              @click="saveWidgetConfig"
              :disabled="saving"
            >
              <i v-if="saving" class="fa fa-spinner fa-spin"></i>
              {{ saving ? "Saving..." : "Save Changes" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from "vuex";
import Communication from "../scripts/communication";

export default {
  name: "MenuBar",

  data() {
    return {
      availableWidgets: [],
      selectedWidgets: [],
      loading: false,
      saving: false,
      saveError: null,
      saveSuccess: false,
    };
  },

  computed: {
    ...mapState(["strings", "isModerator", "courseid"]),
  },

  mounted() {
    this.loadWidgetConfig();
    // Load widget configuration when modal is opened
    const modal = document.getElementById("widgetManagementModal");
    if (modal) {
      modal.addEventListener("show.bs.modal", this.loadWidgetConfig);
    }
  },

  /*beforeUnmount() {
    const modal = document.getElementById("widgetManagementModal");
    if (modal) {
      modal.removeEventListener("show.bs.modal", this.loadWidgetConfig);
    }
  },*/

  methods: {
    async loadWidgetConfig() {
      this.loading = true;
      this.saveError = null;
      this.saveSuccess = false;

      try {
        Communication.setPluginName("format_serial3");

        const response = await Communication.webservice("get_widget_config", {
          courseid: this.courseid,
        });

        console.log("Widget config response:", response);

        if (response.success) {
          console.log("Widget config response:", response);
          // Load available widgets from the backend
          this.availableWidgets = response.widgets || [];

          console.log("Available widgets:", this.availableWidgets);

          // Set selected widgets (enabled ones)
          this.selectedWidgets = this.availableWidgets
            .filter((w) => w.enabled)
            .map((w) => w.id);
        } else {
          this.saveError = response.error || "Failed to load configuration";
        }
      } catch (error) {
        console.error("Failed to load widget configuration:", error);
        this.saveError =
          "Failed to load widget configuration. Please try again.";
      } finally {
        this.loading = false;
      }
    },

    async saveWidgetConfig() {
      this.saving = true;
      this.saveError = null;
      this.saveSuccess = false;

      try {
        Communication.setPluginName("format_serial3");

        const response = await Communication.webservice("save_widget_config", {
          courseid: this.courseid,
          widgets: this.selectedWidgets,
        });

        if (response.success) {
          this.saveSuccess = true;

          // Emit event to parent to reload widgets
          this.$emit("widgetsUpdated");

          // Close modal after short delay
          setTimeout(() => {
            const modal = document.getElementById("widgetManagementModal");
            if (modal && window.bootstrap) {
              const bsModal = window.bootstrap.Modal.getInstance(modal);
              if (bsModal) {
                bsModal.hide();
              }
            } else if (window.$ && window.$.fn.modal) {
              window.$("#widgetManagementModal").modal("hide");
            }
          }, 1500);
        } else {
          this.saveError =
            response.message || "Failed to save widget configuration.";
        }
      } catch (error) {
        console.error("Failed to save widget configuration:", error);
        this.saveError =
          "Failed to save widget configuration. Please try again.";
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../scss/variables.scss";

.icon {
  color: rgba(0, 0, 0, 0.6);
  width: 35px;
  height: 35px;
  font-size: 22px;
  border: 1px solid $light-grey;
  display: flex;
  align-items: center;
  justify-content: center;

  &:hover {
    color: rgba(0, 0, 0, 0.9);
  }
}

.button {
  border: none;
  background: none;
}

.widget-list {
  max-height: 400px;
  overflow-y: auto;
}

.widget-item {
  padding: 4px 0;
  border-bottom: 1px solid #eee;

  &:last-child {
    border-bottom: none;
  }

  label {
    margin-bottom: 0 !important;
    cursor: pointer;
    padding: 4px 0;
  }
}

.widget-name {
  font-weight: 500;
  font-size: 0.95rem;
  line-height: 1.3;
}

.widget-description {
  margin-top: 2px;
  font-size: 0.8rem;
  line-height: 1.2;
  color: #6c757d;
}
</style>
