/**
 * Departamento Module - Hospital HIS
 */

window.Modules.departamentos = {
  data: [],
  filteredData: [],
  areas: [],

  init() {
    this.renderLayout();
    this.loadAreas();
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");

    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="flex: 1; max-width: 400px;">
          <input type="text" id="departamentoSearch" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar departamento...">
        </div>
        <button id="addDepartamentoBtn" class="btn btn-primary">
          <span>+</span> Registrar Departamento
        </button>
      </div>
      
      <div id="departamentoTableContainer" class="table-container"></div>
    `;

    document.getElementById("departamentoSearch")
      .addEventListener("input", (e) => this.filter(e.target.value));

    document.getElementById("addDepartamentoBtn")
      .addEventListener("click", () => this.showModal());
  },

  async loadAreas() {
    try {
      const res = await fetch("../api/areas/listar_areas.php", {
        credentials: "include"
      });

      const data = await res.json();

      if (data.ok) {
        this.areas = data.data;
      } else {
        UI.toast.show("Error cargando áreas", "error");
      }
    } catch {
      UI.toast.show("Error cargando áreas", "error");
    }
  },

  async loadData() {
    try {
      const response = await fetch("../api/departamentos/listar_departamentos.php", {
        credentials: "include"
      });

      const res = await response.json();

      if (res.ok) {
        this.data = res.data;
        this.filteredData = [...this.data];
        this.renderTable();
      } else {
        UI.toast.show(res.message, "error");
      }
    } catch {
      UI.toast.show("Error al cargar datos", "error");
    }
  },

  renderTable() {
    const container = document.getElementById("departamentoTableContainer");

    if (this.filteredData.length === 0) {
      container.innerHTML = `<div style="padding:2rem;text-align:center;color:var(--text-light);">No hay departamentos registrados.</div>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Departamento</th>
            <th>Área</th>
            <th>Ubicación</th>
            <th style="text-align:right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(item => {
      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.ID}</span></td>
          </td>
          <td style="font-weight:600;">${item.NOMBREDEPARTAMENTO}</td>
          <td>${item.NOMBREAREA}</td>
          <td style="font-size:0.85rem;">${item.UBICACION || 'N/A'}</td>
          <td style="text-align:right;">
            <button class="btn btn-secondary btn-sm"
              onclick="Modules.departamentos.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>
            <button class="btn btn-secondary btn-sm"
              onclick="Modules.departamentos.confirmDelete(${item.ID})">🗑️</button>
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
      item.NOMBREDEPARTAMENTO.toLowerCase().includes(q) ||
      item.NOMBREAREA.toLowerCase().includes(q) ||
      String(item.ID).includes(q)
    );

    this.renderTable();
  },

  renderAreasOptions(selectedId = null) {
    return this.areas.map(a => `
      <option value="${a.ID}" ${selectedId == a.ID ? 'selected' : ''}>
        ${a.NOMBREAREA}
      </option>
    `).join("");
  },

  showModal(item = null) {
    const isEdit = item !== null;

    const title = isEdit
      ? "Editar Departamento"
      : "Registrar Departamento";

    const body = `
      <form id="departamentoForm">
        <div class="form-group">
          <label>ID</label>
          <input type="number" id="d_id" value="${item ? item.ID : ''}" ${isEdit ? 'readonly' : ''} required>
        </div>

        <div class="form-group">
          <label>Nombre del Departamento</label>
          <input type="text" id="d_nombre" value="${item ? item.NOMBREDEPARTAMENTO : ''}" required>
        </div>

        <div class="form-group">
          <label>Área</label>
          <select id="d_area" required>
            <option value="">Seleccione un área</option>
            ${this.renderAreasOptions(item ? item.AREAS_ID : null)}
          </select>
        </div>

        <div class="form-group">
          <label>Ubicación</label>
          <input type="text" id="d_ubicacion" value="${item ? (item.UBICACION || '') : ''}">
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveDepartamentoBtn">
        ${isEdit ? "Actualizar" : "Registrar"}
      </button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveDepartamentoBtn")
      .addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      id: parseInt(document.getElementById("d_id").value),
      nombreDepartamento: document.getElementById("d_nombre").value.trim(),
      ubicacion: document.getElementById("d_ubicacion").value.trim(),
      areasId: parseInt(document.getElementById("d_area").value)
    };

    if (!data.id || !data.nombreDepartamento || !data.areasId) {
      UI.toast.show("ID, nombre y área son obligatorios", "warning");
      return;
    }

    const endpoint = isEdit
      ? "editar_departamentos.php"
      : "insertar_departamentos.php";

    const method = isEdit ? "PUT" : "POST";

    try {
      const response = await fetch(`../api/departamentos/${endpoint}`, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
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
    } catch {
      UI.toast.show("Error al procesar", "error");
    }
  },

  confirmDelete(id) {
    const body = `<p>¿Eliminar el departamento con ID <strong>${id}</strong>?</p>`;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.departamentos.delete(${id})">
        Eliminar
      </button>
    `;

    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const response = await fetch("../api/departamentos/eliminar_departamentos.php", {
        method: "DELETE",
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
    } catch {
      UI.toast.show("Error al eliminar", "error");
    }
  }
};