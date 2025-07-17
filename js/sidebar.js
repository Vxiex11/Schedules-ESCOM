// Función para los tooltips del sidebar
function setupSidebarTooltips() {
  try {
    const tooltip = document.getElementById("tooltip");
    if (!tooltip) {
      console.warn("Elemento tooltip no encontrado");
      return;
    }

    const sidebarItems = document.querySelectorAll(".sidebar-item");
    if (!sidebarItems.length) {
      console.warn("No se encontraron elementos .sidebar-item");
      return;
    }

    sidebarItems.forEach(item => {
      const tooltipText = item.getAttribute("data-tooltip");
      if (!tooltipText) {
        console.warn("Elemento sidebar-item sin data-tooltip", item);
        return;
      }

      item.addEventListener("mouseenter", function() {
        const rect = this.getBoundingClientRect();
        tooltip.textContent = tooltipText;
        tooltip.style.top = `${rect.top + window.scrollY + rect.height / 12}px`;
        tooltip.style.left = `${rect.right + window.scrollX + 10}px`;
        tooltip.style.opacity = "1";
      });

      item.addEventListener("mouseleave", function() {
        tooltip.style.opacity = "0";
      });
    });
  } catch (error) {
    console.error("Error en setupSidebarTooltips:", error);
  }
}

// Función para el menú móvil
function setupMobileMenu() {
  try {
    const toggleBtn = document.querySelector('.mobile-menu-toggle');
    if (!toggleBtn) {
      console.warn("Botón móvil no encontrado");
      return;
    }

    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) {
      console.warn("Sidebar no encontrado");
      return;
    }

    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    if (!sidebarLinks.length) {
      console.warn("No se encontraron enlaces .sidebar-link");
    }

    // Evento para el botón toggle
    toggleBtn.addEventListener('click', function() {
      sidebar.classList.toggle('active');
      this.classList.toggle('active');
    });

    // Eventos para los enlaces
    sidebarLinks.forEach(link => {
      link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('active');
          toggleBtn.classList.remove('active');
        }
      });
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function(event) {
      if (window.innerWidth > 768) return;
      
      const isClickInsideSidebar = event.target.closest('.sidebar');
      const isClickOnToggle = event.target.closest('.mobile-menu-toggle');
      
      if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
        toggleBtn.classList.remove('active');
      }
    });
  } catch (error) {
    console.error("Error en setupMobileMenu:", error);
  }
}

// Inicialización
function init() {
  try {
    // Esperar a que el DOM esté completamente cargado
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function() {
        setupSidebarTooltips();
        setupMobileMenu();
      });
    } else {
      // Si el DOM ya está listo
      setupSidebarTooltips();
      setupMobileMenu();
    }
  } catch (error) {
    console.error("Error en la inicialización:", error);
  }
}

// Ejecutar la inicialización
init();