/**
 * Hospital Module - Hospital HIS
 */

window.Modules.hospital = {
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
          <input type="text" id="hospitalSearch" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar hospital por nombre o dirección...">
        </div>
        <button id="addHospitalBtn" class="btn btn-primary">
          <span>+</span> Registrar Hospital
        </button>
      </div>
      
      <div id="hospitalTableContainer" class="table-container">
        <!-- Table will be rendered here -->
      </div>
    `;

    document.getElementById("hospitalSearch").addEventListener("input", (e) => this.filter(e.target.value));
    document.getElementById("addHospitalBtn").addEventListener("click", () => this.showModal());
  },

  async loadData() {
    try {
      const response = await fetch("../api/hospital/listar_hospital.php", { credentials: "include" });
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
    const container = document.getElementById("hospitalTableContainer");
    
    if (this.filteredData.length === 0) {
      container.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--text-light);">No hay hospitales registrados.</div>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>Clave (UNI_ORG)</th>
            <th>Nombre de la Institución</th>
            <th>Director</th>
            <th>Ubicación</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(item => {
      html += `
        <tr>
          <td><span class="btn btn-secondary" style="font-size: 0.75rem; cursor: default; font-weight: 700;">${item.UNI_ORG}</span></td>
          <td>
            <div style="font-weight: 600;">${item.NOMUO}</div>
            <div style="font-size: 0.75rem; color: var(--text-light);">Tel: ${item.TELEFONO || 'S/T'}</div>
          </td>
          <td style="font-size: 0.875rem;">${item.DIRECTOR || '<i>No asignado</i>'}</td>
          <td style="font-size: 0.85rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${item.DIRECCION || 'N/A'}</td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm" onclick="Modules.hospital.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>
            <button class="btn btn-secondary btn-sm" onclick="Modules.hospital.confirmDelete('${item.UNI_ORG}')">🗑️</button>
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
      item.NOMUO.toLowerCase().includes(q) || 
      item.DIRECCION.toLowerCase().includes(q) ||
      item.UNI_ORG.toLowerCase().includes(q)
    );
    this.renderTable();
  },

  showModal(item = null) {
    const isEdit = item !== null;
    const title = isEdit ? "Editar Información del Hospital" : "Registrar Nuevo Hospital";
    
    const body = `
      <form id="hospitalForm">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="h_uni">Clave Unidad (UNI_ORG)</label>
            <input type="text" id="h_uni" value="${item ? item.UNI_ORG : ''}" ${isEdit ? 'readonly' : ''} placeholder="Ej: H001" maxlength="5" required>
          </div>
          <div class="form-group">
            <label for="h_tel">Teléfono</label>
            <input type="number" id="h_tel" value="${item ? item.TELEFONO : ''}" placeholder="Solo números">
          </div>
        </div>
        
        <div class="form-group">
          <label for="h_nombre">Nombre del Hospital</label>
          <input type="text" id="h_nombre" value="${item ? item.NOMUO : ''}" placeholder="Ej: Hospital General de Especialidades" required>
        </div>

        <div class="form-group">
          <label for="h_director">Nombre del Director</label>
          <input type="text" id="h_director" value="${item ? (item.DIRECTOR || '') : ''}" placeholder="Dr./Dra. Nombre Apellido">
        </div>

        <div class="form-group">
          <label for="h_dir">Dirección Completa</label>
          <input type="text" id="h_dir" value="${item ? (item.DIRECCION || '') : ''}" placeholder="Calle, Número, Colonia, Ciudad">
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveHospitalBtn">${isEdit ? 'Actualizar Datos' : 'Registrar Institución'}</button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveHospitalBtn").addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      uni_org: document.getElementById("h_uni").value.trim(),
      nomuo: document.getElementById("h_nombre").value.trim(),
      direccion: document.getElementById("h_dir").value.trim(),
      director: document.getElementById("h_director").value.trim(),
      telefono: document.getElementById("h_tel").value
    };

    if (!data.uni_org || !data.nomuo) {
      UI.toast.show("La clave y el nombre son obligatorios", "warning");
      return;
    }

    const endpoint = isEdit ? "editar_hospital.php" : "insertar_hospital.php";
    
    try {
      const response = await fetch(`../api/hospital/${endpoint}`, {
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

  confirmDelete(uni_org) {
    const body = `<p>¿Estás seguro de que deseas eliminar el hospital con clave <strong>${uni_org}</strong>? Esto podría afectar a todas las áreas asociadas.</p>`;
    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.hospital.delete('${uni_org}')">Eliminar Definitivamente</button>
    `;
    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(uni_org) {
    try {
      const response = await fetch("../api/hospital/eliminar_hospital.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ uni_org }),
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
