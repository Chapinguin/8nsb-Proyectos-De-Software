/**
 * Especialidades Module - Hospital HIS
 */

window.Modules.especialidades = {
  data: [],
  filteredData: [],

  init() {
    this.renderLayout();
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");
    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="flex: 1; max-width: 400px;">
          <input type="text" id="especialidadesSearch" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar especialidad...">
        </div>
        <button id="addEspecialidadBtn" class="btn btn-primary">
          <span>+</span> Nueva Especialidad
        </button>
      </div>
      
      <div id="especialidadesTableContainer" class="table-container">
        <!-- Table will be rendered here -->
      </div>
    `;

    document.getElementById("especialidadesSearch").addEventListener("input", (e) => this.filter(e.target.value));
    document.getElementById("addEspecialidadBtn").addEventListener("click", () => this.showModal());
  },

  async loadData() {
    try {
      const response = await fetch("../api/especialidades/listar_especialidades.php", { credentials: "include" });
      const res = await response.json();
      
      if (res.ok) {
        this.data = res.data;
        this.filteredData = [...this.data];
        this.renderTable();
      } else {
        UI.toast.show(res.message, "error");
      }
    } catch (error) {
      UI.toast.show("Error al cargar datos", "error");
    }
  },

  renderTable() {
    const container = document.getElementById("especialidadesTableContainer");
    
    if (this.filteredData.length === 0) {
      container.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--text-light);">No se encontraron especialidades.</div>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre de la Especialidad</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(item => {
      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.ID}</span></td>
          <td>${item.ESPECIALIDAD}</td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm" onclick="Modules.especialidades.showModal(${item.ID}, '${item.ESPECIALIDAD}')">✏️</button>
            <button class="btn btn-secondary btn-sm" onclick="Modules.especialidades.confirmDelete(${item.ID})">🗑️</button>
          </td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
  },

  filter(query) {
    const q = query.toLowerCase();
    this.filteredData = this.data.filter(item => 
      item.ESPECIALIDAD.toLowerCase().includes(q) || 
      item.ID.toString().includes(q)
    );
    this.renderTable();
  },

  showModal(id = null, name = "") {
    const isEdit = id !== null;
    const title = isEdit ? "Editar Especialidad" : "Nueva Especialidad";
    
    const body = `
      <form id="especialidadForm">
        <div class="form-group">
          <label for="e_id">ID de la Especialidad</label>
          <input type="number" id="e_id" value="${id || ''}" ${isEdit ? 'readonly' : ''} placeholder="Ej: 10" required>
        </div>
        <div class="form-group">
          <label for="e_name">Nombre de la Especialidad</label>
          <input type="text" id="e_name" value="${name}" placeholder="Ej: Cardiología" required>
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveEspecialidadBtn">${isEdit ? 'Actualizar' : 'Guardar'}</button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveEspecialidadBtn").addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const id = document.getElementById("e_id").value;
    const name = document.getElementById("e_name").value;

    if (!id || !name) {
      UI.toast.show("Todos los campos son obligatorios", "warning");
      return;
    }

    const endpoint = isEdit ? "editar_especialidades.php" : "insertar_especialidades.php";
    
    try {
      const response = await fetch(`../api/especialidades/${endpoint}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: id, especialidad: name }),
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
      UI.toast.show("Error al procesar la solicitud", "error");
    }
  },

  confirmDelete(id) {
    const body = `<p>¿Estás seguro de que deseas eliminar la especialidad <strong>#${id}</strong>? Esta acción no se puede deshacer.</p>`;
    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.especialidades.delete(${id})">Eliminar definitivamente</button>
    `;
    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const response = await fetch("../api/especialidades/eliminar_especialidades.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: id }),
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
