/**
 * Roles Module - Hospital HIS
 */

window.Modules.roles = {
  data: [],

  init() {
    this.renderLayout();
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");
    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h2 style="font-size: 1.1rem;">Gestión de Roles del Sistema</h2>
          <p style="font-size: 0.85rem; color: var(--text-light);">Define los niveles de acceso para los empleados.</p>
        </div>
        <button id="addRolBtn" class="btn btn-primary">
          <span>+</span> Nuevo Rol
        </button>
      </div>
      
      <div id="rolesTableContainer" class="table-container">
        <!-- Table will be rendered here -->
      </div>
    `;

    document.getElementById("addRolBtn").addEventListener("click", () => this.showModal());
  },

  async loadData() {
    try {
      const response = await fetch("../api/roles/listar.php", { credentials: "include" });
      const res = await response.json();
      
      if (res.ok) {
        this.data = res.data;
        this.renderTable();
      } else {
        UI.toast.show(res.message, "error");
      }
    } catch (error) {
      UI.toast.show("Error al cargar roles", "error");
    }
  },

  renderTable() {
    const container = document.getElementById("rolesTableContainer");
    
    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre del Rol</th>
            <th>Descripción</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.data.forEach(item => {
      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.id}</span></td>
          <td><span class="btn btn-secondary" style="font-size: 0.75rem; cursor: default;">${item.nombre}</span></td>
          <td style="color: var(--text-light); font-size: 0.85rem;">${item.descripcion || 'Sin descripción'}</td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm" onclick="Modules.roles.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>
            <button class="btn btn-secondary btn-sm" onclick="Modules.roles.confirmDelete(${item.id})">🗑️</button>
          </td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
  },

  showModal(item = null) {
    const isEdit = item !== null;
    const title = isEdit ? "Editar Rol" : "Nuevo Rol";
    
    const body = `
      <form id="rolForm">
        <div class="form-group">
          <label for="r_nombre">Nombre del Rol</label>
          <input type="text" id="r_nombre" value="${item ? item.nombre : ''}" placeholder="Ej: Auditor" required>
        </div>
        <div class="form-group">
          <label for="r_desc">Descripción</label>
          <input type="text" id="r_desc" value="${item ? (item.descripcion || '') : ''}" placeholder="Ej: Acceso a reportes financieros">
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveRolBtn">${isEdit ? 'Actualizar' : 'Guardar'}</button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveRolBtn").addEventListener("click", () => this.save(isEdit, item?.id));
  },

  async save(isEdit, id = null) {
    const nombre = document.getElementById("r_nombre").value.trim();
    const descripcion = document.getElementById("r_desc").value.trim();

    if (!nombre) {
      UI.toast.show("El nombre del rol es obligatorio", "warning");
      return;
    }

    const endpoint = isEdit ? "editar.php" : "insertar.php";
    const payload = isEdit ? { id, nombre, descripcion } : { nombre, descripcion };
    
    try {
      const response = await fetch(`../api/roles/${endpoint}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        credentials: "include"
      });

      const res = await response.json();

      if (res.ok) {
        UI.toast.show(res.message, "success");
        UI.modal.close();
        this.loadData();
      } else {
        UI.toast.show(res.message, "error");
      }
    } catch (error) {
      UI.toast.show("Error al procesar", "error");
    }
  },

  confirmDelete(id) {
    const body = `<p>¿Estás seguro de que deseas eliminar este rol? Los usuarios asignados a él podrían perder acceso.</p>`;
    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.roles.delete(${id})">Eliminar</button>
    `;
    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const response = await fetch("../api/roles/eliminar.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id }),
        credentials: "include"
      });

      const res = await response.json();

      if (res.ok) {
        UI.toast.show(res.message, "success");
        UI.modal.close();
        this.loadData();
      } else {
        UI.toast.show(res.message, "error");
      }
    } catch (error) {
      UI.toast.show("Error al eliminar", "error");
    }
  }
};
