/**
 * Quirófanos Module - Hospital HIS
 */

window.Modules.quirofanos = {
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
          <input type="text" id="quirofanoSearch" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar quirófano por nombre o área...">
        </div>

        <button id="addQuirofanoBtn" class="btn btn-primary">
          <span>+</span> Registrar Quirófano
        </button>
      </div>

      <div id="quirofanoTableContainer" class="table-container"></div>
    `;

    document.getElementById("quirofanoSearch")
      .addEventListener("input", e => this.filter(e.target.value));

    document.getElementById("addQuirofanoBtn")
      .addEventListener("click", () => this.showModal());
  },

  async loadAreas() {
    try {
      const res = await fetch("../api/areas/listar_areas.php", {
        credentials: "include"
      });

      const data = await res.json();

      if (data.ok) {
        this.areas = data.data.map(a => ({
          ...a,
          DISPLAY: a.HOSPITAL 
            ? `${a.HOSPITAL} - ${a.NOMBREAREA}` 
            : a.NOMBREAREA
        }));

        this.areas.sort((a, b) => a.DISPLAY.localeCompare(b.DISPLAY));

      } else {
        UI.toast.show("Error cargando áreas", "error");
      }

    } catch {
      UI.toast.show("Error cargando áreas", "error");
    }
  },

  async loadData() {
    try {
      const response = await fetch("../api/quirofanos/listar_quirofanos.php", {
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
    const container = document.getElementById("quirofanoTableContainer");

    if (this.filteredData.length === 0) {
      container.innerHTML = `
        <div style="padding: 2rem; text-align: center; color: var(--text-light);">
          No hay quirófanos registrados.
        </div>
      `;
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
      // 🔥 Buscar área formateada
      const area = this.areas.find(a => a.ID == item.AREAS_ID);
      const areaDisplay = area 
        ? area.DISPLAY 
        : item.NOMBREAREA;

      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.ID}</span></td>
          <td style="font-weight: 600;">${item.NOMBREQUIROFANO}</td>
          <td>${areaDisplay}</td>
          <td style="font-size: 0.85rem;">${item.UBICACION || 'N/A'}</td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm"
              onclick="Modules.quirofanos.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>

            <button class="btn btn-secondary btn-sm"
              onclick="Modules.quirofanos.confirmDelete(${item.ID})">🗑️</button>
          </td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
  },

  filter(query) {
    const q = query.toLowerCase();

    this.filteredData = this.data.filter(item => {
      const area = this.areas.find(a => a.ID == item.AREAS_ID);
      const areaDisplay = area ? area.DISPLAY.toLowerCase() : '';

      return (
        item.NOMBREQUIROFANO.toLowerCase().includes(q) ||
        areaDisplay.includes(q) ||
        item.NOMBREAREA.toLowerCase().includes(q) ||
        String(item.ID).includes(q)
      );
    });

    this.renderTable();
  },

  renderAreasOptions(selectedId = null) {
    return this.areas.map(a => `
      <option value="${a.ID}" ${selectedId == a.ID ? 'selected' : ''}>
        ${a.DISPLAY}
      </option>
    `).join("");
  },

  showModal(item = null) {
    const isEdit = item !== null;
    const title = isEdit ? "Editar Quirófano" : "Registrar Quirófano";

    const body = `
      <form id="quirofanoForm">

        <div class="form-group">
          <label>ID</label>
          <input type="number" id="q_id" value="${item ? item.ID : ''}" ${isEdit ? 'readonly' : ''} required>
        </div>

        <div class="form-group">
          <label>Nombre del Quirófano</label>
          <input type="text" id="q_nombre" value="${item ? item.NOMBREQUIROFANO : ''}" required>
        </div>

        <div class="form-group">
          <label>Área</label>
          <select id="q_area" required>
            <option value="">Seleccione un área</option>
            ${this.renderAreasOptions(item ? item.AREAS_ID : null)}
          </select>
        </div>

        <div class="form-group">
          <label>Ubicación</label>
          <input type="text" id="q_ubicacion" value="${item ? (item.UBICACION || '') : ''}">
        </div>

      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveQuirofanoBtn">
        ${isEdit ? 'Actualizar' : 'Registrar'}
      </button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveQuirofanoBtn")
      .addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      id: parseInt(document.getElementById("q_id").value),
      nombreQuirofano: document.getElementById("q_nombre").value.trim(),
      areasId: parseInt(document.getElementById("q_area").value),
      ubicacion: document.getElementById("q_ubicacion").value.trim()
    };

    if (!data.id || !data.nombreQuirofano || !data.areasId) {
      UI.toast.show("ID, nombre y área son obligatorios", "warning");
      return;
    }

    const endpoint = isEdit ? "editar_quirofanos.php" : "insertar_quirofanos.php";
    const method = isEdit ? "PUT" : "POST";

    try {
      const response = await fetch(`../api/quirofanos/${endpoint}`, {
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
      UI.toast.show("Error al procesar la solicitud", "error");
    }
  },

  confirmDelete(id) {
    const body = `
      <p>¿Seguro que deseas eliminar el quirófano con ID <strong>${id}</strong>?</p>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.quirofanos.delete(${id})">
        Eliminar
      </button>
    `;

    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const response = await fetch("../api/quirofanos/eliminar_quirofanos.php", {
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