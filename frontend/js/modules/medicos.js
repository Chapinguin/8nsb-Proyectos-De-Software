/**
 * Medicos Module - Hospital HIS
 */

window.Modules.medicos = {
  data: [],
  filteredData: [],
  especialidades: [],
  hospitales: [],

  async init() {
    this.renderLayout();
    await Promise.all([this.loadEspecialidades(), this.loadHospitales()]);
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");
    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="flex: 1; max-width: 400px;">
          <input type="text" id="medicosSearch" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar médico por nombre, apellido o expediente...">
        </div>
        <button id="addMedicoBtn" class="btn btn-primary">
          <span>+</span> Registrar Médico
        </button>
      </div>
      
      <div id="medicosTableContainer" class="table-container">
        <!-- Table will be rendered here -->
      </div>
    `;

    document.getElementById("medicosSearch").addEventListener("input", (e) => this.filter(e.target.value));
    document.getElementById("addMedicoBtn").addEventListener("click", () => this.showModal());
  },

  async loadEspecialidades() {
    try {
      const response = await fetch("../api/especialidades/listar_especialidades.php", { credentials: "include" });
      const res = await response.json();
      if (res.ok) this.especialidades = res.data;
    } catch (error) {
      console.error("Error al cargar especialidades:", error);
    }
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
      const response = await fetch("../api/medicos/listar_medicos.php", { credentials: "include" });
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
    const container = document.getElementById("medicosTableContainer");
    
    if (this.filteredData.length === 0) {
      container.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--text-light);">No hay médicos registrados.</div>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>Expediente</th>
            <th>Nombre Completo</th>
            <th>Especialidad</th>
            <th>Hospital</th>
            <th>Contacto</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(item => {
      const fullName = `${item.NOMBRE} ${item.APELLIDOPATERNO} ${item.APELLIDOMATERNO}`;
      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.EXPEDIENTE}</span></td>
          <td>
            <div style="font-weight: 600;">${fullName}</div>
          </td>
          <td><span class="btn btn-secondary" style="font-size: 0.7rem; padding: 2px 8px; cursor: default;">${item.ESPECIALIDAD}</span></td>
          <td><span style="font-size: 0.85rem; color: var(--text-light);">${item.HOSPITAL}</span></td>
          <td style="font-size: 0.8rem;">
            <div>📱 ${item.TELEFONOMOVIL || 'S/N'}</div>
            <div>🏠 ${item.TELEFONOCASA || 'S/N'}</div>
          </td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm" onclick="Modules.medicos.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>
            <button class="btn btn-secondary btn-sm" onclick="Modules.medicos.confirmDelete(${item.EXPEDIENTE})">🗑️</button>
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
      const fullName = `${item.NOMBRE} ${item.APELLIDOPATERNO} ${item.APELLIDOMATERNO}`.toLowerCase();
      return fullName.includes(q) || 
             item.EXPEDIENTE.toString().includes(q) ||
             item.ESPECIALIDAD.toLowerCase().includes(q) ||
             item.HOSPITAL.toLowerCase().includes(q);
    });
    this.renderTable();
  },

  showModal(item = null) {
    const isEdit = item !== null;
    const title = isEdit ? "Editar Información del Médico" : "Registrar Nuevo Médico";
    
    let especialidadOptions = this.especialidades.map(e => 
      `<option value="${e.ID}" ${isEdit && item.ESPECIALIDADES_ID == e.ID ? 'selected' : ''}>${e.ESPECIALIDAD}</option>`
    ).join('');

    let hospitalOptions = this.hospitales.map(h => 
      `<option value="${h.UNI_ORG}" ${isEdit && item.HOSPITAL_UNI_ORG === h.UNI_ORG ? 'selected' : ''}>${h.NOMUO} (${h.UNI_ORG})</option>`
    ).join('');

    const body = `
      <form id="medicoForm">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="m_expediente">N° Expediente</label>
            <input type="number" id="m_expediente" value="${item ? item.EXPEDIENTE : ''}" ${isEdit ? 'readonly' : ''} placeholder="Ej: 123456" required>
          </div>
          <div class="form-group">
            <label for="m_nombre">Nombre(s)</label>
            <input type="text" id="m_nombre" value="${item ? item.NOMBRE : ''}" placeholder="Ej: Luis Alberto" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="m_paterno">Apellido Paterno</label>
            <input type="text" id="m_paterno" value="${item ? item.APELLIDOPATERNO : ''}" placeholder="Ej: García" required>
          </div>
          <div class="form-group">
            <label for="m_materno">Apellido Materno</label>
            <input type="text" id="m_materno" value="${item ? item.APELLIDOMATERNO : ''}" placeholder="Ej: Pérez" required>
          </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="m_movil">Teléfono Móvil</label>
            <input type="number" id="m_movil" value="${item ? item.TELEFONOMOVIL : ''}" placeholder="Ej: 5512345678">
          </div>
          <div class="form-group">
            <label for="m_casa">Teléfono Casa</label>
            <input type="number" id="m_casa" value="${item ? item.TELEFONOCASA : ''}" placeholder="Ej: 5598765432">
          </div>
        </div>

        <div class="form-group">
          <label for="m_especialidad">Especialidad</label>
          <select id="m_especialidad" required>
            <option value="">Seleccione una especialidad...</option>
            ${especialidadOptions}
          </select>
        </div>

        <div class="form-group">
          <label for="m_hospital">Hospital de Adscripción</label>
          <select id="m_hospital" required>
            <option value="">Seleccione un hospital...</option>
            ${hospitalOptions}
          </select>
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveMedicoBtn">${isEdit ? 'Actualizar Datos' : 'Registrar Médico'}</button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveMedicoBtn").addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      expediente: document.getElementById("m_expediente").value,
      nombre: document.getElementById("m_nombre").value.trim(),
      apellidoPaterno: document.getElementById("m_paterno").value.trim(),
      apellidoMaterno: document.getElementById("m_materno").value.trim(),
      telefonoMovil: document.getElementById("m_movil").value,
      telefonoCasa: document.getElementById("m_casa").value,
      especialidadesId: document.getElementById("m_especialidad").value,
      hospitalUniOrg: document.getElementById("m_hospital").value
    };

    if (!data.expediente || !data.nombre || !data.apellidoPaterno || !data.apellidoMaterno || !data.especialidadesId || !data.hospitalUniOrg) {
      UI.toast.show("Los campos con asterisco o obligatorios son necesarios", "warning");
      return;
    }

    const endpoint = isEdit ? "editar_medicos.php" : "insertar_medicos.php";
    
    try {
      const response = await fetch(`../api/medicos/${endpoint}`, {
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

  confirmDelete(expediente) {
    const body = `<p>¿Estás seguro de que deseas eliminar al médico con expediente <strong>#${expediente}</strong>? Esta acción no se puede deshacer.</p>`;
    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.medicos.delete(${expediente})">Eliminar Permanente</button>
    `;
    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(expediente) {
    try {
      const response = await fetch("../api/medicos/eliminar_medicos.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ expediente }),
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
