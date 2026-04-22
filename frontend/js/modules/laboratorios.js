window.Modules.laboratorios = {
  data: [],
  filteredData: [],

  init() {
    this.renderLayout();
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");

    contentArea.innerHTML = `
      <div class="card" style="margin-bottom:1rem; display:flex; justify-content:space-between;">
        <input type="text" id="searchLab" placeholder="Buscar laboratorio..." />
        <button id="addLabBtn" class="btn btn-primary">+ Nuevo</button>
      </div>

      <div id="labTable"></div>
    `;

    document.getElementById("searchLab")
      .addEventListener("input", e => this.filter(e.target.value));

    document.getElementById("addLabBtn")
      .addEventListener("click", () => this.showModal());
  },

  async loadData() {
    try {
      const res = await fetch("../api/laboratorios/listar_laboratorios.php", {
        credentials: "include"
      });

      const data = await res.json();

      if (data.ok) {
        this.data = data.data;
        this.filteredData = [...this.data];
        this.renderTable();
      }
    } catch (e) {
      UI.toast.show("Error cargando datos", "error");
    }
  },

  renderTable() {
    const container = document.getElementById("labTable");

    if (this.filteredData.length === 0) {
      container.innerHTML = "Sin registros";
      return;
    }

    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Laboratorio</th>
            <th>Área</th>
            <th>Ubicación</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
    `;

    this.filteredData.forEach(l => {
      html += `
        <tr>
          <td>${l.ID}</td>
          <td>${l.NOMBRELABORATORIO}</td>
          <td>${l.NOMBREAREA}</td>
          <td>${l.UBICACION || 'N/A'}</td>
          <td>
            <button onclick='Modules.laboratorios.showModal(${JSON.stringify(l)})'>✏️</button>
            <button onclick="Modules.laboratorios.confirmDelete(${l.ID})">🗑️</button>
          </td>
        </tr>
      `;
    });

    html += "</tbody></table>";
    container.innerHTML = html;
  },

  filter(q) {
    q = q.toLowerCase();
    this.filteredData = this.data.filter(l =>
      l.NOMBRELABORATORIO.toLowerCase().includes(q) ||
      l.NOMBREAREA.toLowerCase().includes(q)
    );
    this.renderTable();
  },

  showModal(item = null) {
    const isEdit = item !== null;

    const body = `
      <input id="id" placeholder="ID" value="${item?.ID || ''}" ${isEdit ? 'readonly' : ''}/>
      <input id="nombre" placeholder="Nombre" value="${item?.NOMBRELABORATORIO || ''}"/>
      <input id="ubicacion" placeholder="Ubicación" value="${item?.UBICACION || ''}"/>
      <input id="area" placeholder="ID Área" value="${item?.AREAS_ID || ''}"/>
    `;

    const footer = `
      <button onclick="closeModal()">Cancelar</button>
      <button id="saveBtn">Guardar</button>
    `;

    UI.modal.show(isEdit ? "Editar" : "Nuevo", body, footer);

    document.getElementById("saveBtn")
      .addEventListener("click", () => this.save(isEdit));
  },

  async save(isEdit) {
    const data = {
      id: document.getElementById("id").value,
      nombreLaboratorio: document.getElementById("nombre").value,
      ubicacion: document.getElementById("ubicacion").value,
      areasId: document.getElementById("area").value
    };

    const url = isEdit
      ? "../api/laboratorios/editar_laboratorios.php"
      : "../api/laboratorios/insertar_laboratorios.php";

    const method = isEdit ? "PUT" : "POST";

    try {
      const res = await fetch(url, {
        method: method,
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

    } catch (e) {
      UI.toast.show("Error en request", "error");
    }
  },

  confirmDelete(id) {
    const body = `¿Eliminar laboratorio ${id}?`;
    const footer = `
      <button onclick="closeModal()">Cancelar</button>
      <button onclick="Modules.laboratorios.delete(${id})">Eliminar</button>
    `;

    UI.modal.show("Confirmar", body, footer);
  },

  async delete(id) {
    try {
      const res = await fetch("../api/laboratorios/eliminar_laboratorios.php", {
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

    } catch (e) {
      UI.toast.show("Error eliminando", "error");
    }
  }
};