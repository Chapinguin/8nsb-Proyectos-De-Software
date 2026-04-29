/**
 * Estudios Module - Hospital HIS
 */

window.Modules.estudios = {
  data: [],
  filteredData: [],
  tiposEstudio: [],
  areas: [],

  init() {
    this.renderLayout();
    this.loadTiposEstudio();
    this.loadAreas();
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");

    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="flex: 1; max-width: 400px;">
          <input 
            type="text" 
            id="searchEstudio" 
            class="form-group" 
            style="margin-bottom: 0; width: 100%;" 
            placeholder="🔍 Buscar estudio..."
          >
        </div>

        <button id="addEstudioBtn" class="btn btn-primary">
          <span>+</span> Registrar Estudio
        </button>
      </div>

      <div id="estudioTableContainer" class="table-container"></div>
    `;

    document.getElementById("searchEstudio")
      .addEventListener("input", e => this.filter(e.target.value));

    document.getElementById("addEstudioBtn")
      .addEventListener("click", () => this.showModal());
  },

  async loadTiposEstudio() {
    try {
      const res = await fetch("../api/tipoestudios/listar_tipoestudios.php", {
        credentials: "include"
      });

      const data = await res.json();

      if (data.ok) {
        this.tiposEstudio = data.data;
      } else {
        UI.toast.show("Error cargando tipos de estudio", "error");
      }
    } catch {
      UI.toast.show("Error cargando tipos de estudio", "error");
    }
  },

  async loadAreas() {
    try {
      const res = await fetch("../api/areas/listar_areas.php", {
        credentials: "include"
      });

      const data = await res.json();

      if (data.ok) {
        // 🔥 Formateamos aquí: "Hospital - Área"
        this.areas = data.data.map(a => ({
          ...a,
          DISPLAY: `${a.HOSPITAL} - ${a.NOMBREAREA}`
        }));

        // opcional: ordenar bonito
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
      const res = await fetch("../api/estudios/listar_estudios.php", {
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
      UI.toast.show("Error cargando estudios", "error");
    }
  },

  renderTable() {
    const container = document.getElementById("estudioTableContainer");

    if (this.filteredData.length === 0) {
      container.innerHTML = `
        <div style="padding: 2rem; text-align: center; color: var(--text-light);">
          No hay estudios registrados.
        </div>
      `;
      return;
    }

    const estatusMap = {
      0: 'Pendiente',
      1: 'Completado',
      2: 'Cancelado'
    };

    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Tipo</th>
            <th>Médico</th>
            <th>Fecha</th>
            <th>Estatus</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(item => {
      const medico = `${item.NOMBRE} ${item.APELLIDOPATERNO} ${item.APELLIDOMATERNO}`;

      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.ID}</span></td>
          <td style="font-weight: 600;">${item.NOMBREESTUDIO}</td>
          <td>${medico}</td>
          <td style="font-size: 0.85rem;">${item.FECHAESTUDIO || 'N/A'}</td>
          <td>${estatusMap[item.ESTATUS] || 'N/A'}</td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm"
              onclick="Modules.estudios.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>

            <button class="btn btn-secondary btn-sm"
              onclick="Modules.estudios.confirmDelete(${item.ID})">🗑️</button>
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
      item.NOMBREESTUDIO.toLowerCase().includes(q) ||
      item.NOMBRE.toLowerCase().includes(q) ||
      String(item.ID).includes(q)
    );

    this.renderTable();
  },

  renderTiposOptions(selectedId = null) {
    return this.tiposEstudio.map(t => `
      <option value="${t.ID}" ${selectedId == t.ID ? 'selected' : ''}>
        ${t.NOMBREESTUDIO}
      </option>
    `).join("");
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
    const title = isEdit ? "Editar Estudio" : "Registrar Estudio";

    const body = `
      <form id="estudioForm">

        <div class="form-group">
          <label>ID</label>
          <input type="number" id="e_id" value="${item ? item.ID : ''}" ${isEdit ? 'readonly' : ''} required>
        </div>

        <div class="form-group">
          <label>Tipo de Estudio</label>
          <select id="e_tipo" required>
            <option value="">Seleccione un tipo</option>
            ${this.renderTiposOptions(item ? item.TIPOESTUDIOS_ID : null)}
          </select>
        </div>

        <div class="form-group">
          <label>Área</label>
          <select id="e_area">
            <option value="">Seleccione un área</option>
            ${this.renderAreasOptions()}
          </select>
        </div>

        <div class="form-group">
          <label>Expediente Médico</label>
          <input type="number" id="e_medico" value="${item ? item.MEDICOS_EXPEDIENTE : ''}" required>
        </div>

        <div class="form-group">
          <label>Fecha del Estudio</label>
          <input type="date" id="e_fecha" value="${item ? (item.FECHAESTUDIO || '') : ''}">
        </div>

        <div class="form-group">
          <label>Estatus</label>
          <input type="number" id="e_estatus" value="${item ? (item.ESTATUS ?? '') : ''}">
        </div>

      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveEstudioBtn">
        ${isEdit ? 'Actualizar' : 'Registrar'}
      </button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveEstudioBtn")
      .addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      id: parseInt(document.getElementById("e_id").value),
      tipoEstudiosId: parseInt(document.getElementById("e_tipo").value),
      medicosExpediente: parseInt(document.getElementById("e_medico").value),
      fechaEstudio: document.getElementById("e_fecha").value,
      estatus: document.getElementById("e_estatus").value
    };

    if (!data.id || !data.tipoEstudiosId || !data.medicosExpediente) {
      UI.toast.show("ID, tipo y médico son obligatorios", "warning");
      return;
    }

    const url = isEdit
      ? "../api/estudios/editar_estudios.php"
      : "../api/estudios/insertar_estudios.php";

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
    const body = `<p>¿Seguro que deseas eliminar el estudio con ID <strong>${id}</strong>?</p>`;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.estudios.delete(${id})">
        Eliminar
      </button>
    `;

    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const res = await fetch("../api/estudios/eliminar_estudios.php", {
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