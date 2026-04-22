/**
 * UI Core - Hospital HIS
 * Handles shared UI components like modals and toasts.
 */

const UI = {
  // Modal Management
  modal: {
    show: (title, bodyHtml, footerHtml = "") => {
      const overlay = document.getElementById("modalOverlay");
      const modalTitle = document.getElementById("modalTitle");
      const modalBody = document.getElementById("modalBody");
      const modalFooter = document.getElementById("modalFooter");

      modalTitle.textContent = title;
      modalBody.innerHTML = bodyHtml;
      modalFooter.innerHTML = footerHtml;

      overlay.style.display = "flex";
      document.body.style.overflow = "hidden";
    },
    close: () => {
      const overlay = document.getElementById("modalOverlay");
      overlay.style.display = "none";
      document.body.style.overflow = "auto";
    }
  },

  // Toast Management
  toast: {
    show: (message, type = "info", duration = 3000) => {
      const container = document.getElementById("toastContainer");
      const toast = document.createElement("div");
      toast.className = `toast ${type}`;
      
      const icon = type === "success" ? "✅" : (type === "error" ? "❌" : "ℹ️");
      
      toast.innerHTML = `
        <span>${icon}</span>
        <p>${message}</p>
      `;

      container.appendChild(toast);

      setTimeout(() => {
        toast.style.animation = "slideOutRight 0.3s ease forwards";
        setTimeout(() => toast.remove(), 300);
      }, duration);
    }
  },

  // Utils
  showSkeleton: (containerSelector, rows = 5) => {
    const container = document.querySelector(containerSelector);
    let skeletonHtml = `
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th class="skeleton" style="width: 100px; height: 1.5rem; border-radius: 4px;"></th>
              <th class="skeleton" style="height: 1.5rem; border-radius: 4px;"></th>
              <th class="skeleton" style="width: 120px; height: 1.5rem; border-radius: 4px;"></th>
            </tr>
          </thead>
          <tbody>
    `;
    
    for (let i = 0; i < rows; i++) {
      skeletonHtml += `
        <tr>
          <td class="skeleton" style="height: 1.25rem; margin: 0.5rem; border-radius: 4px;"></td>
          <td class="skeleton" style="height: 1.25rem; margin: 0.5rem; border-radius: 4px;"></td>
          <td class="skeleton" style="height: 1.25rem; margin: 0.5rem; border-radius: 4px;"></td>
        </tr>
      `;
    }

    skeletonHtml += `
          </tbody>
        </table>
      </div>
    `;
    
    container.innerHTML = skeletonHtml;
  }
};

// Global accessor
window.UI = UI;
window.closeModal = UI.modal.close;
