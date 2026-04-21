/**
 * Habitaciones Module - Hospital HIS
 */

window.Modules.habitaciones = {
  data: [],
  filteredData: [],
  areas: [],

  async init() {
    this.renderLayout();
    await this.loadAreas();
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");
    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="flex: 1; max-width: 400px;">
          <input type="text" id="habitacionesSearch" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar habitación por nombre o área...">
        </div>
        <button id="addHabitacionBtn" class="btn btn-primary">
          <span>+</span> Registrar Habitación
        </button>
      </div>
      
      <div id="habitacionesTableContainer" class="table-container">
        <!-- Table will be rendered here -->
      </div>
    `;

    document.getElementById("habitacionesSearch").addEventListener("input", (e) => this.filter(e.target.value));
    document.getElementById("addHabitacionBtn").addEventListener("click", () => this.showModal());
  },

  async loadAreas() {
    try {
      const response = await fetch("../api/areas/listar_areas.php", { credentials: "include" });
      const res = await response.json();
      if (res.ok) this.areas = res.data;
    } catch (error) {
      console.error("Error al cargar áreas:", error);
    }
  },

  async loadData() {
    try {
      UI.showSkeleton("#habitacionesTableContainer");
      const response = await fetch("../api/habitaciones/listar_habitaciones.php", { credentials: "include" });
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
    const container = document.getElementById("habitacionesTableContainer");
    
    if (this.filteredData.length === 0) {
      container.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--text-light);">No hay habitaciones registradas.</div>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Habitación</th>
            <th>Ubicación</th>
            <th>Área</th>
            <th>Equipamiento</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(item => {
      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.ID}</span></td>
          <td><div style="font-weight: 600;">${item.NOMBREHABITACION}</div></td>
          <td><span style="font-size: 0.85rem;">${item.UBICACION || 'N/A'}</span></td>
          <td><span class="btn btn-secondary" style="font-size: 0.7rem; padding: 2px 8px; cursor: default;">${item.NOMBREAREA}</span></td>
          <td title="${item.EQUIPAMIENTO || ''}"><span style="font-size: 0.8rem; color: var(--text-light);">${(item.EQUIPAMIENTO || 'N/A').substring(0, 30)}${(item.EQUIPAMIENTO || '').length > 30 ? '...' : ''}</span></td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm" onclick="Modules.habitaciones.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>
            <button class="btn btn-secondary btn-sm" onclick="Modules.habitaciones.confirmDelete(${item.ID})">🗑️</button>
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
      item.NOMBREHABITACION.toLowerCase().includes(q) || 
      item.ID.toString().includes(q) ||
      item.NOMBREAREA.toLowerCase().includes(q) ||
      (item.UBICACION && item.UBICACION.toLowerCase().includes(q))
    );
    this.renderTable();
  },

  showModal(item = null) {
    const isEdit = item !== null;
    const title = isEdit ? "Editar Habitación" : "Registrar Nueva Habitación";
    
    let areaOptions = this.areas.map(a => 
      `<option value="${a.ID}" ${isEdit && item.AREAS_ID == a.ID ? 'selected' : ''}>${a.NOMBREAREA}</option>`
    ).join('');

    const body = `
      <form id="habitacionForm">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="h_id">ID Habitación</label>
            <input type="number" id="h_id" value="${item ? item.ID : ''}" ${isEdit ? 'readonly' : ''} placeholder="Ej: 101" required>
          </div>
          <div class="form-group">
            <label for="h_nombre">Nombre/N° Habitación</label>
            <input type="text" id="h_nombre" value="${item ? item.NOMBREHABITACION : ''}" placeholder="Ej: Habitación 101" required>
          </div>
        </div>

        <div class="form-group">
          <label for="h_area">Área Hospitalaria</label>
          <select id="h_area" required>
            <option value="">Seleccione un área...</option>
            ${areaOptions}
          </select>
        </div>

        <div class="form-group">
          <label for="h_ubicacion">Ubicación Específica</label>
          <input type="text" id="h_ubicacion" value="${item ? item.UBICACION || '' : ''}" placeholder="Ej: Piso 1, Ala Norte">
        </div>

        <div class="form-group">
          <label for="h_equipamiento">Equipamiento / Notas</label>
          <textarea id="h_equipamiento" rows="3" placeholder="Ej: Cama articulada, Monitor de signos vitales...">${item ? item.EQUIPAMIENTO || '' : ''}</textarea>
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveHabitacionBtn">${isEdit ? 'Actualizar Habitación' : 'Registrar Habitación'}</button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveHabitacionBtn").addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      id: document.getElementById("h_id").value,
      nombreHabitacion: document.getElementById("h_nombre").value.trim(),
      areasId: document.getElementById("h_area").value,
      ubicacion: document.getElementById("h_ubicacion").value.trim(),
      equipamiento: document.getElementById("h_equipamiento").value.trim()
    };

    if (!data.id || !data.nombreHabitacion || !data.areasId) {
      UI.toast.show("ID, nombre y área son obligatorios", "warning");
      return;
    }

    const endpoint = isEdit ? "editar_habitaciones.php" : "insertar_habitaciones.php";
    
    try {
      const response = await fetch(`../api/habitaciones/${endpoint}`, {
        method: "POST",
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
    } catch (error) {
      UI.toast.show("Error al procesar la solicitud", "error");
    }
  },

  confirmDelete(id) {
    const body = `<p>¿Estás seguro de que deseas eliminar la habitación <strong>#${id}</strong>? Esta acción no se puede deshacer y puede afectar a registros de ingresos.</p>`;
    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.habitaciones.delete(${id})">Eliminar Permanente</button>
    `;
    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const response = await fetch("../api/habitaciones/eliminar_habitaciones.php", {
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
