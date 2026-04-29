/**
 * Consultorios Module - Hospital HIS
 */

window.Modules.consultorios = {
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
          <input type="text" id="consultorioSearch" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar consultorio...">
        </div>

        <button id="addConsultorioBtn" class="btn btn-primary">
          <span>+</span> Registrar Consultorio
        </button>
      </div>

      <div id="consultorioTableContainer" class="table-container"></div>
    `;

    document.getElementById("consultorioSearch")
      .addEventListener("input", e => this.filter(e.target.value));

    document.getElementById("addConsultorioBtn")
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
      const response = await fetch("../api/consultorios/listar_consultorios.php", {
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
    const container = document.getElementById("consultorioTableContainer");

    if (this.filteredData.length === 0) {
      container.innerHTML = `
        <div style="padding:2rem;text-align:center;color:var(--text-light);">
          No hay consultorios registrados.
        </div>
      `;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Consultorio</th>
            <th>Área</th>
            <th>Ubicación</th>
            <th style="text-align:right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(item => {

      const area = this.areas.find(a => a.ID == item.AREAS_ID);
      const areaDisplay = area ? area.DISPLAY : item.NOMBREAREA;

      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.ID}</span></td>
          <td style="font-weight:600;">${item.CONSULTORIO}</td>
          <td>${areaDisplay}</td>
          <td style="font-size:0.85rem;">${item.UBICACION || 'N/A'}</td>
          <td style="text-align:right;">
            <button class="btn btn-secondary btn-sm"
              onclick="Modules.consultorios.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>

            <button class="btn btn-secondary btn-sm"
              onclick="Modules.consultorios.confirmDelete(${item.ID})">🗑️</button>
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
        item.CONSULTORIO.toLowerCase().includes(q) ||
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

    const title = isEdit
      ? "Editar Consultorio"
      : "Registrar Consultorio";

    const body = `
      <form id="consultorioForm">

        <div class="form-group">
          <label>ID</label>
          <input type="number" id="c_id" value="${item ? item.ID : ''}" ${isEdit ? 'readonly' : ''} required>
        </div>

        <div class="form-group">
          <label>Nombre del Consultorio</label>
          <input type="text" id="c_nombre" value="${item ? item.CONSULTORIO : ''}" required>
        </div>

        <div class="form-group">
          <label>Área</label>
          <select id="c_area" required>
            <option value="">Seleccione un área</option>
            ${this.renderAreasOptions(item ? item.AREAS_ID : null)}
          </select>
        </div>

        <div class="form-group">
          <label>Ubicación</label>
          <input type="text" id="c_ubicacion" value="${item ? (item.UBICACION || '') : ''}">
        </div>

      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveConsultorioBtn">
        ${isEdit ? "Actualizar" : "Registrar"}
      </button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveConsultorioBtn")
      .addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      id: parseInt(document.getElementById("c_id").value),
      consultorio: document.getElementById("c_nombre").value.trim(),
      ubicacion: document.getElementById("c_ubicacion").value.trim(),
      areasId: parseInt(document.getElementById("c_area").value)
    };

    if (!data.id || !data.consultorio || !data.areasId) {
      UI.toast.show("ID, consultorio y área son obligatorios", "warning");
      return;
    }

    const endpoint = isEdit
      ? "editar_consultorios.php"
      : "insertar_consultorios.php";

    const method = isEdit ? "PUT" : "POST";

    try {
      const response = await fetch(`../api/consultorios/${endpoint}`, {
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
    const body = `
      <p>¿Eliminar el consultorio con ID <strong>${id}</strong>?</p>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.consultorios.delete(${id})">
        Eliminar
      </button>
    `;

    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const response = await fetch("../api/consultorios/eliminar_consultorios.php", {
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