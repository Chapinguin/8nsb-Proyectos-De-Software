/**
 * Tipo de Estudios Module - Hospital HIS
 */

window.Modules.tipoestudios = {
  tipos: [],
  laboratorios: [],

  async init() {
    this.renderLayout();
    await this.loadLaboratorios();
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");
    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
          <div style="flex: 1; max-width: 400px;">
            <input type="text" id="tipoSearch" class="form-group" style="margin-bottom: 0; width: 100%;" placeholder="🔍 Buscar tipo de estudio...">
          </div>
          <button id="addTipoBtn" class="btn btn-primary">
            <span>+</span> Nuevo Tipo de Estudio
          </button>
        </div>
      </div>
      
      <div id="tipoTableContainer" class="table-container"></div>
    `;

    document.getElementById("tipoSearch").addEventListener("input", (e) => this.filter(e.target.value));
    document.getElementById("addTipoBtn").addEventListener("click", () => this.showModal());
  },

  async loadLaboratorios() {
    try {
      const response = await fetch("../api/laboratorios/listar_laboratorios.php", { credentials: "include" });
      const res = await response.json();
      if (res.ok) this.laboratorios = res.data;
    } catch (error) {
      console.error("Error al cargar laboratorios:", error);
    }
  },

  async loadData() {
    try {
      UI.showSkeleton("#tipoTableContainer");
      const response = await fetch("../api/tipoestudios/listar_tipoestudios.php", { credentials: "include" });
      const res = await response.json();
      if (res.ok) {
        this.tipos = res.data;
        this.renderTable(this.tipos);
      }
    } catch (error) {
      UI.toast.show("Error al cargar datos", "error");
    }
  },

  renderTable(data) {
    const container = document.getElementById("tipoTableContainer");
    if (data.length === 0) {
      container.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--text-light);">No hay registros.</div>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre del Estudio</th>
            <th>Laboratorio</th>
            <th>Costo</th>
            <th>Requisitos</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    data.forEach(item => {
      html += `
        <tr>
          <td><span class="badge" style="background: var(--background); color: var(--text);">${item.ID}</span></td>
          <td><strong>${item.NOMBREESTUDIO}</strong></td>
          <td>${item.NOMBRELABORATORIO || 'No asignado'}</td>
          <td style="font-weight: 600; color: #10b981;">$${parseFloat(item.COSTO).toFixed(2)}</td>
          <td style="font-size: 0.85rem; max-width: 200px;" title="${item.REQUISITOSESTUDIO}">${item.REQUISITOSESTUDIO || 'N/A'}</td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm" onclick="Modules.tipoestudios.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>
            <button class="btn btn-secondary btn-sm" onclick="Modules.tipoestudios.confirmDelete(${item.ID})">🗑️</button>
          </td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
  },

  filter(query) {
    const q = query.toLowerCase();
    const filtered = this.tipos.filter(t => 
      t.NOMBREESTUDIO.toLowerCase().includes(q) || 
      (t.NOMBRELABORATORIO && t.NOMBRELABORATORIO.toLowerCase().includes(q))
    );
    this.renderTable(filtered);
  },

  showModal(item = null) {
    const isEdit = item !== null;
    const title = isEdit ? "Editar Tipo de Estudio" : "Nuevo Tipo de Estudio";
    
    let labOptions = this.laboratorios.map(l => 
      `<option value="${l.ID}" ${isEdit && item.LABORATORIOS_ID == l.ID ? 'selected' : ''}>${l.NOMBRELABORATORIO} (${l.NOMBREAREA})</option>`
    ).join('');

    const body = `
      <form id="tipoForm">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="t_id">ID</label>
            <input type="number" id="t_id" value="${item ? item.ID : ''}" ${isEdit ? 'readonly' : ''} required>
          </div>
          <div class="form-group">
            <label for="t_costo">Costo ($)</label>
            <input type="number" id="t_costo" step="0.01" value="${item ? item.COSTO : ''}" required>
          </div>
        </div>
        <div class="form-group">
          <label for="t_nombre">Nombre del Estudio</label>
          <input type="text" id="t_nombre" value="${item ? item.NOMBREESTUDIO : ''}" required>
        </div>
        <div class="form-group">
          <label for="t_lab">Laboratorio / Departamento</label>
          <select id="t_lab" required>
            <option value="">Seleccione un laboratorio...</option>
            ${labOptions}
          </select>
        </div>
        <div class="form-group">
          <label for="t_req">Requisitos</label>
          <textarea id="t_req" rows="3">${item ? item.REQUISITOSESTUDIO : ''}</textarea>
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveTipoBtn">${isEdit ? 'Actualizar' : 'Guardar'}</button>
    `;

    UI.modal.show(title, body, footer);
    document.getElementById("saveTipoBtn").addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      id: document.getElementById("t_id").value,
      nombreEstudio: document.getElementById("t_nombre").value,
      requisitosEstudio: document.getElementById("t_req").value,
      costo: document.getElementById("t_costo").value,
      laboratoriosId: document.getElementById("t_lab").value
    };

    const endpoint = isEdit ? "editar_tipoestudios.php" : "insertar_tipoestudios.php";
    
    try {
      const response = await fetch(`../api/tipoestudios/${endpoint}`, {
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
      UI.toast.show("Error al guardar", "error");
    }
  },

  confirmDelete(id) {
    UI.modal.show(
      "Confirmar Eliminación",
      `<p>¿Estás seguro de eliminar el tipo de estudio <strong>#${id}</strong>?</p>`,
      `<button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
       <button class="btn btn-danger" onclick="Modules.tipoestudios.delete(${id})">Eliminar</button>`
    );
  },

  async delete(id) {
    try {
      const response = await fetch("../api/tipoestudios/eliminar_tipoestudios.php", {
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
