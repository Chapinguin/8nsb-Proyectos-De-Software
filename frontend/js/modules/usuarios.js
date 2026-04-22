/**
 * Usuarios Module - Hospital HIS
 */

window.Modules.usuarios = {
  data: [],
  roles: [],

  async init() {
    this.renderLayout();
    await this.loadRoles();
    this.loadData();
  },

  renderLayout() {
    const contentArea = document.getElementById("contentArea");
    contentArea.innerHTML = `
      <div class="card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h2 style="font-size: 1.1rem;">Gestión de Usuarios y Accesos</h2>
          <p style="font-size: 0.85rem; color: var(--text-light);">Crea cuentas y asigna permisos por rol.</p>
        </div>
        <button id="addUsuarioBtn" class="btn btn-primary">
          <span>+</span> Nuevo Usuario
        </button>
      </div>
      
      <div id="usuariosTableContainer" class="table-container">
        <!-- Table will be rendered here -->
      </div>
    `;

    document.getElementById("addUsuarioBtn").addEventListener("click", () => this.showModal());
  },

  async loadRoles() {
    try {
      const response = await fetch("../api/roles/listar.php", { credentials: "include" });
      const res = await response.json();
      if (res.ok) this.roles = res.data;
    } catch (error) {
      console.error("Error al cargar roles:", error);
    }
  },

  async loadData() {
    try {
      const response = await fetch("../api/usuarios/listar.php", { credentials: "include" });
      const res = await response.json();
      
      if (res.ok) {
        this.data = res.data;
        this.renderTable();
      } else {
        UI.toast.show(res.message, "error");
      }
    } catch (error) {
      UI.toast.show("Error al cargar usuarios", "error");
    }
  },

  renderTable() {
    const container = document.getElementById("usuariosTableContainer");
    
    let html = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre / Usuario</th>
            <th>Correo</th>
            <th>Roles</th>
            <th>Estatus</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
    `;

    this.data.forEach(item => {
      const rolesBadges = (item.roles || []).map(r => 
        `<span class="btn btn-secondary" style="font-size: 0.65rem; padding: 1px 6px; cursor: default; margin-right: 2px;">${r.nombre}</span>`
      ).join('');

      const estatusBadge = item.estatus == 1 
        ? `<span style="color: var(--success); font-size: 0.75rem; font-weight: 600;">● Activo</span>` 
        : `<span style="color: var(--danger); font-size: 0.75rem; font-weight: 600;">○ Inactivo</span>`;

      html += `
        <tr>
          <td><span style="font-weight: 600; color: var(--primary);">#${item.id}</span></td>
          <td>
            <div style="font-weight: 600;">${item.nombre}</div>
            <div style="font-size: 0.75rem; color: var(--text-light);">@${item.username}</div>
          </td>
          <td style="font-size: 0.85rem;">${item.correo || '<i style="color: #ccc;">N/A</i>'}</td>
          <td>${rolesBadges || '<small style="color: var(--text-light);">Sin roles</small>'}</td>
          <td>${estatusBadge}</td>
          <td style="text-align: right;">
            <button class="btn btn-secondary btn-sm" onclick="Modules.usuarios.showModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">✏️</button>
            <button class="btn btn-secondary btn-sm" onclick="Modules.usuarios.confirmDelete(${item.id})">🗑️</button>
          </td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
  },

  showModal(item = null) {
    const isEdit = item !== null;
    const title = isEdit ? "Editar Usuario" : "Nuevo Usuario";
    
    // Generar checkboxes para roles
    const userRoleIds = isEdit ? (item.roles || []).map(r => parseInt(r.id)) : [];
    const rolesHtml = this.roles.map(rol => `
      <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; margin-bottom: 0.5rem; cursor: pointer;">
        <input type="checkbox" name="u_roles" value="${rol.id}" ${userRoleIds.includes(parseInt(rol.id)) ? 'checked' : ''}>
        ${rol.nombre}
      </label>
    `).join('');

    const body = `
      <form id="usuarioForm">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="u_nombre">Nombre Completo</label>
            <input type="text" id="u_nombre" value="${item ? item.nombre : ''}" placeholder="Ej: Juan Pérez" required>
          </div>
          <div class="form-group">
            <label for="u_username">Usuario</label>
            <input type="text" id="u_username" value="${item ? item.username : ''}" placeholder="Ej: jperez" required>
          </div>
        </div>
        
        <div class="form-group">
          <label for="u_correo">Correo Electrónico</label>
          <input type="email" id="u_correo" value="${item ? (item.correo || '') : ''}" placeholder="ejemplo@hospital.com">
        </div>

        <div class="form-group">
          <label for="u_pass">Contraseña ${isEdit ? '(Dejar en blanco para no cambiar)' : ''}</label>
          <input type="password" id="u_pass" placeholder="••••••••" ${isEdit ? '' : 'required'}>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label>Roles de Acceso</label>
            <div style="background: var(--background); padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); max-height: 150px; overflow-y: auto;">
              ${rolesHtml}
            </div>
          </div>
          <div class="form-group">
            <label for="u_estatus">Estatus de la Cuenta</label>
            <select id="u_estatus">
              <option value="1" ${item && item.estatus == 1 ? 'selected' : ''}>Activo</option>
              <option value="0" ${item && item.estatus == 0 ? 'selected' : ''}>Inactivo</option>
            </select>
          </div>
        </div>
      </form>
    `;

    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="saveUsuarioBtn">${isEdit ? 'Actualizar' : 'Crear Usuario'}</button>
    `;

    UI.modal.show(title, body, footer);

    document.getElementById("saveUsuarioBtn").addEventListener("click", () => this.save(isEdit, item?.id));
  },

  async save(isEdit, id = null) {
    const data = {
      username: document.getElementById("u_username").value.trim(),
      nombre: document.getElementById("u_nombre").value.trim(),
      correo: document.getElementById("u_correo").value.trim(),
      password: document.getElementById("u_pass").value,
      estatus: document.getElementById("u_estatus").value
    };

    if (id) data.id = id;

    if (!data.username || !data.nombre || (!isEdit && !data.password)) {
      UI.toast.show("Nombre, Usuario y Contraseña son obligatorios", "warning");
      return;
    }

    const rolesSeleccionados = Array.from(document.querySelectorAll('input[name="u_roles"]:checked')).map(cb => cb.value);

    try {
      // 1. Guardar/Actualizar Usuario
      const endpoint = isEdit ? "editar.php" : "insertar.php";
      const method = isEdit ? "PUT" : "POST";
      
      const response = await fetch(`../api/usuarios/${endpoint}`, {
        method: method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
        credentials: "include"
      });

      const res = await response.json();

      if (!res.ok) {
        UI.toast.show(res.message, "error");
        return;
      }

      const usuarioId = isEdit ? id : res.id;

      // 2. Gestionar Roles (Limpiar y Reasignar)
      // Primero eliminamos los roles actuales
      await fetch("../api/usuario_roles/eliminar.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ usuario_id: usuarioId }),
        credentials: "include"
      });

      // Luego asignamos los nuevos roles uno por uno
      for (const rolId of rolesSeleccionados) {
        await fetch("../api/usuario_roles/insertar.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ usuario_id: usuarioId, rol_id: rolId }),
          credentials: "include"
        });
      }

      UI.toast.show("Usuario y accesos configurados correctamente", "success");
      UI.modal.close();
      this.loadData();

    } catch (error) {
      UI.toast.show("Error al procesar la solicitud", "error");
      console.error(error);
    }
  },

  confirmDelete(id) {
    const body = `<p>¿Estás seguro de que deseas eliminar este usuario? Se perderán todos sus accesos de forma permanente.</p>`;
    const footer = `
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-danger" onclick="Modules.usuarios.delete(${id})">Eliminar Permanente</button>
    `;
    UI.modal.show("Confirmar eliminación", body, footer);
  },

  async delete(id) {
    try {
      const response = await fetch("../api/usuarios/eliminar.php", {
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
