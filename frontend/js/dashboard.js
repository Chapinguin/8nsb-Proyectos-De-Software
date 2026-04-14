/**
 * Dashboard Controller - Hospital HIS
 * Manages user session, navigation, and module loading based on roles.
 */

const state = {
  user: null,
  activeModule: 'dashboard'
};

const dom = {
  userNombre: document.getElementById("userNombre"),
  userBadge: document.getElementById("userBadge"),
  logoutBtn: document.getElementById("logoutBtn"),
  contentArea: document.getElementById("contentArea"),
  moduleTitle: document.getElementById("moduleTitle"),
  sidebarNav: document.getElementById("sidebarNav")
};

// --- Role-Based Module Configuration ---
const MODULES_CONFIG = [
  { 
    id: 'dashboard', 
    label: 'Dashboard', 
    icon: '🏠', 
    roles: ['Administrador', 'Recepcion', 'Medico', 'Laboratorio'] 
  },
  { 
    id: 'hospital', 
    label: 'Hospitales', 
    icon: '🏢', 
    roles: ['Administrador'] 
  },
  { 
    id: 'especialidades', 
    label: 'Especialidades', 
    icon: '🩺', 
    roles: ['Administrador'] 
  },
  { 
    id: 'areas', 
    label: 'Áreas', 
    icon: '📍', 
    roles: ['Administrador'] 
  },
  { 
    id: 'medicos', 
    label: 'Médicos', 
    icon: '👨‍⚕️', 
    roles: ['Administrador', 'Recepcion'] 
  },
  { 
    id: 'consultas', 
    label: 'Consultas', 
    icon: '📅', 
    roles: ['Administrador', 'Recepcion', 'Medico'] 
  },
  { 
    id: 'ingresos', 
    label: 'Ingresos/Egresos', 
    icon: '🚪', 
    roles: ['Administrador', 'Recepcion'] 
  },
  { 
    id: 'estudios', 
    label: 'Estudios', 
    icon: '🧪', 
    roles: ['Administrador', 'Medico', 'Laboratorio'] 
  },
  {
    id: 'usuarios',
    label: 'Usuarios y Accesos',
    icon: '👥',
    roles: ['Administrador']
  },
  {
    id: 'roles',
    label: 'Configurar Roles',
    icon: '🔐',
    roles: ['Administrador']
  }
];

// --- Session Management ---
async function checkSession() {
  try {
    const response = await fetch("../api/auth/me.php", { credentials: "include" });
    const data = await response.json();

    if (!response.ok || !data.ok) {
      window.location.href = "./login.html";
      return;
    }

    state.user = data.user;
    updateUserUI();
    renderSidebar(); // Generar el menú después de conocer al usuario
  } catch (error) {
    console.error("Session check failed:", error);
    window.location.href = "./login.html";
  }
}

function updateUserUI() {
  dom.userNombre.textContent = state.user.nombre;
  dom.userBadge.textContent = state.user.roles[0]?.nombre || "Usuario";
  dom.userBadge.classList.remove("skeleton");
}

dom.logoutBtn.addEventListener("click", async () => {
  try {
    await fetch("../api/auth/logout.php", { credentials: "include" });
    window.location.href = "./login.html";
  } catch (error) {
    UI.toast.show("Error al cerrar sesión", "error");
  }
});

// --- Dynamic Sidebar Rendering ---
function renderSidebar() {
  const userRoles = state.user.roles.map(r => r.nombre);
  dom.sidebarNav.innerHTML = "";

  MODULES_CONFIG.forEach(module => {
    // Verificar si el usuario tiene al menos uno de los roles permitidos para este módulo
    const hasAccess = module.roles.some(role => userRoles.includes(role));

    if (hasAccess) {
      const navItem = document.createElement("li");
      navItem.className = "nav-item";
      
      const navLink = document.createElement("a");
      navLink.href = "#";
      navLink.className = `nav-link ${state.activeModule === module.id ? 'active' : ''}`;
      navLink.setAttribute("data-module", module.id);
      navLink.innerHTML = `<span>${module.icon}</span> ${module.label}`;
      
      navLink.addEventListener("click", (e) => {
        e.preventDefault();
        handleModuleSwitch(module.id);
      });

      navItem.appendChild(navLink);
      dom.sidebarNav.appendChild(navItem);
    }
  });
}

function handleModuleSwitch(moduleName) {
  // Update UI state
  const links = dom.sidebarNav.querySelectorAll(".nav-link");
  links.forEach(l => l.classList.remove("active"));
  
  const activeLink = dom.sidebarNav.querySelector(`[data-module="${moduleName}"]`);
  if (activeLink) activeLink.classList.add("active");

  loadModule(moduleName);
}

async function loadModule(moduleName) {
  state.activeModule = moduleName;
  const config = MODULES_CONFIG.find(m => m.id === moduleName);
  dom.moduleTitle.textContent = config ? config.label : "Hospital";
  
  if (moduleName === 'dashboard') {
    renderDashboardHome();
    return;
  }

  // Show Loading State
  UI.showSkeleton("#contentArea");

  try {
    // Si el módulo ya está cargado en memoria
    if (window.Modules && window.Modules[moduleName]) {
      window.Modules[moduleName].init();
    } else {
      // Cargar el script del módulo de forma asíncrona
      const script = document.createElement("script");
      script.src = `./js/modules/${moduleName}.js`;
      script.onerror = () => {
        dom.contentArea.innerHTML = `
          <div class="card" style="text-align: center; border-left: 4px solid var(--danger);">
            <h3>Módulo en Desarrollo</h3>
            <p>El módulo <strong>${moduleName}</strong> aún no está disponible.</p>
          </div>`;
      };
      script.onload = () => {
        if (window.Modules && window.Modules[moduleName]) {
          window.Modules[moduleName].init();
        }
      };
      document.body.appendChild(script);
    }
  } catch (error) {
    console.error(`Error loading module ${moduleName}:`, error);
    dom.contentArea.innerHTML = `<div class="card error">Error al cargar el módulo ${moduleName}</div>`;
  }
}

function renderDashboardHome() {
  const userRoles = state.user.roles.map(r => r.nombre);
  const accessibleModules = MODULES_CONFIG.filter(m => 
    m.id !== 'dashboard' && m.roles.some(role => userRoles.includes(role))
  );

  let modulesHtml = accessibleModules.map(m => `
    <div class="card" style="background: var(--background); border: none; text-align: center; cursor: pointer; transition: var(--transition);" 
         onclick="document.querySelector('[data-module=\\'${m.id}\\']').click()">
      <h3 style="color: var(--primary); font-size: 2rem;">${m.icon}</h3>
      <p style="font-weight: 600;">${m.label}</p>
      <p style="font-size: 0.875rem; color: var(--text-light);">Acceso permitido</p>
    </div>
  `).join('');

  dom.contentArea.innerHTML = `
    <div class="card">
      <h2>¡Hola de nuevo, ${state.user.nombre}!</h2>
      <p>Bienvenido al Sistema de Gestión Hospitalaria. Según tu rol como <strong>${userRoles.join(', ')}</strong>, tienes acceso a los siguientes módulos:</p>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        ${modulesHtml}
      </div>
    </div>
  `;
}

// Global registry for modules
window.Modules = {};

// Initialize
checkSession();
