/**
 * Areas Module - Hospital HIS
 */

window.Modules.areas = {
  data: [],
  filteredData: [],
  hospitales: [],

  async init() {
    this.renderLayout();
    await this.loadHospitales();
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");
    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="flex: 1; max-width: 400px;">
          <input type="text" id="areasSearch" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar área o ubicación...">
        </div>
        <button id="addAreaBtn" class="btn btn-primary">
          <span>+</span> Nueva Área
        </button>
      </div>
      
      <div id="areasTableContainer" class="table-container">
        <!-- Table will be rendered here -->
      </div>
    `;

    document.getElementById("areasSearch").addEventListener("input", (e) => this.filter(e.target.value));
    document.getElementById("addAreaBtn").addEventListener("click", () => this.showModal());
  },

  async loadHospitales() {
    try {
      const response = await fetch("../api/hospital/listar_hospital.php", { credentials: "include" });
      const res = await response.json();
      if (res.ok) this.hospitales = res.data;
    } catch (error) {
      console.error("Error al cargar hospitales:", error);
    }
  },

  async loadData() {
    try {
      const response = await fetch("../api/areas/listar_areas.php", { credentials: "include" });
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
    const container = document.getElementById("areasTableContainer");
    
    if (this.filteredData.length === 0) {
      container.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--text-light);">No se encontraron áreas.</div>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre del Área</th>
            <th>Ubicación</th>
            <th>Hospital</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(item => {
      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.ID}</span></td>
          <td>${item.NOMBREAREA}</td>
          <td><small style="color: var(--text-light);">${item.UBICACION || 'N/A'}</small></td>
          <td><span class="btn btn-secondary" style="font-size: 0.7rem; padding: 2px 8px;">${item.HOSPITAL_UNI_ORG}</span></td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm" onclick="Modules.areas.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>
            <button class="btn btn-secondary btn-sm" onclick="Modules.areas.confirmDelete(${item.ID})">🗑️</button>
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
      item.NOMBREAREA.toLowerCase().includes(q) || 
      (item.UBICACION && item.UBICACION.toLowerCase().includes(q)) ||
      item.ID.toString().includes(q)
    );
    this.renderTable();
  },

  showModal(item = null) {
    const isEdit = item !== null;
    const title = isEdit ? "Editar Área" : "Nueva Área";
    
    let hospitalOptions = this.hospitales.map(h => 
      `<option value="${h.UNI_ORG}" ${isEdit && item.HOSPITAL_UNI_ORG === h.UNI_ORG ? 'selected' : ''}>${h.NOMUO} (${h.UNI_ORG})</option>`
    ).join('');

    const body = `
      <form id="areaForm">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="a_id">ID Área</label>
            <input type="number" id="a_id" value="${item ? item.ID : ''}" ${isEdit ? 'readonly' : ''} required>
          </div>
          <div class="form-group">
            <label for="a_id1">ID1 (Interno)</label>
            <input type="number" id="a_id1" value="${item ? item.ID1 : ''}" required>
          </div>
        </div>
        <div class="form-group">
          <label for="a_name">Nombre del Área</label>
          <input type="text" id="a_name" value="${item ? item.NOMBREAREA : ''}" placeholder="Ej: Urgencias" required>
        </div>
        <div class="form-group">
          <label for="a_ubicacion">Ubicación / Piso</label>
          <input type="text" id="a_ubicacion" value="${item ? (item.UBICACION || '') : ''}" placeholder="Ej: Planta Baja, Ala Norte">
        </div>
        <div class="form-group">
          <label for="a_hospital">Hospital</label>
          <select id="a_hospital" required>
            <option value="">Seleccione un hospital...</option>
            ${hospitalOptions}
          </select>
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveAreaBtn">${isEdit ? 'Actualizar' : 'Guardar'}</button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveAreaBtn").addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      id: document.getElementById("a_id").value,
      id1: document.getElementById("a_id1").value,
      nombreArea: document.getElementById("a_name").value,
      ubicacion: document.getElementById("a_ubicacion").value,
      hospitalUniOrg: document.getElementById("a_hospital").value
    };

    if (!data.id || !data.nombreArea || !data.hospitalUniOrg) {
      UI.toast.show("ID, Nombre y Hospital son obligatorios", "warning");
      return;
    }

    const endpoint = isEdit ? "editar_areas.php" : "insertar_areas.php";
    
    try {
      const response = await fetch(`../api/areas/${endpoint}`, {
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
    const body = `<p>¿Estás seguro de que deseas eliminar el área <strong>#${id}</strong>?</p>`;
    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.areas.delete(${id})">Eliminar</button>
    `;
    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const response = await fetch("../api/areas/eliminar_areas.php", {
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
