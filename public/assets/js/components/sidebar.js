/**
 * CheckMaster UFRMI - Sidebar Component
 * Handles sidebar toggle, section collapse/expand, active states, and mobile drawer.
 */
(function (window, document) {
  'use strict';

  window.CM = window.CM || {};

  CM.sidebar = {
    _sidebar: null,
    _overlay: null,
    _burgers: null,

    init() {
      this._sidebar = document.getElementById('cm-sidebar');
      if (!this._sidebar) return;

      this._burgers = document.querySelectorAll('.cm-burger, .cm-burger-mobile, #cm-burger-toggle');
      this._overlay = document.querySelector('.cm-drawer-overlay');

      this._bindBurgers();
      this._bindSections();
      this._bindOverlay();
      this._bindKeyboard();
      this._setActiveSection();
    },

    /* Toggle sidebar on mobile */
    _bindBurgers() {
      this._burgers.forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          this.toggle();
        });
      });
    },

    toggle() {
      if (!this._sidebar) return;
      const isActive = this._sidebar.classList.toggle('is-active');

      // Also toggle mobile drawer if present
      const drawer = document.querySelector('.cm-mobile-drawer');
      if (drawer) {
        drawer.classList.toggle('is-active', isActive);
      }

      if (this._overlay) {
        this._overlay.classList.toggle('is-active', isActive);
      }

      // Prevent body scroll when sidebar is open on mobile
      document.body.classList.toggle('cm-no-scroll', isActive);
    },

    close() {
      if (!this._sidebar) return;
      this._sidebar.classList.remove('is-active');
      const drawer = document.querySelector('.cm-mobile-drawer');
      if (drawer) drawer.classList.remove('is-active');
      if (this._overlay) this._overlay.classList.remove('is-active');
      document.body.classList.remove('cm-no-scroll');
    },

    /* Collapsible sections */
    _bindSections() {
      // Top-level sections
      const sectToggles = this._sidebar.querySelectorAll('.cm-menu-section-title');
      sectToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          const section = toggle.closest('.cm-menu-section');
          if (!section) return;

          const isCollapsed = section.classList.toggle('is-collapsed');
          toggle.setAttribute('aria-expanded', !isCollapsed);

          const menuItems = section.querySelector('.cm-menu-items');
          if (menuItems) {
            if (isCollapsed) {
              menuItems.style.maxHeight = '0px';
              menuItems.style.opacity = '0';
            } else {
              menuItems.style.maxHeight = menuItems.scrollHeight + 'px';
              menuItems.style.opacity = '1';
              setTimeout(() => {
                if (!section.classList.contains('is-collapsed')) {
                  menuItems.style.maxHeight = 'none';
                }
              }, 350);
            }
          }
        });
      });

      // Second-level parent menus
      const parentToggles = this._sidebar.querySelectorAll('.cm-menu-toggle');
      parentToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          const parent = toggle.closest('.cm-menu-parent');
          if (!parent) return;

          const isCollapsed = parent.classList.toggle('is-collapsed');

          const submenu = parent.querySelector('.cm-submenu');
          if (submenu) {
            if (isCollapsed) {
              submenu.style.display = 'none';
            } else {
              submenu.style.display = 'block';
            }
          }
        });
      });


      // Initialize collapsed sections
      this._sidebar.querySelectorAll('.cm-menu-section.is-collapsed .cm-menu-items').forEach(menu => {
        menu.style.maxHeight = '0px';
        menu.style.opacity = '0';
      });
    },

    /* Close on overlay click */
    _bindOverlay() {
      if (this._overlay) {
        this._overlay.addEventListener('click', () => this.close());
      }

      // Also bind close buttons
      document.querySelectorAll('.cm-drawer-close').forEach(btn => {
        btn.addEventListener('click', () => this.close());
      });
    },

    /* Close on Escape key */
    _bindKeyboard() {
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this._sidebar.classList.contains('is-active')) {
          this.close();
        }
      });
    },

    /* Expand active section */
    _setActiveSection() {
      const activeSections = this._sidebar.querySelectorAll('.cm-menu-item a.is-active');
      activeSections.forEach(link => {
        const section = link.closest('.cm-menu-section');
        if (section) {
          section.classList.remove('is-collapsed');
          const menuItems = section.querySelector('.cm-menu-items');
          if (menuItems) {
            menuItems.style.maxHeight = 'none';
            menuItems.style.opacity = '1';
          }
          const title = section.querySelector('.cm-menu-section-title');
          if (title) title.setAttribute('aria-expanded', 'true');
        }
      });
    }
  };
})(window, document);
