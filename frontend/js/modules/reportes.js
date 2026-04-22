/**
 * Reportes Module - Hospital HIS
 * Centralized reporting module with sub-sections
 */

window.Modules.reportes = {
  currentView: 'menu', // 'menu' or 'urgencias'

  async init() {
    this.showMenu();
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

        <div class="card report-card" style="opacity: 0.6; border-top: 4px solid var(--secondary);">
          <div style="font-size: 2rem; margin-bottom: 1rem;">🧪</div>
          <h3>Reporte de Estudios</h3>
          <p style="font-size: 0.875rem; color: var(--text-light); margin-top: 0.5rem;">
            Resumen de estudios realizados por laboratorio y tipo.
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
    const contentArea = document.getElementById("contentArea");
    
    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
          <button class="btn btn-secondary" style="margin-bottom: 0.5rem;" onclick="Modules.reportes.showMenu()">← Volver al Menú</button>
          <h2>🚑 Reporte Detallado de Urgencias</h2>
        </div>
        <button id="refreshUrgenciasBtn" class="btn btn-primary">🔄 Actualizar Datos</button>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="border-left: 4px solid var(--primary);">
          <h3 style="color: var(--text-light); font-size: 0.9rem; text-transform: uppercase;">Ingresos Totales</h3>
          <div id="statIngresos" style="font-size: 2.5rem; font-weight: 700; color: var(--primary); margin: 0.5rem 0;">0</div>
        </div>
        
        <div class="card" style="border-left: 4px solid var(--danger);">
          <h3 style="color: var(--text-light); font-size: 0.9rem; text-transform: uppercase;">Egresos Totales</h3>
          <div id="statEgresos" style="font-size: 2.5rem; font-weight: 700; color: var(--danger); margin: 0.5rem 0;">0</div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981;">
          <h3 style="color: var(--text-light); font-size: 0.9rem; text-transform: uppercase;">Pacientes en Área</h3>
          <div id="statActivos" style="font-size: 2.5rem; font-weight: 700; color: #10b981; margin: 0.5rem 0;">0</div>
        </div>
      </div>

      <div class="card">
        <h3>🕒 Últimos Movimientos (Urgencias)</h3>
        <div id="urgRecientesTable" class="table-container" style="margin-top: 1rem;">
          <p style="text-align: center; color: var(--text-light); padding: 2rem;">Cargando...</p>
        </div>
      </div>
    `;

    document.getElementById("refreshUrgenciasBtn").addEventListener("click", () => this.fetchUrgenciasData());
    this.fetchUrgenciasData();
  },

  async fetchUrgenciasData() {
    try {
      const response = await fetch("../api/reportes/urgencias.php", { credentials: "include" });
      const res = await response.json();

      if (res.ok) {
        this.animateValue("statIngresos", 0, res.data.ingresos, 800);
        this.animateValue("statEgresos", 0, res.data.egresos, 800);
        this.animateValue("statActivos", 0, Math.max(0, res.data.ingresos - res.data.egresos), 800);
        
        this.renderTable(res.data.recientes);
      } else {
        UI.toast.show(res.message, "error");
      }
    } catch (error) {
      UI.toast.show("Error al conectar con la API", "error");
    }
  },

  renderTable(data) {
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
