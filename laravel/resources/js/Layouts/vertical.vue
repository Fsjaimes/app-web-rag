<script>
import { Link } from '@inertiajs/vue3';
// import router from "@/router";
import simplebar from "simplebar-vue";
import { layoutComputed, layoutMethods } from "@/state/helpers";

import NavBar from "@/Components/nav-bar.vue";
import Menu from "@/Components/menu.vue";
import RightBar from "@/Components/right-bar.vue";
import Footer from "@/Components/footer.vue";

/**
 * Vertical layout
 */
export default {
  components: { NavBar, RightBar, Footer, simplebar, Menu, Link},
  data() {
    return {
      isMenuCondensed: false,
    };
  },
  computed: {

    ...layoutComputed,

    sidebarDoc() {
      return this.$page.url.startsWith('/documentacion');
    },
  },
  created: () => {
    document.body.removeAttribute("data-layout", "horizontal");
    document.body.removeAttribute("data-topbar", "dark");
    document.body.removeAttribute("data-layout-size", "boxed");
  },
  methods: {
    ...layoutMethods,

    updateSidebarSize() {
      document.documentElement.setAttribute("data-sidebar-size", this.sidebarSize);
    },

    /**
     * Alterna entre menú colapsado (sm-hover) y menú fijo/expandido (sm-hover-active).
     * El botón con el ícono de círculo fija o suelta el menú lateral.
     */
    toggleSidebarPin() {
      localStorage.setItem('rmenu', 'vertical');
      document.documentElement.setAttribute("data-layout", "vertical");
      const newSize = this.sidebarSize === "sm-hover" ? "sm-hover-active" : "sm-hover";
      this.changeSidebarSize({ sidebarSize: newSize });
      document.documentElement.setAttribute("data-sidebar-size", newSize);
    },
    toggleMenu() {
      document.body.classList.toggle("sidebar-enable");

      if (window.screen.width >= 992) {
        // eslint-disable-next-line no-unused-vars
        router.afterEach((routeTo, routeFrom) => {
          document.body.classList.remove("sidebar-enable");
          document.body.classList.remove("vertical-collpsed");
        });
        document.body.classList.toggle("vertical-collpsed");
      } else {
        // eslint-disable-next-line no-unused-vars
        router.afterEach((routeTo, routeFrom) => {
          document.body.classList.remove("sidebar-enable");
        });
        document.body.classList.remove("vertical-collpsed");
      }
      this.isMenuCondensed = !this.isMenuCondensed;
    },
    toggleRightSidebar() {
      document.body.classList.toggle("right-bar-enabled");
    },
    hideRightSidebar() {
      document.body.classList.remove("right-bar-enabled");
    },

  },
  mounted() {
    this.changeSidebarSize({ sidebarSize: "sm-hover" });
    document.documentElement.setAttribute("data-sidebar-size", this.sidebarSize);

    document.getElementById('overlay').addEventListener('click', () => {
      document.body.classList.remove('vertical-sidebar-enable');
    });

    window.addEventListener("resize", () => {
      document.body.classList.remove('vertical-sidebar-enable');
      const hamburgerIcon = document.querySelector(".hamburger-icon");
      if (hamburgerIcon) {
        hamburgerIcon.classList.add("open");
      }
      this.updateSidebarSize();
    });

  },
  unmounted() {
    window.removeEventListener("resize", this.updateSidebarSize )
  }
};
</script>

<template>
  <div id="layout-wrapper">
    <NavBar />
    <div>
      <!-- ========== Left Sidebar Start ========== -->
      <!-- ========== App Menu ========== -->
      <div class="app-menu navbar-menu">
        <!-- LOGO -->
        <div class="navbar-brand-box mt-3">
          <!-- Dark Logo-->
          <Link href="/dashboard" class="logo logo-dark">
            <span class="logo-sm">
              <img src="@assets/images/logo-verde-UTS.png" alt="UTS" height="40" />
            </span>
            <span class="logo-lg">
              <img src="@assets/images/logo-verde-UTS.png" alt="UTS" height="60" />
            </span>
          </Link>
          <!-- Light Logo-->
          <Link href="/dashboard" class="logo logo-light">
            <span class="logo-sm">
              <img src="@assets/images/escudo-UTS.png" alt="UTS" height="40" />
            </span>
            <span class="logo-lg">
              <img src="@assets/images/logo-blanco-UTS.png" alt="UTS" height="60" />
            </span>
          </Link>
          <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover" @click="toggleSidebarPin">
            <i
              class="ri-record-circle-line"
              :class="sidebarColor === 'dark' ? 'text-white' : 'text-dark'"
            ></i>
          </button>
        </div>

        <simplebar id="scrollbar" class="h-100" ref="scrollbar">
            <Menu />
        </simplebar>
        <div class="sidebar-background"></div>
      </div>
      <!-- Left Sidebar End -->
      <!-- Vertical Overlay-->
      <div class="vertical-overlay" id="overlay"></div>
    </div>
    <!-- ============================================================== -->
    <!-- Start Page Content here -->
    <!-- ============================================================== -->

    <div class="main-content">
      <div class="page-content">
        <!-- Start Content-->
        <b-container fluid>
          <slot />
        </b-container>
      </div>
      <Footer />
    </div>
    <RightBar />
  </div>
</template>
