/**
 * Reportes Module - Hospital HIS
 * Centralized reporting module with sub-sections
 */

window.Modules.reportes = {
  currentView: 'menu', // 'menu', 'urgencias', 'estudios'
  hospitales: [],
  selectedHospital: '',

  async init() {
    await this.loadHospitales();
    this.showMenu();
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

  showMenu() {
    this.currentView = 'menu';
    const contentArea = document.getElementById("contentArea");
    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 2rem;">
        <h2>📊 Panel de Reportes</h2>
        <p style="color: var(--text-light);">Selecciona el tipo de reporte que deseas visualizar.</p>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
        <!-- Card Urgencias -->
        <div class="card report-card" style="cursor: pointer; transition: var(--transition); border-top: 4px solid var(--primary);" onclick="Modules.reportes.loadUrgenciasView()">
          <div style="font-size: 2rem; margin-bottom: 1rem;">🚑</div>
          <h3>Reporte de Urgencias</h3>
          <p style="font-size: 0.875rem; color: var(--text-light); margin-top: 0.5rem;">
            Estadísticas de ingresos, egresos y ocupación en tiempo real del área de urgencias.
          </p>
          <div style="margin-top: 1.5rem; color: var(--primary); font-weight: 600; font-size: 0.85rem;">
            Ver reporte →
          </div>
        </div>

        <!-- Card Estudios -->
        <div class="card report-card" style="cursor: pointer; transition: var(--transition); border-top: 4px solid #8b5cf6;" onclick="Modules.reportes.loadEstudiosView()">
          <div style="font-size: 2rem; margin-bottom: 1rem;">🧪</div>
          <h3>Reporte de Estudios</h3>
          <p style="font-size: 0.875rem; color: var(--text-light); margin-top: 0.5rem;">
            Resumen de estudios paraclínicos realizados por laboratorio y tipo (Rayos X, Sangre, etc).
          </p>
          <div style="margin-top: 1.5rem; color: #8b5cf6; font-weight: 600; font-size: 0.85rem;">
            Ver reporte →
          </div>
        </div>

        <!-- Placeholder para futuros reportes -->
        <div class="card report-card" style="opacity: 0.6; border-top: 4px solid var(--secondary);">
          <div style="font-size: 2rem; margin-bottom: 1rem;">📅</div>
          <h3>Reporte de Consultas</h3>
          <p style="font-size: 0.875rem; color: var(--text-light); margin-top: 0.5rem;">
            Análisis de flujo de pacientes y especialidades más demandadas.
          </p>
          <div style="margin-top: 1.5rem; color: var(--text-light); font-weight: 600; font-size: 0.85rem;">
            Próximamente
          </div>
        </div>
      </div>
    `;
  },

  async loadUrgenciasView() {
    this.currentView = 'urgencias';
    this.renderViewWithFilter('🚑 Reporte Detallado de Urgencias', 'refreshUrgenciasBtn');
    document.getElementById("refreshUrgenciasBtn").addEventListener("click", () => this.fetchUrgenciasData());
    this.fetchUrgenciasData();
  },

  async loadEstudiosView() {
    this.currentView = 'estudios';
    this.renderViewWithFilter('🧪 Reporte de Estudios Paraclínicos', 'refreshEstudiosBtn');
    document.getElementById("refreshEstudiosBtn").addEventListener("click", () => this.fetchEstudiosData());
    this.fetchEstudiosData();
  },

  renderViewWithFilter(title, btnId) {
    const contentArea = document.getElementById("contentArea");
    let hospitalOptions = this.hospitales.map(h => 
      `<option value="${h.UNI_ORG}" ${this.selectedHospital === h.UNI_ORG ? 'selected' : ''}>${h.NOMUO}</option>`
    ).join('');

    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
          <div>
            <button class="btn btn-secondary" style="margin-bottom: 0.5rem;" onclick="Modules.reportes.showMenu()">← Volver al Menú</button>
            <h2>${title}</h2>
          </div>
          <button id="${btnId}" class="btn btn-primary">🔄 Actualizar Datos</button>
        </div>

        <div style="background: var(--background); padding: 1rem; border-radius: 8px; display: flex; align-items: center; gap: 1rem;">
          <label for="filterHospital" style="font-weight: 600; font-size: 0.9rem;">Filtrar por Hospital:</label>
          <select id="filterHospital" class="form-group" style="margin-bottom: 0; width: auto; min-width: 250px;">
            <option value="">Todos los hospitales</option>
            ${hospitalOptions}
          </select>
        </div>
      </div>

      <div id="reportDataContainer">
        <p style="text-align: center; color: var(--text-light); padding: 3rem;">Cargando datos del reporte...</p>
      </div>
    `;

    document.getElementById("filterHospital").addEventListener("change", (e) => {
      this.selectedHospital = e.target.value;
      if (this.currentView === 'urgencias') this.fetchUrgenciasData();
      if (this.currentView === 'estudios') this.fetchEstudiosData();
    });
  },

  async fetchUrgenciasData() {
    try {
      const url = `../api/reportes/urgencias.php?hospital_id=${this.selectedHospital}`;
      const response = await fetch(url, { credentials: "include" });
      const res = await response.json();

      if (res.ok) {
        this.renderUrgenciasStats(res.data);
      } else {
        UI.toast.show(res.message, "error");
      }
    } catch (error) {
      UI.toast.show("Error al conectar con la API", "error");
    }
  },

  renderUrgenciasStats(data) {
    const container = document.getElementById("reportDataContainer");
    container.innerHTML = `
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="border-left: 4px solid var(--primary);">
          <h3 style="color: var(--text-light); font-size: 0.9rem; text-transform: uppercase;">Ingresos Totales</h3>
          <div id="statIngresos" style="font-size: 2.5rem; font-weight: 700; color: var(--primary); margin: 0.5rem 0;">${data.ingresos}</div>
        </div>
        
        <div class="card" style="border-left: 4px solid var(--danger);">
          <h3 style="color: var(--text-light); font-size: 0.9rem; text-transform: uppercase;">Egresos Totales</h3>
          <div id="statEgresos" style="font-size: 2.5rem; font-weight: 700; color: var(--danger); margin: 0.5rem 0;">${data.egresos}</div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981;">
          <h3 style="color: var(--text-light); font-size: 0.9rem; text-transform: uppercase;">Pacientes en Área</h3>
          <div id="statActivos" style="font-size: 2.5rem; font-weight: 700; color: #10b981; margin: 0.5rem 0;">${Math.max(0, data.ingresos - data.egresos)}</div>
        </div>
      </div>

      <div class="card">
        <h3>🕒 Últimos Movimientos (Urgencias)</h3>
        <div id="urgRecientesTable" class="table-container" style="margin-top: 1rem;"></div>
      </div>
    `;
    this.renderUrgenciasTable(data.recientes);
  },

  async fetchEstudiosData() {
    try {
      const url = `../api/reportes/estudios.php?hospital_id=${this.selectedHospital}`;
      const response = await fetch(url, { credentials: "include" });
      const res = await response.json();

      if (res.ok) {
        this.renderEstudiosStats(res.data);
      } else {
        UI.toast.show(res.message, "error");
      }
    } catch (error) {
      UI.toast.show("Error al conectar con la API", "error");
    }
  },

  renderEstudiosStats(data) {
    const container = document.getElementById("reportDataContainer");
    
    let resumenHtml = data.resumen.map(r => `
      <div class="card" style="background: var(--background); border: none;">
        <h4 style="color: var(--text-light); font-size: 0.8rem; text-transform: uppercase;">${r.NOMBRELABORATORIO}</h4>
        <div style="font-size: 1.5rem; font-weight: 700; color: #8b5cf6;">${r.total_estudios} <span style="font-size: 0.9rem; font-weight: 400;">estudios</span></div>
      </div>
    `).join('');

    if (data.resumen.length === 0) {
      resumenHtml = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-light);">No hay estudios registrados.</p>';
    }

    container.innerHTML = `
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        ${resumenHtml}
      </div>

      <div class="card">
        <h3>📊 Desglose Detallado por Tipo de Estudio</h3>
        <div class="table-container" style="margin-top: 1rem;">
          <table>
            <thead>
              <tr>
                <th>Laboratorio / Depto</th>
                <th>Área Relacionada</th>
                <th>Tipo de Estudio</th>
                <th style="text-align: center;">Total Realizados</th>
              </tr>
            </thead>
            <tbody>
              ${data.detallado.map(d => `
                <tr>
                  <td><strong>${d.NOMBRELABORATORIO}</strong></td>
                  <td>${d.NOMBREAREA}</td>
                  <td>${d.NOMBREESTUDIO}</td>
                  <td style="text-align: center;"><span class="badge" style="background: #ede9fe; color: #8b5cf6; padding: 4px 12px;">${d.total}</span></td>
                </tr>
              `).join('')}
              ${data.detallado.length === 0 ? '<tr><td colspan="4" style="text-align: center;">Sin datos</td></tr>' : ''}
            </tbody>
          </table>
        </div>
      </div>
    `;
  },

  renderUrgenciasTable(data) {
    const container = document.getElementById("urgRecientesTable");
    if (!data || data.length === 0) {
      container.innerHTML = `<p style="padding: 2rem; text-align: center; color: var(--text-light);">No hay movimientos registrados.</p>`;
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Fecha y Hora</th>
            <th>Habitación</th>
          </tr>
        </thead>
        <tbody>
    `;

    data.forEach(item => {
      const badge = item.tipo_mov === 'Ingreso' 
        ? '<span style="color: var(--primary); background: #eff6ff; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">Ingreso</span>' 
        : '<span style="color: var(--danger); background: #fef2f2; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">Egreso</span>';
      
      html += `
        <tr>
          <td>${badge}</td>
          <td>${new Date(item.fecha).toLocaleString()}</td>
          <td><strong>${item.NOMBREHABITACION}</strong></td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
  },

  animateValue(id, start, end, duration) {
    const obj = document.getElementById(id);
    if (!obj) return;
    let startTimestamp = null;
    const step = (timestamp) => {
      if (!startTimestamp) startTimestamp = timestamp;
      const progress = Math.min((timestamp - startTimestamp) / duration, 1);
      obj.innerHTML = Math.floor(progress * (end - start) + start);
      if (progress < 1) window.requestAnimationFrame(step);
    };
    window.requestAnimationFrame(step);
  }
};
