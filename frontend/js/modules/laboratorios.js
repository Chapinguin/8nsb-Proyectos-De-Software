/**
 * Laboratorios Module - Hospital HIS
 */

window.Modules.laboratorios = {
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
          <input type="text" id="searchLab" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar laboratorio por nombre o área...">
        </div>
        <button id="addLabBtn" class="btn btn-primary">
          <span>+</span> Registrar Laboratorio
        </button>
      </div>

      <div id="labTableContainer" class="table-container"></div>
    `;

    document.getElementById("searchLab")
      .addEventListener("input", e => this.filter(e.target.value));

    document.getElementById("addLabBtn")
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
      const res = await fetch("../api/laboratorios/listar_laboratorios.php", {
        credentials: "include"
      });

      const data = await res.json();

      if (data.ok) {
        this.data = data.data;
        this.filteredData = [...this.data];
        this.renderTable();
      } else {
        UI.toast.show(data.message, "error");
      }
    } catch {
      UI.toast.show("Error cargando datos", "error");
    }
  },

  renderTable() {
    const container = document.getElementById("labTableContainer");

    if (this.filteredData.length === 0) {
      container.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--text-light);">No hay laboratorios registrados.</div>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Área</th>
            <th>Ubicación</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(item => {
      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.ID}</span></td>
          </td>
          <td style="font-weight: 600;">${item.NOMBRELABORATORIO}</td>
          <td>${item.NOMBREAREA}</td>
          <td style="font-size: 0.85rem;">${item.UBICACION || 'N/A'}</td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm"
              onclick="Modules.laboratorios.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>
            <button class="btn btn-secondary btn-sm"
              onclick="Modules.laboratorios.confirmDelete(${item.ID})">🗑️</button>
          </td>
        </tr>
      `;
    });

    html += "</tbody></table>";
    container.innerHTML = html;
  },

  filter(query) {
    const q = query.toLowerCase();

    this.filteredData = this.data.filter(item =>
      item.NOMBRELABORATORIO.toLowerCase().includes(q) ||
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
    const title = isEdit ? "Editar Laboratorio" : "Registrar Laboratorio";

    const body = `
      <form id="labForm">
        <div class="form-group">
          <label>ID</label>
          <input type="number" id="l_id" value="${item ? item.ID : ''}" ${isEdit ? 'readonly' : ''} required>
        </div>

        <div class="form-group">
          <label>Nombre del Laboratorio</label>
          <input type="text" id="l_nombre" value="${item ? item.NOMBRELABORATORIO : ''}" required>
        </div>

        <div class="form-group">
          <label>Área</label>
          <select id="l_area" required>
            <option value="">Seleccione un área</option>
            ${this.renderAreasOptions(item ? item.AREAS_ID : null)}
          </select>
        </div>

        <div class="form-group">
          <label>Ubicación</label>
          <input type="text" id="l_ubicacion" value="${item ? (item.UBICACION || '') : ''}">
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveLabBtn">
        ${isEdit ? 'Actualizar' : 'Registrar'}
      </button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveLabBtn")
      .addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      id: parseInt(document.getElementById("l_id").value),
      nombreLaboratorio: document.getElementById("l_nombre").value.trim(),
      ubicacion: document.getElementById("l_ubicacion").value.trim(),
      areasId: parseInt(document.getElementById("l_area").value)
    };

    if (!data.id || !data.nombreLaboratorio || !data.areasId) {
      UI.toast.show("ID, nombre y área son obligatorios", "warning");
      return;
    }

    const url = isEdit
      ? "../api/laboratorios/editar_laboratorios.php"
      : "../api/laboratorios/insertar_laboratorios.php";

    const method = isEdit ? "PUT" : "POST";

    try {
      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
        credentials: "include"
      });

      const json = await res.json();

      if (json.ok) {
        UI.toast.show(json.message, "success");
        UI.modal.close();
        this.loadData();
      } else {
        UI.toast.show(json.message, "error");
      }

    } catch {
      UI.toast.show("Error en request", "error");
    }
  },

  confirmDelete(id) {
    const body = `<p>¿Seguro que deseas eliminar el laboratorio con ID <strong>${id}</strong>?</p>`;
    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.laboratorios.delete(${id})">
        Eliminar
      </button>
    `;

    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const res = await fetch("../api/laboratorios/eliminar_laboratorios.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id }),
        credentials: "include"
      });

      const json = await res.json();

      if (json.ok) {
        UI.toast.show(json.message, "success");
        UI.modal.close();
        this.loadData();
      } else {
        UI.toast.show(json.message, "error");
      }

    } catch {
      UI.toast.show("Error eliminando", "error");
    }
  }
};